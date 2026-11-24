@php
    use App\Support\Panel\IconLibrary;

    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user?->name))
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    $locale = app()->getLocale();
    $locales = config('panel.locales', []);
    $skins = config('panel.skins', []);
    $currentMode = config('panel.default_mode', 'system');

    $menuPanel = 'absolute mt-2 hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800';
    $menuItem = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-50 data-[active=true]:bg-brand-50 data-[active=true]:font-medium data-[active=true]:text-brand-600 dark:text-gray-300 dark:hover:bg-white/5 dark:data-[active=true]:bg-brand-500/15 dark:data-[active=true]:text-brand-400';
@endphp

<header class="sticky top-0 z-20 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" data-sidebar-toggle class="ta-icon-btn lg:hidden" aria-label="{{ __('ui.topbar.open_menu') }}">
                <x-panel-icon name="bars-3" class="h-5 w-5" />
            </button>

            <button type="button" data-sidebar-collapse class="ta-icon-btn hidden lg:inline-flex"
                    aria-label="{{ __('ui.topbar.hide_menu') }}" aria-expanded="true" title="{{ __('ui.topbar.hide_menu') }}">
                <span data-collapse-icon="expanded"><x-panel-icon name="chevron-double-left" class="h-5 w-5" /></span>
                <span data-collapse-icon="collapsed" class="hidden"><x-panel-icon name="chevron-double-right" class="h-5 w-5" /></span>
            </button>

            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold text-gray-800 dark:text-gray-100">@yield('title', __('ui.dashboard.title'))</h1>
                <p class="hidden truncate text-xs text-gray-400 sm:block">@yield('subtitle', __('ui.dashboard.subtitle'))</p>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            {{-- Colour mode --}}
            <div class="relative">
                <button type="button" data-dropdown-toggle="mode-menu" class="ta-icon-btn" aria-label="{{ __('ui.topbar.change_theme') }}">
                    <span data-mode-icon="light"><x-panel-icon name="sun" class="h-5 w-5" /></span>
                    <span data-mode-icon="dark" class="hidden"><x-panel-icon name="moon" class="h-5 w-5" /></span>
                </button>

                <div id="mode-menu" data-dropdown-menu class="{{ $menuPanel }} right-0 w-44">
                    <div class="p-1.5">
                        @foreach (['light' => 'sun', 'dark' => 'moon', 'system' => 'computer-desktop'] as $mode => $icon)
                            <button type="button" data-set-mode="{{ $mode }}" data-active="{{ $mode === $currentMode ? 'true' : 'false' }}"
                                    class="group {{ $menuItem }}" role="menuitemradio" aria-checked="{{ $mode === $currentMode ? 'true' : 'false' }}">
                                <x-panel-icon :name="$icon" class="h-4 w-4" />
                                <span>{{ __('ui.topbar.'.$mode) }}</span>
                                <x-panel-icon name="check" class="ml-auto hidden h-4 w-4 group-data-[active=true]:block" />
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Skin --}}
            <div class="relative">
                <button type="button" data-dropdown-toggle="skin-menu" class="ta-icon-btn" aria-label="{{ __('ui.topbar.skin') }}">
                    <x-panel-icon name="paint-brush" class="h-5 w-5" />
                </button>

                <div id="skin-menu" data-dropdown-menu class="{{ $menuPanel }} right-0 w-60">
                    <p class="border-b border-gray-100 px-3 py-2 text-[11px] font-semibold tracking-wider text-gray-400 uppercase dark:border-gray-700">
                        {{ __('ui.topbar.skin') }}
                    </p>
                    <div class="grid grid-cols-3 gap-2 p-3">
                        @foreach ($skins as $key => $skin)
                            <button type="button" data-set-skin="{{ $key }}" title="{{ $skin['label'] }}"
                                    class="flex flex-col items-center gap-1.5 rounded-lg p-2 text-center transition hover:bg-gray-50 data-[active=true]:bg-brand-50 data-[active=true]:ring-1 data-[active=true]:ring-brand-500/40 dark:hover:bg-white/5 dark:data-[active=true]:bg-brand-500/15">
                                <span class="h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800" style="background-color: {{ $skin['color'] }}"></span>
                                <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ $skin['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Language --}}
            <div class="relative">
                <button type="button" data-dropdown-toggle="lang-menu" class="ta-icon-btn" aria-label="{{ __('ui.topbar.language') }}">
                    <x-panel-icon name="globe-alt" class="h-5 w-5" />
                </button>

                <div id="lang-menu" data-dropdown-menu class="{{ $menuPanel }} right-0 w-48">
                    <div class="p-1.5">
                        @foreach ($locales as $code => $meta)
                            <form method="POST" action="{{ route('locale.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $code }}">
                                <button type="submit" data-active="{{ $code === $locale ? 'true' : 'false' }}" class="{{ $menuItem }}">
                                    <span class="text-xs font-semibold uppercase">{{ $code }}</span>
                                    <span>{{ $meta['native'] }}</span>
                                    @if ($code === $locale)
                                        <x-panel-icon name="check" class="ml-auto h-4 w-4" />
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- User --}}
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
                        <a href="{{ route('panel.profile') }}"
                           class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                            <x-panel-icon name="user-circle" class="h-4 w-4" />
                            {{ __('ui.topbar.profile') }}
                        </a>

                        <a href="{{ route('panel.password.edit') }}"
                           class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                            <x-panel-icon name="lock-closed" class="h-4 w-4" />
                            {{ __('ui.topbar.change_password') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-error-600 transition hover:bg-error-50 dark:text-error-500 dark:hover:bg-error-500/10">
                                <x-panel-icon name="arrow-right-on-rectangle" class="h-4 w-4" />
                                {{ __('ui.topbar.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
