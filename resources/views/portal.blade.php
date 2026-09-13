@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-[1400px] space-y-6">
<section class="relative overflow-hidden rounded-[2rem] bg-ink p-7 text-white"><div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-brand/30 blur-3xl"></div><div class="relative"><p class="text-sm text-blue-200">HORTON</p><h2 class="mt-3 text-3xl font-bold">سلام، {{ auth()->user()->name }} 👋</h2><p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">پنل کاربری برای مدیریت سرویس‌ها، خریدها و ارتباط با پشتیبانی.</p></div></section>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><a href="{{ route('portal.shop') }}" class="glass-card rounded-2xl p-5"><p class="text-sm text-ink-soft">فروشگاه</p><p class="mt-2 text-xl font-bold text-ink">مشاهده محصولات</p></a><a href="{{ route('portal.orders') }}" class="glass-card rounded-2xl p-5"><p class="text-sm text-ink-soft">سفارش‌ها</p><p class="mt-2 text-xl font-bold text-ink">خریدهای من</p></a><a href="{{ route('portal.services') }}" class="glass-card rounded-2xl p-5"><p class="text-sm text-ink-soft">سرویس‌ها</p><p class="mt-2 text-xl font-bold text-ink">سرویس‌های من</p></a><a href="{{ route('portal.support') }}" class="glass-card rounded-2xl p-5"><p class="text-sm text-ink-soft">پشتیبانی</p><p class="mt-2 text-xl font-bold text-ink">تیکت‌ها</p></a></section>
</div>
@endsection
