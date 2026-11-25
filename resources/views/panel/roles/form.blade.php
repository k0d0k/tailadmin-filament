@extends('layouts.tailadmin')

@section('title', __('ui.actions.edit').' '.$role->displayName())
@section('subtitle', __('ui.roles.subtitle'))

@section('content')
    <form method="POST" action="{{ route('panel.roles.update', $role) }}" class="mx-auto max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.actions.edit') }} {{ __('ui.roles.name') }}</h2>
                    <p class="text-xs text-gray-400">{{ __('ui.roles.subtitle') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="ta-label">{{ __('ui.roles.name') }} <span class="text-error-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="ta-input">
                    @error('name')
                        <p class="ta-help !text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="ta-label">Label</label>
                    <input type="text" name="label" value="{{ old('label', $role->label) }}" class="ta-input">
                </div>

                <div>
                    <label class="ta-label">{{ __('ui.roles.scope') }} <span class="text-error-500">*</span></label>
                    <select name="scope" class="ta-select">
                        @foreach ($scopeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('scope', $role->scope) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="ta-label">{{ __('ui.roles.description') }}</label>
                    <textarea name="description" rows="2" class="ta-input">{{ old('description', $role->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('panel.roles.index') }}" class="ta-btn-ghost !py-2 !text-xs">
                <x-panel-icon name="arrow-left" class="h-4 w-4" />
                {{ __('ui.actions.back') }}
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('panel.roles.access', $role) }}" class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="key" class="h-4 w-4" />
                    {{ __('ui.roles.access') }}
                </a>
                <button type="submit" class="ta-btn-primary !py-2 !text-xs">
                    <x-panel-icon name="check" class="h-4 w-4" />
                    {{ __('ui.actions.save') }}
                </button>
            </div>
        </div>
    </form>
@endsection
