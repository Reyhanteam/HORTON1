@extends('layouts.dashboard', ['title' => 'HORTON | داشبورد مدیریت', 'heading' => 'داشبورد مدیریت'])

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6">
        <section class="rounded-[2rem] bg-ink p-8 text-white shadow-sm">
            <p class="text-sm text-blue-200">HORTON Management</p>
            <h2 class="mt-3 text-3xl font-bold">مرکز مدیریت HORTON</h2>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                تمام عملیات مدیریتی HORTON از همین داشبورد انجام می‌شود؛ بدون داشبورد دوم و بدون مسیر /admin/dashboard.
            </p>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.users.index') }}" class="glass-card rounded-2xl p-5 transition hover:-translate-y-0.5">
                <strong>کاربران</strong>
                <p class="mt-2 text-xs text-ink-soft">مدیریت کاربران و جزئیات حساب‌ها</p>
            </a>
            <a href="{{ route('admin.wallet.show', ['user' => auth()->id()]) }}" class="glass-card rounded-2xl p-5 transition hover:-translate-y-0.5">
                <strong>کیف پول</strong>
                <p class="mt-2 text-xs text-ink-soft">دسترسی به عملیات مالی کاربران</p>
            </a>
            <a href="{{ route('admin.support.tickets.index') }}" class="glass-card rounded-2xl p-5 transition hover:-translate-y-0.5">
                <strong>پشتیبانی</strong>
                <p class="mt-2 text-xs text-ink-soft">مدیریت تیکت‌های کاربران</p>
            </a>
            <a href="{{ route('admin.notifications.index') }}" class="glass-card rounded-2xl p-5 transition hover:-translate-y-0.5">
                <strong>اعلان‌ها</strong>
                <p class="mt-2 text-xs text-ink-soft">مرکز اطلاع‌رسانی سیستم</p>
            </a>
        </div>

        <section class="glass-card rounded-2xl p-6">
            <h3 class="text-base font-bold text-ink">ساختار داشبورد</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-line bg-white p-4 text-sm">هدر ثابت</div>
                <div class="rounded-xl border border-line bg-white p-4 text-sm">سایدبار ثابت</div>
                <div class="rounded-xl border border-line bg-white p-4 text-sm">فوتر مشترک</div>
            </div>
        </section>
    </div>
@endsection
