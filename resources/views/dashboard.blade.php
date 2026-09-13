@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <section class="relative overflow-hidden rounded-[2rem] bg-ink p-6 text-white shadow-2xl shadow-ink/10 lg:p-9">
            <div class="absolute -left-20 -top-24 h-64 w-64 rounded-full bg-brand/30 blur-3xl"></div>
            <div class="absolute -bottom-32 right-1/3 h-72 w-72 rounded-full bg-blue-400/10 blur-3xl"></div>
            <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="text-sm font-medium text-blue-200">پنل مدیریت HORTON</p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight lg:text-3xl">سلام، {{ auth()->user()->name ?? 'مدیر' }} 👋</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">مرکز کنترل فروشگاه، کاربران، سرویس‌ها، پرداخت‌ها، بازاریابی و تنظیمات ربات تلگرام.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-sm text-slate-200">
                    <span class="block text-xs text-slate-400">وضعیت پنل</span>
                    <span class="mt-2 flex items-center gap-2 font-semibold"><i class="h-2.5 w-2.5 rounded-full bg-emerald-400"></i> آماده به کار</span>
                </div>
            </div>
        </section>

        <section class="stagger grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['کاربران', 'مدیریت کاربران ربات', 'admin.users.index'], ['سفارش‌ها', 'فروش و خریدها', 'admin.orders.index'], ['سرویس‌ها', 'وضعیت سرویس‌ها', 'admin.services.index'], ['تراکنش‌ها', 'پرداخت و کیف پول', 'admin.payments.index']] as [$title, $description, $route])
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ $title }}</p>
                            <p class="mt-2 text-xs leading-6 text-ink-soft">{{ $description }}</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-50 text-xs font-bold text-brand">HORTON</span>
                    </div>
                    @if (\Illuminate\Support\Facades\Route::has($route))
                        <a href="{{ route($route) }}" class="mt-5 inline-flex items-center text-xs font-semibold text-brand hover:text-brand-dark">مشاهده →</a>
                    @else
                        <span class="mt-5 inline-flex text-xs text-slate-400">ماژول در حال توسعه</span>
                    @endif
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
            <div class="glass-card rounded-3xl p-6 lg:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-ink">مرکز عملیات</h3>
                        <p class="mt-1 text-xs text-ink-soft">دسترسی سریع به بخش‌های اصلی پنل</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-brand">Admin only</span>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @php
                        $quickLinks = [
                            ['کاربران و احراز هویت', 'admin.users.index'],
                            ['محصولات و پلن‌ها', 'admin.products.index'],
                            ['سفارش‌ها و فاکتورها', 'admin.orders.index'],
                            ['سرویس‌ها و Providerها', 'admin.services.index'],
                            ['پرداخت‌ها و Wallet', 'admin.payments.index'],
                            ['Discount / Gift / Referral', 'admin.marketing.index'],
                            ['پشتیبانی و تیکت‌ها', 'admin.support.index'],
                            ['تنظیمات Bot', 'admin.settings.index'],
                        ];
                    @endphp

                    @foreach ($quickLinks as [$label, $route])
                        @if (\Illuminate\Support\Facades\Route::has($route))
                            <a href="{{ route($route) }}" class="group rounded-2xl border border-line bg-white/70 p-4 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-900/5">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-ink">{{ $label }}</span>
                                    <span class="text-brand transition-transform group-hover:-translate-x-1">←</span>
                                </span>
                            </a>
                        @else
                            <div class="rounded-2xl border border-line/70 bg-slate-50/70 p-4">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-slate-500">{{ $label }}</span>
                                    <span class="rounded-full bg-white px-2 py-1 text-[10px] text-slate-400">به‌زودی</span>
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-blue-100 bg-blue-50/70 p-6 lg:p-7">
                <p class="text-xs font-semibold text-brand">معماری HORTON</p>
                <h3 class="mt-2 text-xl font-bold text-ink">مدیریت جدا از ربات</h3>
                <p class="mt-3 text-sm leading-7 text-ink-soft">ربات فقط برای کاربران نهایی است. تمام عملیات مدیریتی، تنظیمات، گزارش‌ها و کنترل سیستم از همین داشبورد انجام می‌شود.</p>
                <div class="mt-6 space-y-3 text-xs text-ink-soft">
                    <div class="flex items-center gap-3 rounded-xl bg-white/70 p-3"><span class="h-2 w-2 rounded-full bg-brand"></span> Telegram infrastructure → Router Package</div>
                    <div class="flex items-center gap-3 rounded-xl bg-white/70 p-3"><span class="h-2 w-2 rounded-full bg-brand"></span> Business logic → Application Services</div>
                    <div class="flex items-center gap-3 rounded-xl bg-white/70 p-3"><span class="h-2 w-2 rounded-full bg-brand"></span> Administration → Web Dashboard</div>
                </div>
            </div>
        </section>
    </div>
@endsection
