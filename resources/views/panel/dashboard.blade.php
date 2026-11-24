@extends('layouts.tailadmin')

@section('title', __('ui.dashboard.title'))
@section('subtitle', __('ui.dashboard.subtitle'))

@php
    $tone = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
    ];
@endphp

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($stats as $stat)
            <a href="{{ route('panel.index', $stat['route']) }}" class="ta-card group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $tone[$stat['tone']] }}">
                        <x-panel-icon :name="$stat['icon']" class="h-6 w-6" />
                    </span>
                    <x-panel-icon name="chevron-down" class="h-4 w-4 -rotate-90 text-gray-300 transition group-hover:text-brand-500" />
                </div>
                <p class="mt-4 text-3xl font-semibold text-gray-800 dark:text-gray-100">{{ number_format($stat['value']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            </a>
        @endforeach
    </div>

    @if ($canViewUsers || $canViewPrincipals)
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        @if ($canViewUsers)
        <div class="ta-card {{ $canViewPrincipals ? 'xl:col-span-2' : 'xl:col-span-3' }}">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.dashboard.recent_users') }}</h2>
                    <p class="text-xs text-gray-400">{{ __('ui.dashboard.recent_users_hint') }}</p>
                </div>
                <a href="{{ route('panel.index', 'users') }}" class="ta-btn-ghost !py-2 !text-xs">{{ __('ui.actions.view_all') }}</a>
            </div>

            <div class="overflow-x-auto">
                <table class="ta-table w-full min-w-[520px]">
                    <thead>
                        <tr>
                            <th>{{ __('ui.fields.name') }}</th>
                            <th>{{ __('ui.fields.role') }}</th>
                            <th>{{ __('ui.profile.principal') }}</th>
                            <th>{{ __('ui.fields.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentUsers as $user)
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                </td>
                                <td>
                                    <span class="inline-flex flex-wrap gap-1">
                                        @forelse ($user->roles as $role)
                                            <span class="ta-badge-brand">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </span>
                                </td>
                                <td>{{ $user->principal?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $user->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-400">{{ __('ui.dashboard.no_users') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if ($canViewPrincipals)
        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.profile.principal') }}</h2>
                    <p class="text-xs text-gray-400">{{ __('ui.dashboard.principal_hint') }}</p>
                </div>
            </div>

            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($principals as $principal)
                    <li class="flex items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $principal->name }}</p>
                            <p class="text-xs text-gray-400">{{ $principal->code ?: __('ui.dashboard.no_code') }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="ta-badge-muted">{{ __('ui.dashboard.tenant_count', ['count' => $principal->tenants_count]) }}</span>
                            <span class="ta-badge-brand">{{ __('ui.dashboard.user_count', ['count' => $principal->users_count]) }}</span>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-gray-400">{{ __('ui.dashboard.no_principals') }}</li>
                @endforelse
            </ul>
        </div>
        @endif
    </div>
    @endif
@endsection
