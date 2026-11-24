@extends('layouts.tailadmin')

@section('title', __('ui.audit.title'))
@section('subtitle', __('ui.audit.subtitle'))

@section('content')
    <div class="ta-card">
        <div class="ta-card-header">
            <div>
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.audit.title') }}</h2>
                <p class="text-xs text-gray-400">{{ __('ui.audit.hint') }}</p>
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
                    data-endpoint="{{ route('panel.audit') }}"
                    data-export-base="{{ route('panel.audit.export', ['format' => '__FORMAT__']) }}"
                    data-order='@json([[0, "desc"]])'
                    data-page-length="15"
                >
                    <script type="application/json" data-dt-columns>@json($columns)</script>

                    <table class="ta-table w-full min-w-[900px]">
                        <thead></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
