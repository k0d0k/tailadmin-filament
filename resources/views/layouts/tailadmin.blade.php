@php
    $locale = app()->getLocale();
    $dir = config("panel.locales.{$locale}.dir", 'ltr');
    $skins = config('panel.skins', []);
    $locales = config('panel.locales', []);
    $defaultSkin = config('panel.default_skin', 'indigo');
    $defaultMode = config('panel.default_mode', 'system');
    $modes = config('panel.modes', ['light', 'dark', 'system']);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}"
      data-skin="{{ $defaultSkin }}" data-mode="{{ $defaultMode }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('ui.dashboard.title')) &middot; {{ config('app.name', 'TailAdmin') }}</title>

    <script>
        (function () {
            try {
                var mode = localStorage.getItem('tailadmin-mode') || @json($defaultMode);
                var skin = localStorage.getItem('tailadmin-skin') || @json($defaultSkin);
                var media = window.matchMedia('(prefers-color-scheme: dark)');
                var dark = mode === 'dark' || (mode === 'system' && media.matches);
                if (dark) document.documentElement.classList.add('dark');
                document.documentElement.dataset.mode = mode;
                document.documentElement.dataset.skin = skin;

                if (localStorage.getItem('tailadmin-sidebar') === 'collapsed') {
                    document.documentElement.setAttribute('data-sidebar-collapsed', '');
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $modeLabels = [];
    foreach ($modes as $mode) {
        $modeLabels[$mode] = __('ui.topbar.'.$mode);
    }
@endphp
<body class="h-full">
    <script>
        window.__TA__ = {
            defaultMode: @json($defaultMode),
            defaultSkin: @json($defaultSkin),
            modes: @json($modeLabels),
            datatable: {
                placeholder: @json(__('ui.datatable.placeholder')),
                lengthMenu: @json(__('ui.datatable.length_menu')),
                info: @json(__('ui.datatable.info')),
                infoEmpty: @json(__('ui.datatable.info_empty')),
                infoFiltered: @json(__('ui.datatable.info_filtered')),
                zeroRecords: @json(__('ui.datatable.zero_records')),
                emptyTable: @json(__('ui.datatable.empty_table')),
                processing: @json(__('ui.datatable.processing')),
            },
            labels: {
                showMenu: @json(__('ui.topbar.show_menu')),
                hideMenu: @json(__('ui.topbar.hide_menu')),
                confirmDelete: @json(__('ui.index.confirm_delete')),
                deleted: @json(__('ui.index.deleted')),
            },
        };
    </script>

    <div class="min-h-full">
        @include('panel.partials.sidebar')

        <div class="fixed inset-0 z-30 hidden bg-gray-900/50 lg:hidden" data-sidebar-backdrop></div>

        <div class="lg:pl-[290px]" data-sidebar-content>
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

    @include('panel.partials.lightbox')

    @stack('scripts')
</body>
</html>
