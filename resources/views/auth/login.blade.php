<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk &middot; {{ config('app.name', 'TailAdmin') }}</title>

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
    <div class="flex min-h-full items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 flex items-center justify-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500 text-white shadow-sm">
                    <x-panel-icon name="sparkles" class="h-5 w-5" />
                </span>
                <span class="text-xl font-semibold text-gray-800 dark:text-gray-100">TailAdmin</span>
            </div>

            <div class="ta-card p-6 sm:p-8">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Masuk ke panel</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan akun Anda untuk melanjutkan.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-error-500/20 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="ta-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               class="ta-input @error('email') !border-error-500 @enderror"
                               placeholder="nama@perusahaan.com">
                    </div>

                    <div>
                        <label for="password" class="ta-label">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               class="ta-input" placeholder="••••••••">
                    </div>

                    <label class="inline-flex cursor-pointer items-center gap-2.5">
                        <input type="checkbox" name="remember" value="1"
                               class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Ingat saya</span>
                    </label>

                    <button type="submit" class="ta-btn-primary w-full !py-3">
                        Masuk
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                Demo: <span class="font-medium text-gray-500 dark:text-gray-400">admin@example.com</span> / <span class="font-medium text-gray-500 dark:text-gray-400">password</span>
            </p>
        </div>
    </div>
</body>
</html>
