@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><a href="{{ route('portal.wallet') }}" class="text-sm text-ink-soft">← کیف پول</a><h2 class="mt-3 text-2xl font-bold">تراکنش‌های مالی</h2><p class="mt-1 text-sm text-ink-soft">تمام گردش‌های ثبت‌شده کیف پول شما.</p></div>
        <div class="glass-card px-5 py-4"><span class="text-xs text-ink-soft">موجودی</span><div class="mt-1 text-xl font-bold">{{ number_format((float) ($wallet->balance ?? 0)) }} <span class="text-xs font-normal">ریال</span></div></div>
    </div>
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-right text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-4">شرح</th><th class="px-5 py-4">نوع</th><th class="px-5 py-4">مبلغ</th><th class="px-5 py-4">وضعیت</th><th class="px-5 py-4">تاریخ</th></tr></thead><tbody class="divide-y divide-line">@forelse($transactions as $transaction)<tr><td class="px-5 py-4 font-medium">{{ $transaction->description ?? 'تراکنش' }}</td><td class="px-5 py-4">{{ $transaction->direction ?? $transaction->type ?? '—' }}</td><td class="px-5 py-4">{{ number_format((float) ($transaction->amount ?? 0)) }}</td><td class="px-5 py-4">{{ $transaction->status ?? 'ثبت‌شده' }}</td><td class="px-5 py-4 text-ink-soft">{{ optional($transaction->created_at)->format('Y/m/d H:i') }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-16 text-center text-ink-soft">هنوز تراکنشی ثبت نشده است.</td></tr>@endforelse</tbody></table></div>
        @if($transactions->hasPages())<div class="border-t border-line p-5">{{ $transactions->links() }}</div>@endif
    </div>
</div>
@endsection
