@extends('layouts.tailadmin')

@section('title', __('ui.roles.title'))
@section('subtitle', __('ui.roles.subtitle'))

@section('content')
    <div class="ta-card">
        <div class="ta-card-header">
            <div>
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.roles.title') }}</h2>
                <p class="text-xs text-gray-400">{{ __('ui.roles.subtitle') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <input type="text" data-role-search class="ta-input !py-2 !ps-9 !text-xs" placeholder="{{ __('ui.actions.search') }}…">
                    <x-panel-icon name="magnifying-glass" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-gray-400" />
                </div>
                <button type="button" data-role-open class="ta-btn-primary !py-2 !text-xs">
                    <x-panel-icon name="plus" class="h-4 w-4" />
                    {{ __('ui.roles.add') }}
                </button>
            </div>
        </div>

        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="ta-table w-full min-w-[900px]" data-role-table>
                    <thead>
                        <tr>
                            <th class="text-start">{{ __('ui.roles.name') }}</th>
                            <th class="text-start">{{ __('ui.roles.scope') }}</th>
                            <th class="text-start">{{ __('ui.roles.description') }}</th>
                            <th class="text-center">{{ __('ui.roles.users') }}</th>
                            <th class="text-center">{{ __('ui.roles.access') }}</th>
                            <th class="text-end">{{ __('ui.roles.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr data-role-row>
                                <td>
                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $role->displayName() }}</div>
                                    <div class="font-mono text-xs text-gray-400">{{ $role->name }}</div>
                                </td>
                                <td>
                                    <span class="{{ $role->scope === \App\Models\Role::SCOPE_TENANT ? 'ta-badge-warning' : 'ta-badge-brand' }}">
                                        {{ $role->scopeLabel() }}
                                    </span>
                                </td>
                                <td>{{ \App\Support\Panel\Html::text($role->description) }}</td>
                                <td class="text-center">{{ \App\Support\Panel\Html::count($role->users_count) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('panel.roles.access', $role) }}"
                                       class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                        <x-panel-icon name="key" class="h-3.5 w-3.5" />
                                        {{ (int) $role->access_count }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('panel.roles.edit', $role) }}" title="{{ __('ui.actions.edit') }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5">
                                            <x-panel-icon name="pencil-square" class="h-4 w-4" />
                                        </a>
                                        @if ($role->name !== \App\Support\Acl::SUPER_ADMIN_ROLE)
                                            <form method="POST" action="{{ route('panel.roles.destroy', $role) }}"
                                                  onsubmit="return confirm('{{ __('ui.roles.confirm_delete') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="{{ __('ui.actions.delete') }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10">
                                                    <x-panel-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-gray-400">{{ __('ui.roles.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div data-role-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.roles.add') }}</h3>
                <button type="button" data-role-close class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                    <x-panel-icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('panel.roles.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="ta-label">{{ __('ui.roles.name') }} <span class="text-error-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="ta-input" placeholder="operator">
                    <p class="ta-help">Huruf kecil dan underscore, contoh: operator.</p>
                    @error('name')
                        <p class="ta-help !text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="ta-label">Label</label>
                    <input type="text" name="label" value="{{ old('label') }}" class="ta-input" placeholder="Operator">
                </div>

                <div>
                    <label class="ta-label">{{ __('ui.roles.scope') }} <span class="text-error-500">*</span></label>
                    <select name="scope" class="ta-select">
                        @foreach ($scopeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('scope') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="ta-label">{{ __('ui.roles.description') }}</label>
                    <textarea name="description" rows="2" class="ta-input" placeholder="{{ __('ui.roles.description') }}">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" data-role-close class="ta-btn-ghost !py-2 !text-xs">{{ __('ui.actions.cancel') }}</button>
                    <button type="submit" class="ta-btn-primary !py-2 !text-xs">{{ __('ui.actions.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const modal = document.querySelector('[data-role-modal]');
        const open = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
        const close = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

        document.querySelector('[data-role-open]')?.addEventListener('click', open);
        document.querySelectorAll('[data-role-close]').forEach((el) => el.addEventListener('click', close));
        modal?.addEventListener('click', (event) => { if (event.target === modal) close(); });

        const search = document.querySelector('[data-role-search]');
        search?.addEventListener('input', () => {
            const term = search.value.trim().toLowerCase();
            document.querySelectorAll('[data-role-row]').forEach((row) => {
                row.classList.toggle('hidden', term !== '' && !row.textContent.toLowerCase().includes(term));
            });
        });

        @if ($errors->any())
            open();
        @endif
    })();
</script>
@endpush
