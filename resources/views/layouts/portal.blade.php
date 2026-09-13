<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'HORTON' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell min-h-screen antialiased">
    @php
        $items = [
            ['dashboard', 'داشبورد'],
            ['portal.shop', 'فروشگاه'],
            ['portal.orders', 'سفارش‌های من'],
            ['portal.services', 'سرویس‌های من'],
            ['portal.wallet', 'کیف پول'],
            ['portal.referrals', 'معرفی دوستان'],
            ['portal.notifications', 'اعلان‌ها'],
            ['portal.support', 'پشتیبانی'],
            ['portal.profile', 'پروفایل'],
        ];
    @endphp

    <aside class="fixed inset-y-0 start-0 z-40 hidden w-72 flex-col border-e border-white/10 bg-ink p-5 lg:flex">
        <a href="{{ route('dashboard') }}" class="mb-8 flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand text-lg font-bold text-white">H</span>
            <span><strong class="block text-lg text-white">HORTON</strong><small class="text-xs text-slate-400">حساب کاربری</small></span>
        </a>
        <nav class="space-y-1">
            @foreach($items as [$route, $label])
                <a href="{{ route($route) }}" class="nav-item block rounded-xl px-3 py-3 text-sm font-medium {{ request()->routeIs($route) ? 'bg-white/10 text-white' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4 text-xs text-slate-300">
            <div class="font-semibold text-white">نیاز به کمک دارید؟</div>
            <p class="mt-1 leading-6">از بخش پشتیبانی با تیم HORTON در ارتباط باشید.</p>
            <a href="{{ route('portal.support') }}" class="mt-3 inline-block text-brand">ثبت درخواست ←</a>
        </div>
    </aside>

    <main class="min-h-screen lg:ms-72">
        <header class="sticky top-0 z-30 border-b border-line/80 bg-white/85 px-5 backdrop-blur-xl lg:px-10">
            <div class="flex h-20 items-center justify-between">
                <div>
                    <p class="text-xs text-ink-soft">حساب کاربری HORTON</p>
                    <h1 class="mt-1 text-lg font-bold text-ink">{{ $heading ?? 'حساب کاربری' }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('portal.notifications') }}" class="hidden rounded-xl border border-line px-3 py-2 text-xs sm:block">اعلان‌ها</a>
                    <span class="hidden text-sm sm:block">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-xl border border-line px-3 py-2 text-xs">خروج</button>
                    </form>
                </div>
            </div>
            <nav class="flex gap-2 overflow-x-auto pb-3 lg:hidden">
                @foreach($items as [$route, $label])
                    <a href="{{ route($route) }}" class="whitespace-nowrap rounded-xl border border-line px-3 py-2 text-xs {{ request()->routeIs($route) ? 'bg-ink text-white' : 'bg-white' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </header>
        <div class="page-enter p-5 lg:p-10">
            @if(session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</body>
</html>
