<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'HORTON') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="app-shell min-h-screen antialiased">
    <div data-sidebar-backdrop onclick="hortonAdmin.closeSidebar()" class="fixed inset-0 z-30 hidden bg-ink/30 lg:hidden"></div>

    <aside data-sidebar class="fixed inset-y-0 start-0 z-40 flex w-72 translate-x-full flex-col border-e border-white/10 bg-ink p-5 shadow-2xl transition-transform duration-300 lg:translate-x-0">
        <div class="mb-8 flex items-center justify-between px-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand text-lg font-bold text-white shadow-lg shadow-brand/30">H</span>
                <span>
                    <strong class="block text-lg text-white">HORTON</strong>
                    <small class="text-xs text-slate-400">مدیریت ربات تلگرام</small>
                </span>
            </a>
            <button onclick="hortonAdmin.closeSidebar()" class="rounded-xl p-2 text-slate-400 hover:bg-white/10 lg:hidden" aria-label="بستن منو">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <p class="mb-3 px-3 text-[11px] font-semibold tracking-wider text-slate-500">مدیریت سیستم</p>
        <nav class="space-y-1 overflow-y-auto pe-1">
            @php
                $items = [
                    ['dashboard', 'داشبورد', 'خانه'],
                    ['admin.users.index', 'کاربران', 'کار'],
                    ['admin.categories.index', 'دسته‌بندی‌ها', 'دسته'],
                    ['admin.products.index', 'محصولات و پلن‌ها', 'فروش'],
                    ['admin.orders.index', 'سفارش‌ها', 'سفارش'],
                    ['admin.payments.index', 'پرداخت‌ها و تراکنش‌ها', 'پرداخت'],
                    ['admin.wallets.index', 'کیف پول', 'کیف'],
                    ['admin.services.index', 'سرویس‌ها و ارائه‌دهندگان', 'سرویس'],
                    ['admin.marketing.index', 'بازاریابی و کدها', 'رشد'],
                    ['admin.support.index', 'پشتیبانی', 'تیکت'],
                    ['admin.notifications.index', 'اعلان‌ها و Broadcast', 'اعلان'],
                    ['admin.reports.index', 'گزارش‌ها و لاگ‌ها', 'گزارش'],
                    ['admin.settings.index', 'تنظیمات ربات', 'تنظیم'],
                ];
            @endphp

            @foreach ($items as [$routeName, $label, $short])
                @if ($routeName === 'dashboard' || \Illuminate\Support\Facades\Route::has($routeName))
                    <a href="{{ route($routeName) }}" class="nav-item flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium {{ request()->routeIs($routeName === 'dashboard' ? 'dashboard' : str_replace('.index', '.*', $routeName)) ? 'active' : '' }}">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-current/5 text-[10px] font-bold">{{ $short }}</span>
                        <span>{{ $label }}</span>
                    </a>
                @else
                    <span class="nav-item disabled flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-current/5 text-[10px] font-bold">{{ $short }}</span>
                        <span class="flex-1">{{ $label }}</span>
                        <span class="text-[10px] text-slate-600">به‌زودی</span>
                    </span>
                @endif
            @endforeach
        </nav>

        <div class="mt-auto pt-4">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-white">
                <p class="text-xs text-slate-400">Horton Admin</p>
                <p class="mt-2 text-sm leading-7 text-slate-200">تمام مدیریت ربات از این پنل انجام می‌شود.</p>
            </div>
        </div>
    </aside>

    <main class="min-h-screen lg:ms-72">
        <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-line/80 bg-white/80 px-5 backdrop-blur-xl lg:px-10">
            <div class="flex items-center gap-3">
                <button onclick="hortonAdmin.toggleSidebar()" class="grid h-10 w-10 place-items-center rounded-xl border border-line text-ink lg:hidden" aria-label="باز کردن منو">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <p class="text-xs text-ink-soft">پنل مدیریت HORTON</p>
                    <h1 class="mt-1 text-lg font-bold text-ink">{{ $heading ?? 'نمای کلی' }}</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button data-toast="اعلان جدیدی ندارید" class="relative grid h-10 w-10 place-items-center rounded-xl border border-line text-ink-soft hover:border-brand hover:text-brand" aria-label="اعلان‌ها">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    <i class="absolute start-2 top-2 h-1.5 w-1.5 rounded-full bg-danger"></i>
                </button>

                <div class="hidden items-center gap-2 border-s border-line ps-3 sm:flex">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-blue-100 text-sm font-bold text-brand">{{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}</span>
                    <span class="text-sm font-medium text-ink">{{ auth()->user()->name ?? 'مدیر' }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-line px-3 py-2 text-xs text-ink-soft hover:border-danger hover:text-danger">
                        خروج
                    </button>
                </form>
            </div>
        </header>

        <div class="page-enter p-5 lg:p-10">
            @if(session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @if (isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </div>
    </main>

    @stack('modals')
    @livewireScripts
</body>
</html>
