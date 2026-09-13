<header class="sticky top-0 z-30 border-b border-line/80 bg-white/90 px-5 backdrop-blur-xl lg:px-10">
    <div class="flex h-20 items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs text-ink-soft">پنل مدیریت HORTON</p>
            <h1 class="mt-1 truncate text-lg font-bold text-ink">{{ $heading ?? 'داشبورد مدیریت' }}</h1>
        </div>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            @if(Route::has('admin.notifications.index'))
                <a href="{{ route('admin.notifications.index') }}" class="rounded-xl border border-line bg-white px-3 py-2 text-xs font-medium text-ink">
                    🔔 <span class="hidden sm:inline">اعلان‌ها</span>
                </a>
            @endif
            <div class="hidden sm:block">
                <span class="block text-sm font-semibold text-ink">{{ auth()->user()->name ?? 'مدیر' }}</span>
                <span class="block text-[11px] text-ink-soft">مدیریت HORTON</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-line bg-white px-3 py-2 text-xs font-medium text-ink">خروج</button>
            </form>
        </div>
    </div>
</header>
