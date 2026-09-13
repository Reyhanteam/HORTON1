<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'HORTON | داشبورد مدیریت' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="app-shell min-h-screen antialiased">
    <div class="min-h-screen bg-slate-50">
        @include('dashboard.partials.sidebar')

        <div class="min-h-screen lg:ms-72">
            @include('dashboard.partials.header')

            <main class="page-enter min-h-[calc(100vh-9rem)] p-5 lg:p-10">
                @if(session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>

            @include('dashboard.partials.footer')
        </div>
    </div>

    @livewireScripts
</body>
</html>
