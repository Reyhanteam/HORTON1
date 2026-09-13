@extends('layouts.dashboard', ['title' => 'HORTON | کاربران', 'heading' => 'مدیریت کاربران'])

@section('content')
<div class="mx-auto max-w-[1500px] space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm text-ink-soft">مدیریت کامل کاربران HORTON</p>
            <h1 class="mt-1 text-3xl font-black text-ink">کاربران</h1>
        </div>
        <div class="rounded-2xl border border-line bg-white px-4 py-3 text-sm text-ink-soft">{{ $users->total() }} کاربر</div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="glass-card grid gap-3 rounded-2xl p-4 md:grid-cols-[1fr_180px_120px]">
        <input name="q" value="{{ request('q') }}" placeholder="نام، ایمیل، username یا Telegram ID..." class="rounded-xl border border-line bg-white px-4 py-3 text-sm outline-none focus:border-brand">
        <select name="status" class="rounded-xl border border-line bg-white px-4 py-3 text-sm outline-none focus:border-brand">
            <option value="">همه وضعیت‌ها</option>
            <option value="active" @selected(request('status') === 'active')>فعال</option>
            <option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option>
            <option value="blocked" @selected(request('status') === 'blocked')>مسدود</option>
        </select>
        <button class="rounded-xl bg-ink px-4 py-3 text-sm font-bold text-white hover:opacity-90">جستجو</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-line bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-right text-sm">
                <thead class="bg-slate-50 text-xs text-ink-soft">
                    <tr>
                        <th class="px-5 py-4 font-bold">کاربر</th>
                        <th class="px-5 py-4 font-bold">Telegram</th>
                        <th class="px-5 py-4 font-bold">وضعیت</th>
                        <th class="px-5 py-4 font-bold">عضویت</th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                @forelse($users as $user)
                    @php($telegram = $user->telegramAccounts->first())
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-5 py-4">
                            <a href="{{ route('admin.users.show', $user) }}" class="font-bold text-ink hover:text-brand">{{ $user->name }}</a>
                            <div class="mt-1 text-xs text-ink-soft">{{ $user->email }}</div>
                        </td>
                        <td class="px-5 py-4">
                            @if($telegram)
                                <div class="font-medium">{{ $telegram->username ? '@'.$telegram->username : 'بدون username' }}</div>
                                <div class="mt-1 text-xs text-ink-soft">{{ $telegram->telegram_user_id }}</div>
                            @else
                                <span class="text-xs text-ink-soft">متصل نیست</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($user->status === 'blocked' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ $user->status }}</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-ink-soft">{{ $user->created_at?->format('Y/m/d H:i') }}</td>
                        <td class="px-5 py-4 text-left"><a href="{{ route('admin.users.show', $user) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold hover:bg-slate-200">مشاهده</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-ink-soft">کاربری پیدا نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $users->links() }}</div>
        @endif
    </section>
</div>
@endsection
