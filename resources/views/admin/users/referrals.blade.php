@extends('layouts.dashboard', ['title' => 'HORTON | Referral', 'heading' => 'Referral کاربر'])

@section('content')
<div class="mx-auto max-w-[1500px] space-y-5">
    <div class="flex items-center justify-between gap-4"><div><p class="text-sm text-ink-soft">کاربر: {{ $user->name }}</p><h1 class="mt-1 text-3xl font-black text-ink">Referral</h1></div><a href="{{ route('admin.users.show',$user) }}" class="rounded-xl border border-line bg-white px-4 py-3 text-sm font-bold">بازگشت</a></div>
    <section class="overflow-hidden rounded-2xl border border-line bg-white">
        <div class="overflow-x-auto"><table class="min-w-full text-right text-sm"><thead class="bg-slate-50 text-xs"><tr><th class="px-5 py-4">معرف</th><th class="px-5 py-4">معرفی‌شده</th><th class="px-5 py-4">ثبت</th><th class="px-5 py-4">تأیید</th></tr></thead><tbody class="divide-y divide-line">
        @forelse($referrals as $ref)<tr><td class="px-5 py-4">{{ $ref->referrer?->name ?? '—' }}</td><td class="px-5 py-4">{{ $ref->referred?->name ?? '—' }}</td><td class="px-5 py-4">{{ $ref->registered_at?->format('Y/m/d H:i') ?? '—' }}</td><td class="px-5 py-4">{{ $ref->qualified_at?->format('Y/m/d H:i') ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-14 text-center text-ink-soft">Referral ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div><div class="border-t border-line px-5 py-4">{{ $referrals->links() }}</div>
    </section>
</div>
@endsection
