@php
    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user?->name))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sticky top-0 z-20 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" data-sidebar-toggle class="ta-icon-btn lg:hidden" aria-label="Buka menu">
                <x-panel-icon name="bars-3" class="h-5 w-5" />
            </button>

            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold text-gray-800 dark:text-gray-100">@yield('title', 'Dashboard')</h1>
                <p class="hidden truncate text-xs text-gray-400 sm:block">@yield('subtitle', 'Kelola data panel administrasi')</p>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button" data-theme-toggle class="ta-icon-btn" aria-label="Ganti tema">
                <span data-theme-icon="light"><x-panel-icon name="sun" class="h-5 w-5" /></span>
                <span data-theme-icon="dark" class="hidden"><x-panel-icon name="moon" class="h-5 w-5" /></span>
            </button>

            <div class="relative">
                <button type="button" data-dropdown-toggle="user-menu"
                        class="flex items-center gap-3 rounded-lg p-1.5 pr-2.5 transition hover:bg-gray-50 dark:hover:bg-white/5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-500 text-xs font-semibold text-white">
                        {{ $initials ?: 'U' }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $user?->name }}</span>
                        <span class="block text-xs text-gray-400">{{ $user?->getRoleNames()->first() ?? 'user' }}</span>
                    </span>
                    <x-panel-icon name="chevron-down" class="hidden h-4 w-4 text-gray-400 sm:block" />
                </button>

                <div id="user-menu" data-dropdown-menu
                     class="absolute right-0 mt-2 hidden w-60 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $user?->name }}</p>
                        <p class="truncate text-xs text-gray-400">{{ $user?->email }}</p>
                    </div>
                    <div class="p-1.5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-error-600 transition hover:bg-error-50 dark:text-error-500 dark:hover:bg-error-500/10">
                                <x-panel-icon name="arrow-right-on-rectangle" class="h-4 w-4" />
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
