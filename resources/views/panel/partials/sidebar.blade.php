@php
    use App\Support\Acl;
    use App\Support\Panel\ResourceRegistry;

    $currentResource = request()->routeIs('panel.index') || request()->routeIs('panel.create') || request()->routeIs('panel.edit')
        ? request()->route('resource')
        : null;

    $user = auth()->user();
    $canGenerate = $user?->hasRole(Acl::SUPER_ADMIN_ROLE) || ($user?->checkPermissionTo('generate_crud') ?? false);

    $linkBase = 'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors';
    $linkIdle = 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white';
    $linkActive = 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400';
@endphp

<aside
    data-sidebar
    class="fixed inset-y-0 left-0 z-40 flex w-[290px] -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-300 lg:translate-x-0 dark:border-gray-800 dark:bg-gray-900"
>
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-gray-100 px-6 dark:border-gray-800">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white shadow-sm">
            <x-panel-icon name="sparkles" class="h-5 w-5" />
        </span>
        <div class="leading-tight">
            <div class="text-base font-semibold text-gray-800 dark:text-gray-100">TailAdmin</div>
            <div class="text-[11px] font-medium tracking-wide text-gray-400 uppercase">Control Panel</div>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
        <div class="space-y-1">
            <a href="{{ route('panel.dashboard') }}"
               class="{{ $linkBase }} {{ request()->routeIs('panel.dashboard') ? $linkActive : $linkIdle }}">
                <x-panel-icon name="gauge" class="h-5 w-5" />
                Dashboard
            </a>
        </div>

        @foreach (ResourceRegistry::grouped() as $group => $items)
            @php
                $visible = array_filter($items, fn ($item) => auth()->user()?->can('viewAny', $item['model']));
            @endphp

            @if ($visible)
                <div class="space-y-1">
                    <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-gray-400 uppercase dark:text-gray-500">
                        {{ $group }}
                    </p>

                    @foreach ($visible as $item)
                        <a href="{{ route('panel.index', $item['key']) }}"
                           class="{{ $linkBase }} {{ $currentResource === $item['key'] ? $linkActive : $linkIdle }}">
                            <x-panel-icon :name="$item['icon']" class="h-5 w-5" />
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach

        @if ($canGenerate)
            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-gray-400 uppercase dark:text-gray-500">
                    Sistem
                </p>

                <a href="{{ route('filament.admin.pages.crud-generator') }}" target="_blank" rel="noopener"
                   class="{{ $linkBase }} {{ $linkIdle }}">
                    <x-panel-icon name="sparkles" class="h-5 w-5" />
                    CRUD Generator
                    <x-panel-icon name="arrow-right-on-rectangle" class="ml-auto h-4 w-4 text-gray-300 transition group-hover:text-brand-500" />
                </a>
            </div>
        @endif
    </nav>

    <div class="border-t border-gray-100 p-4 dark:border-gray-800">
        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/5">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Masuk sebagai</p>
            <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()?->name }}</p>
            <p class="truncate text-xs text-gray-400">{{ auth()->user()?->email }}</p>
        </div>
    </div>
</aside>
