@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><h2 class="text-2xl font-bold">تخفیف‌ها و کدها</h2><p class="mt-1 text-sm text-ink-soft">کدهای تخفیف و پیشنهادهای فعال خود را اینجا دنبال کنید.</p></div>
    <div class="glass-card p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><span class="text-xs font-semibold text-brand">کد تخفیف</span><h3 class="mt-2 text-lg font-bold">کد دارید؟ در خرید وارد کنید</h3><p class="mt-1 text-sm text-ink-soft">کد معتبر هنگام تسویه روی سفارش اعمال می‌شود.</p></div><a href="{{ route('portal.shop') }}" class="rounded-xl bg-ink px-5 py-3 text-center text-sm font-semibold text-white">رفتن به فروشگاه</a></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="glass-card p-5"><div class="text-sm font-bold">کدهای فعال</div><p class="mt-3 text-sm text-ink-soft">اگر کمپینی برای حساب شما فعال باشد، کد و شرایط آن در این بخش نمایش داده می‌شود.</p></div>
        <div class="glass-card p-5"><div class="text-sm font-bold">کارت هدیه</div><p class="mt-3 text-sm text-ink-soft">کد هدیه خود را هنگام خرید وارد کنید تا در صورت معتبر بودن، قابل استفاده باشد.</p></div>
    </div>
</div>
@endsection
