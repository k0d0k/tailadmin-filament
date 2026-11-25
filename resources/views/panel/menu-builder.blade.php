@extends('layouts.tailadmin')

@section('title', __('ui.menu_builder.title'))
@section('subtitle', __('ui.menu_builder.subtitle'))

@php($iconMapJson = json_encode($iconMap))

@php($partialData = [
    'iconMapJson' => $iconMapJson,
    'icons' => $icons,
    'permissions' => $permissions,
    'panelResources' => $panelResources,
    'types' => $types,
    'modules' => $modules,
    'locales' => $locales,
])

@section('content')
    <style>
        .ta-node-dragging { opacity: .5; }
        .ta-drop-before { box-shadow: inset 0 3px 0 0 var(--color-brand-500, #465fff); }
        .ta-drop-after { box-shadow: inset 0 -3px 0 0 var(--color-brand-500, #465fff); }
        .ta-drop-inside { outline: 2px dashed var(--color-brand-500, #465fff); outline-offset: 2px; }
    </style>

    <div class="ta-card" data-sync-url="{{ route('panel.menu-builder.sync') }}">
        <div class="ta-card-header">
            <div>
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.menu_builder.title') }}</h2>
                <p class="text-xs text-gray-400">{{ __('ui.menu_builder.hint') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-add-root class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="plus" class="h-4 w-4" />
                    {{ __('ui.menu_builder.add_root') }}
                </button>
                <button type="button" data-save-tree class="ta-btn-primary !py-2 !text-xs">
                    <x-panel-icon name="check" class="h-4 w-4" />
                    {{ __('ui.menu_builder.save') }}
                </button>
            </div>
        </div>

        <div class="p-4">
            <p class="mb-3 text-xs text-gray-400">{{ __('ui.menu_builder.drag_hint') }}</p>

            <ul data-menu-tree class="space-y-2">
                @foreach ($tree as $node)
                    @include('panel.partials.menu-node', $partialData + ['node' => $node])
                @endforeach
            </ul>

            @if ($tree === [])
                <p data-empty-hint
                   class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-400 dark:border-gray-800">
                    {{ __('ui.menu_builder.empty') }}
                </p>
            @endif
        </div>
    </div>

    <template id="menu-node-template">
        @include('panel.partials.menu-node', $partialData + ['node' => [
            'id' => '__ID__',
            'title' => [],
            'key' => null,
            'type' => \App\Models\Menu::TYPE_LINK,
            'label' => '',
            'icon' => null,
            'url' => null,
            'route_name' => null,
            'resource' => null,
            'module_key' => null,
            'permission' => null,
            'is_active' => true,
            'is_visible' => true,
            'target_blank' => false,
            'children' => [],
        ]])
    </template>
@endsection
