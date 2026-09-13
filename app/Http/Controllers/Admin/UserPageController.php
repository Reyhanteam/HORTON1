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
use App\Models\TelegramAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserPageController
{
    public function index(Request $request): View
    {
        $users = User::query()->with('telegramAccounts')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->input('q'));
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhereHas('telegramAccounts', fn ($telegram) => $telegram->where('username', 'like', "%{$term}%")->orWhere('telegram_user_id', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')->paginate(min(max((int) $request->input('per_page', 25), 1), 100))->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->load(['telegramAccounts', 'profile']),
            'wallets' => Wallet::query()->where('user_id', $user->id)->get(),
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'payments' => Payment::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'services' => Service::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'supportTickets' => SupportTicket::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'walletTransactions' => WalletTransaction::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'referrals' => Referral::query()->where(fn($q)=>$q->where('referrer_user_id',$user->id)->orWhere('referred_user_id',$user->id))->with(['referrer:id,name,email','referred:id,name,email'])->latest('id')->limit(10)->get(),
            'cashbackAccount' => CashbackAccount::query()->where('user_id', $user->id)->first(),
            'cashback' => CashbackTransaction::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'discounts' => DiscountUsage::query()->where('user_id', $user->id)->with('discountCode')->latest('id')->limit(10)->get(),
            'gifts' => GiftCodeRedemption::query()->where('user_id', $user->id)->with('giftCode')->latest('id')->limit(10)->get(),
            'notifications' => Notification::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'activity' => AuditLog::query()->where('subject_type', User::class)->where('subject_id', $user->id)->latest('id')->limit(20)->get(),
        ]);
    }

    public function wallet(User $user): View { return $this->related($user, 'کیف پول', Wallet::query()->where('user_id',$user->id)->withCount('transactions')->get(), ['currency'=>'ارز','balance'=>'موجودی','status'=>'وضعیت','transactions_count'=>'تعداد تراکنش']); }
    public function walletTransactions(User $user): View { return $this->related($user, 'تراکنش‌های کیف پول', WalletTransaction::query()->where('user_id',$user->id)->latest('id')->paginate(50), ['id'=>'ID','direction'=>'جهت','type'=>'نوع','amount'=>'مبلغ','created_at'=>'تاریخ']); }
    public function orders(User $user): View { return $this->related($user, 'سفارش‌ها', Order::query()->where('user_id',$user->id)->latest('id')->paginate(25), ['id'=>'ID','status'=>'وضعیت','created_at'=>'تاریخ']); }
    public function payments(User $user): View { return $this->related($user, 'پرداخت‌ها', Payment::query()->where('user_id',$user->id)->latest('id')->paginate(25), ['id'=>'ID','status'=>'وضعیت','amount'=>'مبلغ','created_at'=>'تاریخ']); }
    public function services(User $user): View { return $this->related($user, 'سرویس‌ها', Service::query()->where('user_id',$user->id)->latest('id')->paginate(25), ['id'=>'ID','status'=>'وضعیت','created_at'=>'تاریخ']); }
    public function transactions(User $user): View { return $this->related($user, 'تراکنش‌ها', WalletTransaction::query()->where('user_id',$user->id)->latest('id')->paginate(50), ['id'=>'ID','direction'=>'جهت','type'=>'نوع','amount'=>'مبلغ','created_at'=>'تاریخ']); }
    public function referrals(User $user): View { return view('admin.users.referrals',['user'=>$user,'referrals'=>Referral::query()->where(fn($q)=>$q->where('referrer_user_id',$user->id)->orWhere('referred_user_id',$user->id))->with(['referrer:id,name,email','referred:id,name,email'])->latest('id')->paginate(25)]); }
    public function cashback(User $user): View { return $this->related($user, 'Cashback', CashbackTransaction::query()->where('user_id',$user->id)->latest('id')->paginate(50), ['id'=>'ID','direction'=>'جهت','amount'=>'مبلغ','created_at'=>'تاریخ']); }
    public function discounts(User $user): View { return $this->related($user, 'کدهای تخفیف استفاده‌شده', DiscountUsage::query()->where('user_id',$user->id)->with('discountCode')->latest('id')->paginate(25), ['id'=>'ID','discount_code_id'=>'Discount ID','amount'=>'مبلغ تخفیف','created_at'=>'تاریخ']); }
    public function gifts(User $user): View { return $this->related($user, 'Gift Codeهای مصرف‌شده', GiftCodeRedemption::query()->where('user_id',$user->id)->with('giftCode')->latest('id')->paginate(25), ['id'=>'ID','gift_code_id'=>'Gift ID','value'=>'ارزش','created_at'=>'تاریخ']); }
    public function support(User $user): View { return $this->related($user, 'تیکت‌های پشتیبانی', SupportTicket::query()->where('user_id',$user->id)->latest('id')->paginate(25), ['id'=>'ID','status'=>'وضعیت','created_at'=>'تاریخ']); }
    public function notifications(User $user): View { return $this->related($user, 'اعلان‌های کاربر', Notification::query()->where('user_id',$user->id)->latest('id')->paginate(25), ['id'=>'ID','read_at'=>'خوانده‌شده','created_at'=>'تاریخ']); }
    public function activity(User $user): View { return $this->related($user, 'Activity Log', AuditLog::query()->where('subject_type',User::class)->where('subject_id',$user->id)->latest('id')->paginate(50), ['id'=>'ID','action'=>'عملیات','ip_address'=>'IP','created_at'=>'تاریخ']); }
    public function telegram(User $user): View { return $this->related($user, 'حساب‌های Telegram', TelegramAccount::query()->where('user_id',$user->id)->latest('id')->paginate(25), ['telegram_user_id'=>'Telegram ID','username'=>'Username','created_at'=>'تاریخ اتصال']); }

    private function related(User $user, string $title, $items, array $columns): View { return view('admin.users.related', compact('user','title','items','columns')); }
}
