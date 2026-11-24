@php
    use App\Support\Acl;
    use App\Support\Panel\PanelMenu;
    use App\Support\Panel\ResourceRegistry;

    $user = auth()->user();
    $canGenerate = $user?->hasRole(Acl::SUPER_ADMIN_ROLE) || ($user?->checkPermissionTo('generate_crud') ?? false);
    $canBuildMenu = $user?->hasRole(Acl::SUPER_ADMIN_ROLE) || ($user?->checkPermissionTo('manage_menu') ?? false);
    $canReport = $user?->hasRole(Acl::SUPER_ADMIN_ROLE) || ($user?->checkPermissionTo('manage_report') ?? false);
    $canAudit = $user?->can('viewAny', App\Models\Audit::class) ?? false;

    // Prefer the DB driven menu; fall back to the resource registry so the
    // sidebar keeps working before the seeder has run.
    $sections = PanelMenu::sections($user);

    if ($sections === []) {
        $sections = [];

        foreach (ResourceRegistry::grouped() as $group => $items) {
            $visible = array_values(array_filter($items, fn ($item) => $user?->can('viewAny', $item['model'])));

            if ($visible === []) {
                continue;
            }

            $sections[$group] = array_map(function (array $item): array {
                $url = route('panel.index', $item['key']);

                return [
                    'title' => $item['title'],
                    'icon' => $item['icon'],
                    'url' => $url,
                    'active' => rtrim(request()->url(), '/') === rtrim($url, '/'),
                ];
            }, $visible);
        }
    }

    $linkBase = 'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors';
    $linkIdle = 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white';
    $linkActive = 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400';
@endphp

<aside
    data-sidebar
    class="fixed inset-y-0 left-0 z-40 flex w-[290px] flex-col border-r border-gray-200 bg-white transition-transform duration-300 dark:border-gray-800 dark:bg-gray-900"
>
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-gray-100 px-6 dark:border-gray-800">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white shadow-sm">
            <x-panel-icon name="sparkles" class="h-5 w-5" />
        </span>
        <div class="leading-tight">
            <div class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.app.name') }}</div>
            <div class="text-[11px] font-medium tracking-wide text-gray-400 uppercase">{{ __('ui.app.tagline') }}</div>
        </div>
    </div>

    <div class="shrink-0 px-4 pt-5">
        <a href="{{ route('panel.dashboard') }}"
           class="{{ $linkBase }} {{ request()->routeIs('panel.dashboard') ? $linkActive : $linkIdle }}">
            <x-panel-icon name="gauge" class="h-5 w-5" />
            {{ __('ui.sidebar.dashboard') }}
        </a>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" data-sidebar-nav>
        @if (count($sections) > 1)
            <div>
                <label for="menu-group" class="ta-label !mb-1.5 !text-[11px] !tracking-wider uppercase">{{ __('ui.sidebar.group') }}</label>
                <select id="menu-group" data-menu-group class="ta-select !py-2.5">
                    <option value="">— {{ __('ui.actions.all') }} —</option>
                    @foreach (array_keys($sections) as $group)
                        @if ($group !== '')
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        @endif

        @foreach ($sections as $group => $items)
            <div class="space-y-1" data-menu-section="{{ $group }}">
                @if ($group !== '')
                    <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-gray-400 uppercase dark:text-gray-500">
                        {{ $group }}
                    </p>
                @endif

                @foreach ($items as $item)
                    <a href="{{ $item['url'] }}"
                       class="{{ $linkBase }} {{ $item['active'] ? $linkActive : $linkIdle }}">
                        <x-panel-icon :name="$item['icon']" class="h-5 w-5" />
                        {{ $item['title'] }}
                    </a>
                @endforeach
            </div>
        @endforeach

        @if ($canGenerate || $canBuildMenu || $canReport || $canAudit)
            <div class="space-y-1" data-menu-section="__system">
                <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-gray-400 uppercase dark:text-gray-500">
                    {{ __('ui.sidebar.system') }}
                </p>

                @if ($canBuildMenu)
                    <a href="{{ route('panel.menu-builder') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('panel.menu-builder') ? $linkActive : $linkIdle }}">
                        <x-panel-icon name="bars-3" class="h-5 w-5" />
                        {{ __('ui.menu_builder.title') }}
                    </a>
                @endif

                @if ($canReport)
                    <a href="{{ route('panel.report-builder') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('panel.report-builder') ? $linkActive : $linkIdle }}">
                        <x-panel-icon name="chart-bar" class="h-5 w-5" />
                        {{ __('ui.report_builder.title') }}
                    </a>
                @endif

                @if ($canAudit)
                    <a href="{{ route('panel.audit') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('panel.audit') ? $linkActive : $linkIdle }}">
                        <x-panel-icon name="clock" class="h-5 w-5" />
                        {{ __('ui.audit.title') }}
                    </a>
                @endif

                @if ($canGenerate)
                    <a href="{{ route('panel.generator') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('panel.generator') ? $linkActive : $linkIdle }}">
                        <x-panel-icon name="sparkles" class="h-5 w-5" />
                        {{ __('ui.sidebar.crud_generator') }}
                    </a>
                @endif
            </div>
        @endif
    </nav>

    <div class="border-t border-gray-100 p-4 dark:border-gray-800">
        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/5">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ui.sidebar.signed_in_as') }}</p>
            <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $user?->name }}</p>
            <p class="truncate text-xs text-gray-400">{{ $user?->email }}</p>
        </div>
    </div>
</aside>
