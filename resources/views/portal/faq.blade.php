@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><a href="{{ route('portal.support') }}" class="text-sm text-ink-soft">← پشتیبانی</a><h2 class="mt-3 text-2xl font-bold">سؤالات متداول</h2><p class="mt-1 text-sm text-ink-soft">پاسخ پرسش‌های رایج درباره حساب، خرید و سرویس‌ها.</p></div>
    <div class="space-y-3">
        @foreach([
            ['چطور سرویس خریداری کنم؟','از فروشگاه محصول و پلن موردنظر را انتخاب کنید و وارد مرحله تسویه شوید.'],
            ['وضعیت سفارش را از کجا ببینم؟','در بخش سفارش‌های من می‌توانید وضعیت سفارش، پرداخت و سرویس‌های مرتبط را مشاهده کنید.'],
            ['کیف پول چه کاربردی دارد؟','موجودی کیف پول برای پرداخت سفارش‌ها و مشاهده گردش مالی حساب استفاده می‌شود.'],
            ['اگر پرداخت ناموفق شد چه کنم؟','وضعیت پرداخت را در جزئیات سفارش بررسی کنید. اگر مبلغ کسر شده اما سفارش تکمیل نشده است، از پشتیبانی درخواست بررسی بدهید.'],
            ['چطور سرویس را تمدید کنم؟','در جزئیات سرویس، گزینه‌های قابل‌دسترس برای تمدید یا افزایش ظرفیت نمایش داده می‌شوند.'],
            ['چطور با پشتیبانی ارتباط بگیرم؟','از صفحه پشتیبانی یک تیکت جدید ایجاد کنید و پاسخ‌ها را همان‌جا پیگیری کنید.'],
        ] as [$question,$answer])
            <details class="glass-card group p-5"><summary class="cursor-pointer list-none font-semibold">{{ $question }} <span class="float-left text-ink-soft">＋</span></summary><p class="mt-4 border-t border-line pt-4 text-sm leading-7 text-ink-soft">{{ $answer }}</p></details>
        @endforeach
    </div>
</div>
@endsection
