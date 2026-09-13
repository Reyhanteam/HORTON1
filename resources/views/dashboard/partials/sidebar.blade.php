<aside class="fixed inset-y-0 start-0 z-40 hidden w-72 flex-col border-e border-white/10 bg-ink p-5 lg:flex">
    <a href="{{ route('admin.dashboard') }}" class="mb-8 flex items-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand text-lg font-bold text-white">H</span>
        <span>
            <strong class="block text-lg text-white">HORTON</strong>
            <small class="text-xs text-slate-400">پنل مدیریت</small>
        </span>
    </a>

    <nav class="space-y-1" aria-label="منوی مدیریت">
        @php
            $items = [
                ['admin.dashboard', 'داشبورد', '🏠'],
                ['admin.users.index', 'کاربران', '👥'],
                ['admin.notifications.index', 'اعلان‌ها', '🔔'],
                ['admin.support.tickets.index', 'پشتیبانی', '💬'],
                ['admin.broadcasts.index', 'ارسال همگانی', '📣'],
            ];
        @endphp

        @foreach($items as [$route, $label, $icon])
            @if(Route::has($route))
                <a href="{{ route($route) }}" class="nav-item flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium {{ request()->routeIs($route) ? 'bg-white/10 text-white' : '' }}">
                    <span class="w-6 text-center" aria-hidden="true">{{ $icon }}</span>
                    <span>{{ $label }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4 text-xs text-slate-300">
        <div class="font-semibold text-white">HORTON Management</div>
        <p class="mt-1 leading-6">تمام تنظیمات و عملیات مدیریتی از همین داشبورد انجام می‌شود.</p>
    </div>
</aside>

<div class="border-b border-line bg-ink p-4 lg:hidden">
    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand font-bold text-white">H</span>
        <div>
            <strong class="block text-white">HORTON</strong>
            <small class="text-xs text-slate-400">پنل مدیریت</small>
        </div>
    </div>
    <nav class="mt-4 flex gap-2 overflow-x-auto" aria-label="منوی مدیریت موبایل">
        @foreach($items as [$route, $label, $icon])
            @if(Route::has($route))
                <a href="{{ route($route) }}" class="whitespace-nowrap rounded-xl border border-white/10 px-3 py-2 text-xs text-white {{ request()->routeIs($route) ? 'bg-white/10' : '' }}">
                    {{ $icon }} {{ $label }}
                </a>
            @endif
        @endforeach
    </nav>
</div>
