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
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Daftar {{ $title }}</h2>
                <p class="text-xs text-gray-400">Data dimuat secara server-side dengan DataTables</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-export="csv" class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                    CSV
                </button>
                <button type="button" data-export="xlsx" class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                    Excel
                </button>

                @can('create', $definition['model'])
                    <a href="{{ route('panel.create', $resourceKey) }}" class="ta-btn-primary !py-2 !text-xs">
                        <x-panel-icon name="plus" class="h-4 w-4" />
                        Tambah {{ $singular }}
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
                            <option value="">Semua</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <button type="button" data-filter-reset class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="x-mark" class="h-4 w-4" />
                    Reset
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
@endsection
