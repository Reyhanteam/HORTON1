@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div><a href="{{ route('portal.support') }}" class="text-sm text-ink-soft">← پشتیبانی</a><h2 class="mt-3 text-2xl font-bold">ثبت تیکت جدید</h2><p class="mt-1 text-sm text-ink-soft">موضوع و شرح درخواست خود را با جزئیات بنویسید.</p></div>
    <section class="glass-card p-6 space-y-5">
        <div><label class="mb-2 block text-sm font-semibold">موضوع</label><input class="w-full rounded-xl border border-line px-4 py-3 text-sm" placeholder="موضوع درخواست"></div>
        <div><label class="mb-2 block text-sm font-semibold">دسته‌بندی</label><select class="w-full rounded-xl border border-line bg-white px-4 py-3 text-sm"><option>مشکل سفارش</option><option>مشکل پرداخت</option><option>مشکل سرویس</option><option>سؤال عمومی</option><option>سایر</option></select></div>
        <div><label class="mb-2 block text-sm font-semibold">پیام</label><textarea rows="7" class="w-full rounded-xl border border-line px-4 py-3 text-sm" placeholder="شرح درخواست..."></textarea></div>
        <div class="rounded-2xl border border-dashed border-line p-5 text-center text-sm text-ink-soft">پیوست فایل — در مرحله اتصال فرم به Action فعال می‌شود.</div>
        <button type="button" class="w-full rounded-xl bg-brand px-5 py-3 font-bold text-white">ثبت درخواست</button>
    </section>
</div>
@endsection
