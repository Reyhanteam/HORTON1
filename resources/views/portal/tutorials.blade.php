@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div><a href="{{ route('portal.support') }}" class="text-sm text-ink-soft">← پشتیبانی</a><h2 class="mt-3 text-2xl font-bold">آموزش استفاده</h2><p class="mt-1 text-sm text-ink-soft">راهنمای کوتاه برای شروع کار با HORTON.</p></div>
    <div class="grid gap-5 md:grid-cols-2">
        @foreach([
            ['01','شروع کار','حساب خود را تکمیل کنید و بخش‌های اصلی پنل را بشناسید.'],
            ['02','خرید سرویس','از فروشگاه دسته‌بندی، محصول و پلن مناسب را انتخاب کنید.'],
            ['03','پرداخت','در تسویه، روش پرداخت موردنظر را انتخاب و وضعیت سفارش را پیگیری کنید.'],
            ['04','مدیریت سرویس','سرویس‌های فعال، تاریخ انقضا و عملیات قابل انجام را از بخش سرویس‌ها ببینید.'],
            ['05','کیف پول','گردش حساب و تراکنش‌های مالی را از کیف پول بررسی کنید.'],
            ['06','پشتیبانی','برای مشکلات یا درخواست‌ها یک تیکت ایجاد کنید و پاسخ تیم پشتیبانی را دنبال کنید.'],
        ] as [$number,$title,$text])
            <article class="glass-card p-6"><span class="text-xs font-bold text-brand">راهنمای {{ $number }}</span><h3 class="mt-3 text-lg font-bold">{{ $title }}</h3><p class="mt-2 text-sm leading-7 text-ink-soft">{{ $text }}</p></article>
        @endforeach
    </div>
</div>
@endsection
