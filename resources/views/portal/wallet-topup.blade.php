@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div><a href="{{ route('portal.wallet') }}" class="text-sm text-ink-soft">← کیف پول</a><h2 class="mt-3 text-2xl font-bold">شارژ کیف پول</h2><p class="mt-1 text-sm text-ink-soft">مبلغ موردنظر را انتخاب کنید و روش پرداخت را ادامه دهید.</p></div>
    <section class="glass-card p-6">
        <label class="mb-2 block text-sm font-semibold">مبلغ شارژ</label>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach([100000,250000,500000,1000000] as $amount)<button type="button" class="rounded-2xl border border-line p-4 text-sm hover:border-brand">{{ number_format($amount) }} ریال</button>@endforeach
        </div>
        <label class="mt-6 mb-2 block text-sm font-semibold">مبلغ دلخواه</label>
        <input type="number" min="0" class="w-full rounded-xl border border-line px-4 py-3" placeholder="مثلاً ۵۰۰۰۰۰ ریال">
        <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-ink-soft">روش‌های پرداخت فعال توسط مدیریت در این بخش نمایش داده خواهند شد.</div>
        <button type="button" class="mt-6 w-full rounded-xl bg-brand px-5 py-3 font-bold text-white">ادامه پرداخت</button>
    </section>
</div>
@endsection
