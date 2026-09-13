@extends('layouts.dashboard', ['title' => 'HORTON | '.$user->name, 'heading' => 'جزئیات کاربر'])

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <img src="{{ $user->profile_photo_url }}" alt="" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-line">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-3xl font-black text-ink">{{ $user->name }}</h1>
                    <span id="statusBadge" class="rounded-full px-3 py-1 text-xs font-bold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($user->status === 'blocked' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ $user->status }}</span>
                </div>
                <p class="mt-1 text-sm text-ink-soft">{{ $user->email }} · عضو از {{ $user->created_at?->format('Y/m/d H:i') }}</p>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-line bg-white px-4 py-3 text-sm font-bold hover:bg-slate-50">بازگشت به کاربران</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="glass-card rounded-2xl p-5"><div class="text-xs text-ink-soft">سفارش‌ها</div><div class="mt-2 text-3xl font-black">{{ $orders->count() }}</div></div>
        <div class="glass-card rounded-2xl p-5"><div class="text-xs text-ink-soft">پرداخت‌ها</div><div class="mt-2 text-3xl font-black">{{ $payments->count() }}</div></div>
        <div class="glass-card rounded-2xl p-5"><div class="text-xs text-ink-soft">سرویس‌ها</div><div class="mt-2 text-3xl font-black">{{ $services->count() }}</div></div>
        <div class="glass-card rounded-2xl p-5"><div class="text-xs text-ink-soft">تیکت‌ها</div><div class="mt-2 text-3xl font-black">{{ $supportTickets->count() }}</div></div>
    </div>

    <section class="glass-card rounded-2xl p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-black">اطلاعات و وضعیت حساب</h2>
            <div class="flex flex-wrap gap-2">
                <button data-status="active" class="status-btn rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white">فعال</button>
                <button data-status="inactive" class="status-btn rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white">غیرفعال</button>
                <button data-status="blocked" class="status-btn rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white">مسدود</button>
            </div>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div><div class="text-xs text-ink-soft">نام</div><div class="mt-1 font-bold">{{ $user->name }}</div></div>
            <div><div class="text-xs text-ink-soft">ایمیل</div><div class="mt-1 font-bold">{{ $user->email }}</div></div>
            <div><div class="text-xs text-ink-soft">تأیید ایمیل</div><div class="mt-1 font-bold">{{ $user->email_verified_at ? 'تأیید شده' : 'تأیید نشده' }}</div></div>
            <div><div class="text-xs text-ink-soft">تعداد حساب Telegram</div><div class="mt-1 font-bold">{{ $user->telegramAccounts->count() }}</div></div>
        </div>
    </section>

    <section class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between"><h2 class="text-lg font-black">ویرایش کاربر</h2><span id="editMessage" class="text-xs"></span></div>
        <form id="editUserForm" class="mt-5 grid gap-4 md:grid-cols-2">
            <input name="name" value="{{ $user->name }}" required class="rounded-xl border border-line bg-white px-4 py-3 text-sm" placeholder="نام">
            <input name="email" value="{{ $user->email }}" type="email" required class="rounded-xl border border-line bg-white px-4 py-3 text-sm" placeholder="ایمیل">
            <button class="md:col-span-2 rounded-xl bg-ink px-5 py-3 text-sm font-bold text-white hover:opacity-90">ذخیره تغییرات</button>
        </form>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="glass-card rounded-2xl p-6">
            <div class="flex items-center justify-between"><h2 class="text-lg font-black">Telegram</h2><a href="{{ route('admin.users.telegram', $user) }}" class="text-xs font-bold text-brand">مشاهده کامل</a></div>
            <div class="mt-4 space-y-3">
                @forelse($user->telegramAccounts as $account)
                    <div class="rounded-xl border border-line bg-white p-4"><b>{{ $account->username ? '@'.$account->username : 'بدون username' }}</b><div class="mt-1 text-xs text-ink-soft">{{ $account->telegram_user_id }}</div></div>
                @empty <p class="text-sm text-ink-soft">حساب Telegram ثبت نشده است.</p> @endforelse
            </div>
        </section>

        <section class="glass-card rounded-2xl p-6">
            <h2 class="text-lg font-black">کیف پول</h2>
            <div class="mt-4 space-y-3">
                @forelse($wallets as $wallet)
                    <div class="rounded-xl border border-line bg-white p-4"><div class="flex justify-between"><b>{{ $wallet->currency }}</b><strong>{{ number_format((int) $wallet->balance) }}</strong></div><div class="mt-1 text-xs text-ink-soft">وضعیت: {{ $wallet->status }}</div></div>
                @empty <p class="text-sm text-ink-soft">کیف پولی ثبت نشده است.</p> @endforelse
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <form class="wallet-form space-y-2" data-url="{{ route('admin.wallet.credit', $user) }}"><input name="amount" type="number" min="1" required placeholder="مبلغ افزایش" class="w-full rounded-xl border border-line px-3 py-2 text-sm"><input name="description" required placeholder="دلیل" class="w-full rounded-xl border border-line px-3 py-2 text-sm"><button class="w-full rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white">افزایش موجودی</button></form>
                <form class="wallet-form space-y-2" data-url="{{ route('admin.wallet.debit', $user) }}"><input name="amount" type="number" min="1" required placeholder="مبلغ کاهش" class="w-full rounded-xl border border-line px-3 py-2 text-sm"><input name="description" required placeholder="دلیل" class="w-full rounded-xl border border-line px-3 py-2 text-sm"><button class="w-full rounded-xl bg-rose-600 px-3 py-2 text-xs font-bold text-white">کاهش موجودی</button></form>
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @php($sections = [
            ['سفارش‌ها', $orders, 'admin.users.orders', ['id','status','created_at']],
            ['پرداخت‌ها', $payments, 'admin.users.payments', ['id','status','amount']],
            ['سرویس‌ها', $services, 'admin.users.services', ['id','status','created_at']],
            ['تراکنش‌ها', $walletTransactions, 'admin.users.transactions', ['id','direction','type','amount']],
            ['تیکت‌های پشتیبانی', $supportTickets, 'admin.users.support', ['id','status','created_at']],
            ['اعلان‌ها', $notifications, 'admin.users.notifications', ['id','read_at','created_at']],
            ['Referral', $referrals, 'admin.users.referrals', ['id','referrer_user_id','referred_user_id']],
            ['Cashback', $cashback, 'admin.users.cashback', ['id','direction','amount']],
            ['Discount', $discounts, 'admin.users.discounts', ['id','discount_code_id','amount']],
            ['Gift Code', $gifts, 'admin.users.gifts', ['id','gift_code_id','value']],
            ['Activity Log', $activity, 'admin.users.activity', ['id','action','ip_address','created_at']],
        ])
        @foreach($sections as [$label, $items, $route, $fields])
            <section class="glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between"><h2 class="text-lg font-black">{{ $label }}</h2><a href="{{ route($route, $user) }}" class="text-xs font-bold text-brand">مشاهده کامل</a></div>
                <div class="mt-4 space-y-2">
                    @forelse($items as $item)
                        <div class="overflow-hidden rounded-xl border border-line bg-white p-3 text-xs">
                            <div class="grid gap-2 md:grid-cols-{{ min(count($fields), 4) }}">
                                @foreach($fields as $field)
                                    <div><span class="text-ink-soft">{{ $field }}</span><div class="mt-1 truncate font-bold">{{ data_get($item, $field) instanceof \Carbon\CarbonInterface ? data_get($item, $field)->format('Y/m/d H:i') : (is_scalar(data_get($item, $field)) ? data_get($item, $field) : '—') }}</div></div>
                                @endforeach
                            </div>
                        </div>
                    @empty <p class="text-sm text-ink-soft">موردی ثبت نشده است.</p> @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <section class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between"><h2 class="text-lg font-black">یادداشت داخلی ادمین</h2><span id="noteMessage" class="text-xs"></span></div>
        <form id="noteForm" class="mt-4 flex flex-col gap-3 md:flex-row"><textarea name="note" required maxlength="5000" rows="3" class="flex-1 rounded-xl border border-line px-4 py-3 text-sm" placeholder="یادداشت داخلی که در Activity Log ثبت می‌شود..."></textarea><button class="rounded-xl bg-ink px-5 py-3 text-sm font-bold text-white">ثبت یادداشت</button></form>
    </section>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const jsonRequest = async (url, method, data) => {
    const response = await fetch(url, { method, headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf}, body: data ? JSON.stringify(data) : undefined });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'خطا در انجام عملیات');
    return payload;
};

document.querySelectorAll('.status-btn').forEach(button => button.addEventListener('click', async () => {
    try { await jsonRequest('{{ route('admin.users.status', $user) }}', 'PATCH', {status: button.dataset.status}); location.reload(); }
    catch (e) { alert(e.message); }
}));

document.getElementById('editUserForm').addEventListener('submit', async e => {
    e.preventDefault(); const data = Object.fromEntries(new FormData(e.currentTarget));
    try { await jsonRequest('{{ route('admin.users.update', $user) }}', 'PUT', data); document.getElementById('editMessage').textContent = 'ذخیره شد'; document.getElementById('editMessage').className = 'text-xs text-emerald-600'; }
    catch (e) { document.getElementById('editMessage').textContent = e.message; document.getElementById('editMessage').className = 'text-xs text-rose-600'; }
});

document.querySelectorAll('.wallet-form').forEach(form => form.addEventListener('submit', async e => {
    e.preventDefault(); const data = Object.fromEntries(new FormData(form));
    try { await jsonRequest(form.dataset.url, 'POST', data); location.reload(); } catch (e) { alert(e.message); }
}));

document.getElementById('noteForm').addEventListener('submit', async e => {
    e.preventDefault(); const data = Object.fromEntries(new FormData(e.currentTarget));
    try { await jsonRequest('{{ route('admin.users.notes.store', $user) }}', 'POST', data); e.currentTarget.reset(); document.getElementById('noteMessage').textContent = 'یادداشت ثبت شد'; document.getElementById('noteMessage').className = 'text-xs text-emerald-600'; }
    catch (e) { document.getElementById('noteMessage').textContent = e.message; document.getElementById('noteMessage').className = 'text-xs text-rose-600'; }
});
</script>
@endsection
