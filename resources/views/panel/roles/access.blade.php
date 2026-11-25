@extends('layouts.tailadmin')

@section('title', __('ui.roles.access'))
@section('subtitle', $role->displayName())

@section('content')
    <form method="POST" action="{{ route('panel.roles.saveAccess', $role) }}">
        @csrf

        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('ui.roles.access') }}: {{ $role->displayName() }}
                    </h2>
                    <p class="text-xs text-gray-400">{{ __('ui.roles.access_hint') }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('panel.roles.index') }}" class="ta-btn-ghost !py-2 !text-xs">
                        <x-panel-icon name="arrow-left" class="h-4 w-4" />
                        {{ __('ui.actions.back') }}
                    </a>
                    <button type="submit" class="ta-btn-primary !py-2 !text-xs">
                        <x-panel-icon name="check" class="h-4 w-4" />
                        {{ __('ui.actions.save') }}
                    </button>
                </div>
            </div>

            @if ($role->name === \App\Support\Acl::SUPER_ADMIN_ROLE)
                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <x-panel-icon name="information-circle" class="h-4 w-4" />
                    {{ __('ui.roles.super_admin_hint') }}
                </div>
            @endif

            <div class="p-5">
                <div class="overflow-x-auto">
                    <table class="ta-table w-full min-w-[760px]">
                        <thead>
                            <tr>
                                <th class="text-start">{{ __('ui.roles.name') }}</th>
                                @foreach ($abilities as $ability => $column)
                                    <th class="w-20 text-center">
                                        <label class="inline-flex cursor-pointer items-center gap-1 text-[11px] font-semibold uppercase">
                                            <input type="checkbox" data-column-toggle="{{ $ability }}"
                                                   class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                                            {{ __('ui.roles.abilities.'.$ability) }}
                                        </label>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($menus as $menu)
                                @php($row = $granted[$menu['id']] ?? null)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2" style="padding-inline-start: {{ $menu['depth'] * 1.25 }}rem">
                                            <x-panel-icon :name="$menu['icon']" class="h-4 w-4 shrink-0 text-gray-400" />
                                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $menu['title'] }}</span>
                                            @if ($menu['type'] === \App\Models\Menu::TYPE_GROUP)
                                                <span class="ta-badge-muted">{{ __('ui.menu_builder.types.group') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    @foreach ($abilities as $ability => $column)
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="access[{{ $menu['id'] }}][{{ $ability }}]"
                                                   value="1"
                                                   data-column="{{ $ability }}"
                                                   @checked((bool) ($row?->{$column}))
                                                   class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-sm text-gray-400">{{ __('ui.menu_builder.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-column-toggle]').forEach((toggle) => {
            const ability = toggle.dataset.columnToggle;
            toggle.addEventListener('change', () => {
                document.querySelectorAll(`[data-column="${ability}"]`).forEach((box) => {
                    box.checked = toggle.checked;
                });
            });
        });
    })();
</script>
@endpush
