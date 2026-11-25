@php
    /**
     * Renders one node of the nested menu tree, recursively. Expects:
     * $node, $iconMapJson, $icons, $permissions, $panelResources, $types,
     * $modules and $locales.
     */
    $locales = $locales ?? ['id', 'en', 'ar'];
    $types = $types ?? [];
    $modules = $modules ?? [];
    $titles = (array) ($node['title'] ?? []);
    $primaryLocale = $locales[0] ?? 'id';
    $nodeType = $node['type'] ?? \App\Models\Menu::TYPE_LINK;
@endphp

<li data-node data-id="{{ $node['id'] }}"
    class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-wrap items-center gap-2 p-2 sm:flex-nowrap">
        <span data-node-handle draggable="true" title="{{ __('ui.menu_builder.drag_hint') }}"
              class="flex h-8 w-8 shrink-0 cursor-move items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5">
            <x-panel-icon name="bars-3" class="h-4 w-4" />
        </span>

        <div data-icon-picker class="flex shrink-0 items-center gap-2">
            <span data-icon-preview
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-white/5 dark:text-gray-200">
                {!! \App\Support\Panel\IconLibrary::render($node['icon'] ?: 'bars-3', 'h-5 w-5') !!}
            </span>
            <select data-icon-select class="ta-select !w-32 !py-1.5 !text-xs" title="{{ __('ui.menu_builder.icon') }}">
                <option value="">— {{ __('ui.menu_builder.select_icon') }} —</option>
                @foreach ($icons as $value => $label)
                    <option value="{{ $value }}" @selected(\App\Support\Panel\IconLibrary::normalize($node['icon']) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <script type="application/json" data-icon-map>{!! $iconMapJson !!}</script>
        </div>

        <input type="text" data-field="title.{{ $primaryLocale }}" value="{{ $titles[$primaryLocale] ?? '' }}"
               placeholder="{{ __('ui.menu_builder.label') }} ({{ strtoupper($primaryLocale) }})"
               class="ta-input min-w-0 flex-1 !py-1.5 !text-sm">

        <select data-field="type" title="{{ __('ui.menu_builder.type') }}"
                class="ta-select !w-28 !shrink-0 !py-1.5 !text-xs">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected($nodeType === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <label class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
               title="{{ __('ui.menu_builder.active') }}">
            <input type="checkbox" data-field="is_active" @checked($node['is_active'])
                   class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
            {{ __('ui.menu_builder.active') }}
        </label>

        <button type="button" data-toggle-details title="{{ __('ui.menu_builder.url') }}"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5">
            <x-panel-icon name="chevron-down" class="h-4 w-4" />
        </button>

        <button type="button" data-add-child class="ta-btn-ghost shrink-0 !px-2 !py-1.5 !text-xs">
            <x-panel-icon name="plus" class="h-4 w-4" />
            {{ __('ui.menu_builder.add_child') }}
        </button>

        <button type="button" data-remove-node title="{{ __('ui.menu_builder.delete') }}"
                class="ta-btn-ghost shrink-0 !px-2 !py-1.5 !text-xs !text-error-600">
            <x-panel-icon name="trash" class="h-4 w-4" />
        </button>
    </div>

    <div data-node-details
         class="hidden gap-3 border-t border-gray-100 px-3 py-3 md:grid-cols-2 dark:border-gray-800">
        @foreach (array_slice($locales, 1) as $locale)
            <div>
                <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.label') }} ({{ strtoupper($locale) }})</label>
                <input type="text" data-field="title.{{ $locale }}" value="{{ $titles[$locale] ?? '' }}"
                       class="ta-input !py-1.5 !text-xs" @if ($locale === 'ar') dir="rtl" @endif>
            </div>
        @endforeach

        <div>
            <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.key') }}</label>
            <input type="text" data-field="key" value="{{ $node['key'] }}"
                   placeholder="dashboard" class="ta-input !py-1.5 !text-xs">
        </div>

        <div>
            <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.module') }}</label>
            <select data-field="module_key" class="ta-select !py-1.5 !text-xs">
                <option value="">— {{ __('ui.actions.choose') }} —</option>
                @foreach ($modules as $value => $label)
                    <option value="{{ $value }}" @selected(($node['module_key'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.route_name') }}</label>
            <input type="text" data-field="route_name" value="{{ $node['route_name'] }}"
                   placeholder="panel.index" class="ta-input !py-1.5 !text-xs">
        </div>

        <div>
            <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.url') }}</label>
            <input type="text" data-field="url" value="{{ $node['url'] }}"
                   placeholder="/panel/example" class="ta-input !py-1.5 !text-xs">
        </div>

        <div>
            <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.resource') }}</label>
            <select data-field="resource" class="ta-select !py-1.5 !text-xs">
                <option value="">— {{ __('ui.actions.choose') }} —</option>
                @foreach ($panelResources as $panelResource)
                    <option value="{{ $panelResource['key'] }}" @selected($node['resource'] === $panelResource['key'])>
                        {{ $panelResource['title'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="ta-label !mb-1 !text-[11px]">{{ __('ui.menu_builder.permission') }}</label>
            <select data-field="permission" class="ta-select !py-1.5 !text-xs">
                <option value="">— {{ __('ui.actions.choose') }} —</option>
                @foreach ($permissions as $value => $label)
                    <option value="{{ $value }}" @selected($node['permission'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-center gap-5">
            <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <input type="checkbox" data-field="is_visible" @checked($node['is_visible'])
                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                {{ __('ui.menu_builder.visible') }}
            </label>
            <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <input type="checkbox" data-field="target_blank" @checked($node['target_blank'])
                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                {{ __('ui.menu_builder.target_blank') }}
            </label>
        </div>
    </div>

    <ul data-menu-children
        class="ms-4 space-y-2 border-s border-dashed border-gray-200 py-2 pe-2 ps-3 dark:border-gray-800">
        @foreach ($node['children'] as $child)
            @include('panel.partials.menu-node', [
                'node' => $child,
                'iconMapJson' => $iconMapJson,
                'icons' => $icons,
                'permissions' => $permissions,
                'panelResources' => $panelResources,
                'types' => $types,
                'modules' => $modules,
                'locales' => $locales,
            ])
        @endforeach
    </ul>
</li>
