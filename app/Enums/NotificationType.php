<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case REGISTRATION_SUCCESS = 'account.registration_success';
    case PHONE_VERIFICATION_SUCCESS = 'account.phone_verification_success';
    case ACCOUNT_STATUS_CHANGED = 'account.status_changed';

    case PAYMENT_SUCCESS = 'payment.success';
    case PAYMENT_FAILED = 'payment.failed';
    case PAYMENT_PENDING = 'payment.pending';
    case PAYMENT_MANUAL_APPROVED = 'payment.manual_approved';
    case PAYMENT_MANUAL_REJECTED = 'payment.manual_rejected';

    case WALLET_CREDITED = 'wallet.credited';
    case WALLET_DEBITED = 'wallet.debited';

    case ORDER_CREATED = 'order.created';
    case ORDER_PROCESSING = 'order.processing';
    case ORDER_COMPLETED = 'order.completed';
    case ORDER_FAILED = 'order.failed';

    case SERVICE_EXPIRING_3_DAYS = 'service.expiring_3_days';
    case SERVICE_EXPIRING_24_HOURS = 'service.expiring_24_hours';
    case SERVICE_EXPIRED = 'service.expired';
    case SERVICE_RENEWAL_SUCCESS = 'service.renewal_success';
    case SERVICE_RENEWAL_FAILED = 'service.renewal_failed';
    case SERVICE_CREATED = 'service.created';
    case SERVICE_PROVISIONING = 'service.provisioning';
    case SERVICE_CAPACITY_INCREASED = 'service.capacity_increased';
    case SERVICE_DISABLED = 'service.disabled';
    case SERVICE_REACTIVATED = 'service.reactivated';

    case DISCOUNT_APPLIED = 'marketing.discount_applied';
    case GIFT_REDEEMED = 'marketing.gift_redeemed';
    case REFERRAL_REWARD = 'marketing.referral_reward';
    case CASHBACK_CREDITED = 'marketing.cashback_credited';

    case SUPPORT_TICKET_REPLIED = 'support.ticket_replied';
    case SUPPORT_TICKET_STATUS_CHANGED = 'support.ticket_status_changed';
}
