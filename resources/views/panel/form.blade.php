@extends('layouts.tailadmin')

@section('title', __($record ? 'ui.form.edit' : 'ui.form.add', ['name' => $singular]))
@section('subtitle', $definition['description'])

@section('content')
    <form method="POST"
          action="{{ $record ? route('panel.update', [$resourceKey, $record->slug]) : route('panel.store', $resourceKey) }}"
          class="mx-auto max-w-4xl space-y-6">
        @csrf
        @if ($record)
            @method('PUT')
        @endif

        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        {{ __($record ? 'ui.form.edit' : 'ui.form.add', ['name' => $singular]) }}
                    </h2>
                    <p class="text-xs text-gray-400">{{ __('ui.form.complete') }}</p>
                </div>
                <a href="{{ route('panel.index', $resourceKey) }}" class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="x-mark" class="h-4 w-4" />
                    {{ __('ui.actions.cancel') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                @foreach ($fields as $field)
                    @php($full = ($field['col'] ?? 'half') === 'full')

                    <div class="{{ $full ? 'md:col-span-2' : '' }}">
                        <label for="field-{{ $field['name'] }}" class="ta-label">
                            {{ $field['label'] }}
                            @if ($field['required'])
                                <span class="text-error-500">*</span>
                            @endif
                        </label>

                        @if ($field['type'] === 'toggle')
                            <input type="hidden" name="{{ $field['name'] }}" value="0">
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input type="checkbox" id="field-{{ $field['name'] }}" name="{{ $field['name'] }}" value="1"
                                       class="h-5 w-5 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900"
                                       @checked((bool) $field['value'])>
                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $field['help'] ?? __('ui.fields.active') }}</span>
                            </label>

                        @elseif ($field['type'] === 'select')
                            <select id="field-{{ $field['name'] }}" name="{{ $field['name'] }}" class="ta-select">
                                <option value="">{{ __('ui.actions.choose') }}</option>
                                @foreach ($field['options'] as $value => $label)
                                    <option value="{{ $value }}" @selected((string) $field['value'] === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>

                        @elseif ($field['type'] === 'multiselect')
                            @php($selected = array_map('strval', (array) ($field['value'] ?? [])))
                            <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                @foreach ($field['options'] as $value => $label)
                                    <label class="flex items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-white/5">
                                        <input type="checkbox" name="{{ $field['name'] }}[]" value="{{ $value }}"
                                               class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900"
                                               @checked(in_array((string) $value, $selected, true))>
                                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>

                        @elseif ($field['type'] === 'icon')
                            @include('panel.partials.icon-picker', ['name' => $field['name'], 'value' => $field['value']])

                        @elseif ($field['type'] === 'textarea')
                            <textarea id="field-{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4"
                                      class="ta-textarea @error($field['name']) !border-error-500 @enderror">{{ $field['value'] }}</textarea>

                        @else
                            <input type="{{ $field['type'] === 'password' ? 'password' : $field['type'] }}"
                                   id="field-{{ $field['name'] }}"
                                   name="{{ $field['name'] }}"
                                   value="{{ $field['type'] === 'password' ? '' : $field['value'] }}"
                                   @if ($field['type'] === 'password') autocomplete="new-password" @endif
                                   class="ta-input @error($field['name']) !border-error-500 @enderror">
                        @endif

                        @error($field['name'])
                            <p class="ta-help !text-error-500">{{ $message }}</p>
                        @enderror

                        @if (! empty($field['help']) && $field['type'] !== 'toggle')
                            <p class="ta-help">{{ $field['help'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                <a href="{{ route('panel.index', $resourceKey) }}" class="ta-btn-ghost">{{ __('ui.actions.cancel') }}</a>
                <button type="submit" class="ta-btn-primary">
                    <x-panel-icon name="plus" class="h-4 w-4 rotate-45" />
                    {{ __('ui.actions.save') }}
                </button>
            </div>
        </div>
    </form>
@endsection
