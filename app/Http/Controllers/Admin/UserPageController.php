<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\CashbackAccount;
use App\Models\CashbackTransaction;
use App\Models\DiscountUsage;
use App\Models\GiftCodeRedemption;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserPageController
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('telegramAccounts')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->input('q'));
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhereHas('telegramAccounts', function ($telegram) use ($term): void {
                            $telegram->where('username', 'like', "%{$term}%")
                                ->orWhere('telegram_user_id', 'like', "%{$term}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 25), 1), 100))
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $data = [
            'user' => $user->load(['telegramAccounts', 'profile']),
            'wallets' => Wallet::query()->where('user_id', $user->id)->get(),
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'payments' => Payment::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'services' => Service::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'supportTickets' => SupportTicket::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'walletTransactions' => WalletTransaction::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'referrals' => Referral::query()->where(function ($query) use ($user): void {
                $query->where('referrer_user_id', $user->id)->orWhere('referred_user_id', $user->id);
            })->with(['referrer:id,name,email', 'referred:id,name,email'])->latest('id')->limit(10)->get(),
            'cashbackAccount' => CashbackAccount::query()->where('user_id', $user->id)->first(),
            'cashback' => CashbackTransaction::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'discounts' => DiscountUsage::query()->where('user_id', $user->id)->with('discountCode')->latest('id')->limit(10)->get(),
            'gifts' => GiftCodeRedemption::query()->where('user_id', $user->id)->with('giftCode')->latest('id')->limit(10)->get(),
            'notifications' => Notification::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'activity' => AuditLog::query()->where('subject_type', User::class)->where('subject_id', $user->id)->latest('id')->limit(20)->get(),
        ];

        return view('admin.users.show', $data);
    }
}
