@extends('layouts.tailadmin')

@section('title', __('ui.profile.title'))
@section('subtitle', __('ui.profile.subtitle'))

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('panel.profile.update') }}" class="ta-card">
                @csrf
                @method('PUT')

                <div class="ta-card-header">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.profile.heading') }}</h2>
                        <p class="text-xs text-gray-400">{{ __('ui.profile.subtitle') }}</p>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <div>
                        <label for="name" class="ta-label">
                            {{ __('ui.profile.name') }} <span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                               class="ta-input @error('name') !border-error-500 @enderror">
                        @error('name')
                            <p class="ta-help !text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="ta-label">
                            {{ __('ui.profile.email') }} <span class="text-error-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                               class="ta-input @error('email') !border-error-500 @enderror">
                        @error('email')
                            <p class="ta-help !text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                    <button type="submit" class="ta-btn-primary">
                        <x-panel-icon name="check" class="h-4 w-4" />
                        {{ __('ui.profile.submit') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="ta-card">
            <div class="ta-card-header">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.profile.heading') }}</h2>
            </div>
            <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('ui.profile.role') }}</dt>
                    <dd class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ $user->getRoleNames()->implode(', ') ?: '—' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('ui.profile.principal') }}</dt>
                    <dd class="text-sm text-gray-700 dark:text-gray-200">{{ $user->principal?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('ui.profile.tenant') }}</dt>
                    <dd class="text-sm text-gray-700 dark:text-gray-200">{{ $user->tenant?->name ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
