@extends('layouts.tailadmin')

@section('title', $title)
@section('subtitle', $definition['description'])

@php
    $defaultSort = $definition['default_sort'] ?? null;
    $orderIndex = 0;

    if ($defaultSort) {
        foreach ($columns as $index => $column) {
            if ($column['data'] === $defaultSort[0]) {
                $orderIndex = $index;
                break;
            }
        }
    }

    $order = $defaultSort ? [[$orderIndex, $defaultSort[1]]] : [];
@endphp

@section('content')
    <div class="ta-card">
        <div class="ta-card-header">
            <div>
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.index.list_of', ['title' => $title]) }}</h2>
                <p class="text-xs text-gray-400">{{ __('ui.index.server_side_hint') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-export="csv" class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                    {{ __('ui.actions.export_csv') }}
                </button>
                <button type="button" data-export="xlsx" class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                    {{ __('ui.actions.export_excel') }}
                </button>

                @can('create', $definition['model'])
                    <button type="button" data-import-open class="ta-btn-ghost !py-2 !text-xs">
                        <x-panel-icon name="arrow-down-tray" class="h-4 w-4 rotate-180" />
                        {{ __('ui.actions.import_csv') }}
                    </button>

                    <a href="{{ route('panel.create', $resourceKey) }}" class="ta-btn-primary !py-2 !text-xs">
                        <x-panel-icon name="plus" class="h-4 w-4" />
                        {{ __('ui.actions.add') }} {{ $singular }}
                    </a>
                @endcan
            </div>
        </div>

        @if (! empty($filters))
            <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                @foreach ($filters as $filter)
                    <div class="w-full sm:w-48">
                        <label class="ta-label !mb-1 !text-xs">{{ $filter['label'] }}</label>
                        <select data-filter="{{ $filter['name'] }}" class="ta-select !py-2">
                            <option value="">{{ __('ui.actions.all') }}</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <button type="button" data-filter-reset class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="x-mark" class="h-4 w-4" />
                    {{ __('ui.actions.reset') }}
                </button>
            </div>
        @endif

        <div class="p-5">
            <div class="overflow-x-auto">
                <div
                    data-datatable
                    data-endpoint="{{ route('panel.index', $resourceKey) }}"
                    data-export-base="{{ route('panel.export', [$resourceKey, '__FORMAT__']) }}"
                    data-order='@json($order)'
                    data-page-length="10"
                >
                    <script type="application/json" data-dt-columns>@json($columns)</script>

                    <table class="ta-table w-full min-w-[760px]">
                        <thead></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('create', $definition['model'])
        <div data-import-modal class="fixed inset-0 z-40 hidden items-center justify-center bg-gray-900/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="import-modal-title">
            <div class="ta-card w-full max-w-lg">
                <div class="ta-card-header">
                    <div>
                        <h2 id="import-modal-title" class="text-base font-semibold text-gray-800 dark:text-gray-100">
                            {{ __('ui.import.title', ['title' => $title]) }}
                        </h2>
                        <p class="text-xs text-gray-400">{{ __('ui.import.hint') }}</p>
                    </div>
                    <button type="button" data-import-close class="ta-btn-ghost !p-2" aria-label="{{ __('ui.actions.close') }}">
                        <x-panel-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('panel.import', $resourceKey) }}"
                      enctype="multipart/form-data"
                      data-import-success="{{ __('ui.import.success', ['count' => '__COUNT__']) }}"
                      data-import-failed="{{ __('ui.import.failed') }}"
                      class="space-y-4 p-5">
                    @csrf

                    <div>
                        <label class="ta-label">{{ __('ui.import.file') }}</label>
                        <label class="ta-btn-ghost w-full cursor-pointer !py-2 !text-xs">
                            <x-panel-icon name="arrow-down-tray" class="h-4 w-4 rotate-180" />
                            {{ __('ui.actions.choose_file') }}
                            <input type="file" name="file" accept=".csv,text/csv,text/plain" data-import-input required class="sr-only">
                        </label>
                        <p data-import-filename class="ta-help"></p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <a href="{{ route('panel.import.template', $resourceKey) }}" class="ta-btn-ghost !py-2 !text-xs">
                            <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                            {{ __('ui.import.template') }}
                        </a>

                        <div class="flex items-center gap-3">
                            <button type="button" data-import-close class="ta-btn-ghost">{{ __('ui.actions.cancel') }}</button>
                            <button type="submit" class="ta-btn-primary">
                                <x-panel-icon name="arrow-down-tray" class="h-4 w-4 rotate-180" />
                                {{ __('ui.import.submit') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
