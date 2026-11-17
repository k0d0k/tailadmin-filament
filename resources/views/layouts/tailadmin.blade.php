<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name', 'TailAdmin') }}</title>

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('tailadmin-theme');
                var dark = theme ? theme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full">
        @include('panel.partials.sidebar')

        <div class="fixed inset-0 z-30 hidden bg-gray-900/50 lg:hidden" data-sidebar-backdrop></div>

        <div class="lg:pl-[290px]">
            @include('panel.partials.topbar')

            <main class="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 flex items-center gap-2 rounded-xl border border-success-500/20 bg-success-50 px-4 py-3 text-sm font-medium text-success-600 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-500">
                        <span class="h-2 w-2 rounded-full bg-success-500"></span>
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div id="ta-toasts" class="fixed right-4 bottom-4 z-[100] flex flex-col gap-2"></div>

    @stack('scripts')
</body>
</html>
