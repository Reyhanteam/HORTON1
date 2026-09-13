<?php

declare(strict_types=1);

namespace App\Observers;

use App\Contracts\NotificationDispatcher;
use App\Enums\NotificationType;
use App\Enums\TransactionDirection;
use App\Models\CashbackTransaction;
use App\Models\DiscountUsage;
use App\Models\GiftCodeRedemption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\Service;
use App\Models\ServiceOperation;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;

final class DomainNotificationObserver
{
    public function created(Model $model): void
    {
        $dispatcher = app(NotificationDispatcher::class);

        if ($model instanceof Order) {
            $dispatcher->dispatch($model->user, NotificationType::ORDER_CREATED, [
                'order_id' => $model->id,
                'status' => $model->status->value,
                'amount' => (int) $model->total_amount,
                'currency' => $model->currency,
            ], 'order:'.$model->id.':created');
            return;
        }

        if ($model instanceof Payment && $model->status->value === 'pending' && $model->method->value === 'manual') {
            $dispatcher->dispatch($model->user, NotificationType::PAYMENT_PENDING, [
                'payment_id' => $model->id,
                'order_id' => $model->order_id,
                'amount' => (int) $model->amount,
                'currency' => $model->currency,
            ], 'payment:'.$model->id.':pending');
            return;
        }

        if ($model instanceof WalletTransaction) {
            $type = $model->direction === TransactionDirection::CREDIT
                ? NotificationType::WALLET_CREDITED
                : NotificationType::WALLET_DEBITED;

            $dispatcher->dispatch($model->user, $type, [
                'transaction_id' => $model->id,
                'amount' => (int) $model->amount,
                'balance_before' => (int) $model->balance_before,
                'balance_after' => (int) $model->balance_after,
            ], 'wallet-transaction:'.$model->id.':notification');
            return;
        }

        if ($model instanceof ServiceOperation && $model->status === 'running' && $model->operation === 'create') {
            $service = $model->service()->with('user')->first();
            if ($service) {
                $dispatcher->dispatch($service->user, NotificationType::SERVICE_PROVISIONING, [
                    'service_id' => $service->id,
                    'service_uuid' => $service->uuid,
                ], 'service:'.$service->id.':provisioning');
            }
            return;
        }

        if ($model instanceof DiscountUsage) {
            $model->loadMissing(['user', 'discountCode']);
            $dispatcher->dispatch($model->user, NotificationType::DISCOUNT_APPLIED, [
                'discount_code' => $model->discountCode?->code ?: '',
                'discount_amount' => (int) $model->amount,
                'order_id' => $model->order_id,
            ], 'discount-usage:'.$model->id.':notification');
            return;
        }

        if ($model instanceof GiftCodeRedemption) {
            $model->loadMissing(['user', 'giftCode']);
            $dispatcher->dispatch($model->user, NotificationType::GIFT_REDEEMED, [
                'gift_code' => $model->giftCode?->code ?: '',
                'gift_value' => (int) $model->value,
            ], 'gift-redemption:'.$model->id.':notification');
            return;
        }

        if ($model instanceof CashbackTransaction && $model->direction === TransactionDirection::CREDIT) {
            $dispatcher->dispatch($model->user, NotificationType::CASHBACK_CREDITED, [
                'amount' => (int) $model->amount,
                'transaction_id' => $model->id,
            ], 'cashback-transaction:'.$model->id.':notification');
            return;
        }

        if ($model instanceof SupportMessage && $model->sender_type === 'admin') {
            $ticket = $model->ticket()->with('user')->first();
            if ($ticket) {
                $dispatcher->dispatch($ticket->user, NotificationType::SUPPORT_TICKET_REPLIED, [
                    'ticket_id' => $ticket->id,
                    'ticket_uuid' => $ticket->uuid,
                ], 'support-message:'.$model->id.':notification');
            }
        }
    }

    public function updated(Model $model): void
    {
        $dispatcher = app(NotificationDispatcher::class);

        if ($model instanceof User) {
            if ($model->wasChanged('phone_verified_at') && $model->phone_verified_at !== null) {
                $dispatcher->dispatch($model, NotificationType::PHONE_VERIFICATION_SUCCESS, [], 'user:'.$model->id.':phone-verified');
            }

            if ($model->wasChanged('status')) {
                $dispatcher->dispatch($model, NotificationType::ACCOUNT_STATUS_CHANGED, [
                    'status' => $model->status->value,
                ], 'user:'.$model->id.':status:'.$model->status->value);
            }
            return;
        }

        if ($model instanceof Order && $model->wasChanged('status')) {
            $type = match ($model->status->value) {
                'processing' => NotificationType::ORDER_PROCESSING,
                'completed' => NotificationType::ORDER_COMPLETED,
                'failed' => NotificationType::ORDER_FAILED,
                default => null,
            };
            if ($type) {
                $dispatcher->dispatch($model->user, $type, [
                    'order_id' => $model->id,
                    'status' => $model->status->value,
                    'amount' => (int) $model->total_amount,
                    'currency' => $model->currency,
                ], 'order:'.$model->id.':status:'.$model->status->value);
            }
            return;
        }

        if ($model instanceof Payment && $model->wasChanged('status')) {
            $status = $model->status->value;
            if ($status === 'success') {
                $type = $model->method->value === 'manual'
                    ? NotificationType::PAYMENT_MANUAL_APPROVED
                    : NotificationType::PAYMENT_SUCCESS;
                $dispatcher->dispatch($model->user, $type, [
                    'payment_id' => $model->id,
                    'order_id' => $model->order_id,
                    'amount' => (int) $model->amount,
                    'currency' => $model->currency,
                ], 'payment:'.$model->id.':status:success');
            } elseif ($status === 'failed') {
                $type = $model->method->value === 'manual'
                    ? NotificationType::PAYMENT_MANUAL_REJECTED
                    : NotificationType::PAYMENT_FAILED;
                $dispatcher->dispatch($model->user, $type, [
                    'payment_id' => $model->id,
                    'order_id' => $model->order_id,
                    'amount' => (int) $model->amount,
                    'currency' => $model->currency,
                ], 'payment:'.$model->id.':status:failed');
            }
            return;
        }

        if ($model instanceof Service && $model->wasChanged('status')) {
            $from = $model->getOriginal('status');
            $from = $from instanceof \BackedEnum ? $from->value : (string) $from;
            $to = $model->status->value;
            $type = null;
            if ($from === 'pending' && $to === 'active') $type = NotificationType::SERVICE_CREATED;
            if ($from === 'disabled' && $to === 'active') $type = NotificationType::SERVICE_REACTIVATED;
            if ($to === 'disabled') $type = NotificationType::SERVICE_DISABLED;
            if ($type) {
                $dispatcher->dispatch($model->user, $type, [
                    'service_id' => $model->id,
                    'service_uuid' => $model->uuid,
                    'expires_at' => $model->expires_at?->toDateTimeString() ?: '',
                    'status' => $to,
                ], 'service:'.$model->id.':status:'.$to);
            }
            return;
        }

        if ($model instanceof ServiceOperation && $model->wasChanged('status') && in_array($model->status, ['success', 'failed'], true)) {
            $service = $model->service()->with('user')->first();
            if (! $service) return;
            $type = null;
            if (in_array($model->operation, ['renew', 'extend'], true)) {
                $type = $model->status === 'success' ? NotificationType::SERVICE_RENEWAL_SUCCESS : NotificationType::SERVICE_RENEWAL_FAILED;
            } elseif ($model->operation === 'add_capacity' && $model->status === 'success') {
                $type = NotificationType::SERVICE_CAPACITY_INCREASED;
            }
            if ($type) {
                $dispatcher->dispatch($service->user, $type, [
                    'service_id' => $service->id,
                    'service_uuid' => $service->uuid,
                    'expires_at' => $service->expires_at?->toDateTimeString() ?: '',
                    'capacity' => (int) ($service->capacity ?? 0),
                    'error_message' => (string) ($model->error_message ?: ''),
                ], 'service-operation:'.$model->id.':'.$model->status);
            }
            return;
        }

        if ($model instanceof Referral && $model->wasChanged('status') && $model->status === 'qualified') {
            $model->loadMissing('referrer');
            $dispatcher->dispatch($model->referrer, NotificationType::REFERRAL_REWARD, [
                'referral_id' => $model->id,
                'referred_user_id' => $model->referred_user_id,
            ], 'referral:'.$model->id.':qualified');
            return;
        }

        if ($model instanceof SupportTicket && $model->wasChanged('status')) {
            $dispatcher->dispatch($model->user, NotificationType::SUPPORT_TICKET_STATUS_CHANGED, [
                'ticket_id' => $model->id,
                'status' => $model->status,
            ], 'support-ticket:'.$model->id.':status:'.$model->status);
        }
    }
}
