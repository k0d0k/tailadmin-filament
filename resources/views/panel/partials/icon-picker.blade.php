@php
    /**
     * Visual icon picker. Renders the current selection, a preview and a
     * popover grid of every icon available in App\Support\Panel\IconLibrary.
     * Expects:
     *   $name  — form field name (omit/null when the value is serialized via JS)
     *   $value — currently selected icon key
     */
    $pickerName = $name ?? null;
    $pickerValue = $value ?? '';
    $previewClass = $previewClass ?? 'h-11 w-11';
@endphp

<div data-icon-picker class="relative">
    <input type="hidden" @if (filled($pickerName)) name="{{ $pickerName }}" @endif
           value="{{ $pickerValue }}" data-icon-input>

    <div class="flex items-center gap-2">
        <span data-icon-preview
              class="flex {{ $previewClass }} shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-white/5 dark:text-gray-200">
            {!! \App\Support\Panel\IconLibrary::render($pickerValue ?: 'bars-3', 'h-6 w-6') !!}
        </span>
        <button type="button" data-icon-toggle
                class="ta-btn-ghost !py-2 !text-xs">
            <x-panel-icon name="chevron-down" class="h-4 w-4" />
            <span data-icon-label>{{ $pickerValue ?: __('ui.actions.choose') }}</span>
        </button>
    </div>

    <div data-icon-panel
         class="absolute start-0 z-50 mt-2 hidden w-72 rounded-xl border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-900">
        <div class="grid max-h-56 grid-cols-8 gap-1 overflow-y-auto">
            @foreach (\App\Support\Panel\IconLibrary::keys() as $iconKey)
                <button type="button" data-icon-option="{{ $iconKey }}" title="{{ $iconKey }}"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border transition-colors
                               {{ $pickerValue === $iconKey
                                    ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'
                                    : 'border-transparent text-gray-500 hover:border-brand-400 hover:text-brand-500 dark:text-gray-400' }}">
                    {!! \App\Support\Panel\IconLibrary::render($iconKey, 'h-5 w-5') !!}
                </button>
            @endforeach
        </div>
    </div>
</div>
