@extends('layouts.tailadmin')

@section('title', __('ui.report_builder.title'))
@section('subtitle', __('ui.report_builder.subtitle'))

@section('content')
    <style>
        .ta-report-chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: .5rem;
            border: 1px solid var(--color-gray-200, #e4e7ec);
            background: #fff;
            padding: .4rem .7rem;
            font-size: .75rem;
            font-weight: 500;
            color: var(--color-gray-700, #344054);
            box-shadow: 0 1px 2px rgb(16 24 40 / .05);
            transition: border-color .15s ease, color .15s ease;
        }

        .dark .ta-report-chip {
            border-color: var(--color-gray-700, #344054);
            background: var(--color-gray-900, #101828);
            color: var(--color-gray-200, #e4e7ec);
        }

        .ta-report-chip:hover {
            border-color: var(--color-brand-400, #7592ff);
            color: var(--color-brand-600, #465fff);
        }

        .ta-report-drag {
            cursor: grab;
            user-select: none;
        }

        .ta-report-drag:active {
            cursor: grabbing;
        }
    </style>

    <div data-report-builder
         data-run-url="{{ route('panel.report-builder.run') }}"
         data-export-base="{{ route('panel.report-builder.export', '__FORMAT__') }}"
         data-remove-label="{{ __('ui.actions.delete') }}">

        <script type="application/json" data-report-sources>@json($sources)</script>

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- Data source + available fields --}}
            <div class="ta-card lg:col-span-1">
                <div class="ta-card-header">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.report_builder.source') }}</h2>
                        <p class="text-xs text-gray-400">{{ __('ui.report_builder.source_hint') }}</p>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label class="ta-label" for="report-source">{{ __('ui.report_builder.source') }}</label>
                        <select id="report-source" data-report-source class="ta-select">
                            <option value="">— {{ __('ui.actions.choose') }} —</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source['key'] }}">{{ $source['title'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <p data-report-choose class="ta-help">{{ __('ui.report_builder.choose_source') }}</p>

                    <div data-report-palette class="hidden space-y-4">
                        <div>
                            <p class="mb-2 text-[11px] font-semibold tracking-wider text-gray-400 uppercase">{{ __('ui.report_builder.primary') }}</p>
                            <div data-report-primary class="flex flex-wrap gap-2"></div>
                        </div>

                        <div data-report-relations-wrap>
                            <p class="mb-2 text-[11px] font-semibold tracking-wider text-gray-400 uppercase">{{ __('ui.report_builder.relations') }}</p>
                            <div data-report-relations class="flex flex-wrap gap-2"></div>
                            <p data-report-no-relations class="ta-help hidden">{{ __('ui.report_builder.no_relations') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Selected columns (drag & drop canvas) --}}
            <div class="ta-card lg:col-span-2">
                <div class="ta-card-header">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.report_builder.selected') }}</h2>
                        <p class="text-xs text-gray-400">{{ __('ui.report_builder.drop_hint') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" data-report-run class="ta-btn-primary !py-2 !text-xs" disabled>
                            <x-panel-icon name="chart-bar" class="h-4 w-4" />
                            {{ __('ui.report_builder.run') }}
                        </button>
                        <button type="button" data-report-export="csv" class="ta-btn-ghost !py-2 !text-xs" disabled>
                            <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                            {{ __('ui.actions.export_csv') }}
                        </button>
                        <button type="button" data-report-export="xlsx" class="ta-btn-ghost !py-2 !text-xs" disabled>
                            <x-panel-icon name="arrow-down-tray" class="h-4 w-4" />
                            {{ __('ui.actions.export_excel') }}
                        </button>
                    </div>
                </div>

                <div class="p-5">
                    <div class="mb-3 flex items-center gap-3">
                        <label class="ta-label !mb-0 !text-xs" for="report-limit">{{ __('ui.report_builder.limit') }}</label>
                        <input id="report-limit" type="number" min="1" max="2000" value="100" data-report-limit class="ta-input !w-28 !py-2">
                        <span class="ta-help !mt-0">{{ __('ui.report_builder.limit_hint') }}</span>
                    </div>

                    <div data-report-dropzone
                         class="flex min-h-[140px] flex-wrap content-start gap-2 rounded-xl border-2 border-dashed border-gray-200 p-3 transition-colors dark:border-gray-700">
                        <p data-report-empty class="w-full self-center py-8 text-center text-sm text-gray-400">
                            {{ __('ui.report_builder.no_columns') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Preview --}}
        <div data-report-results class="ta-card mt-5 hidden">
            <div class="ta-card-header">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('ui.report_builder.results') }}
                    <span data-report-total class="ms-1 text-sm font-normal text-gray-400"></span>
                </h2>
            </div>
            <div class="overflow-x-auto p-5">
                <div data-report-table></div>
            </div>
        </div>
    </div>
@endsection
