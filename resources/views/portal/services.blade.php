@extends('layouts.portal',['title'=>'سرویس‌های من','heading'=>'سرویس‌های من'])
@section('content')
<div class="mx-auto max-w-6xl space-y-6">
<h2 class="text-2xl font-bold text-ink">سرویس‌های من</h2>
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
@forelse($services as $service)
<a href="{{ route('portal.services.show',$service) }}" class="glass-card rounded-3xl p-6"><h3 class="font-bold text-ink">{{ $service->plan?->product?->name ?? 'سرویس' }}</h3><p class="mt-2 text-sm text-ink-soft">{{ $service->plan?->name }}</p><p class="mt-5 text-xs text-ink-soft">{{ $service->expires_at?->format('Y/m/d') ?? '—' }}</p></a>
@empty
<div class="col-span-full rounded-3xl border border-dashed border-line bg-white p-12 text-center text-sm text-ink-soft">سرویسی برای نمایش وجود ندارد.</div>
@endforelse
</div><div>{{ $services->links() }}</div>
</div>
@endsection