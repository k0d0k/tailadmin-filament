@extends('layouts.tailadmin')

@section('title', 'CRUD Generator')
@section('subtitle', 'Buat modul CRUD baru langsung dari panel TailAdmin')

@php($oldFields = old('fields', []))

@section('content')
    @if (session('error'))
        <div class="mb-5 flex items-start gap-2 rounded-xl border border-error-500/20 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-500">
            <x-panel-icon name="exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('panel.generator.store') }}" class="mx-auto max-w-5xl space-y-6">
        @csrf

        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Modul</h2>
                    <p class="text-xs text-gray-400">Generator membuat Model, migration, Policy, permission, dan modul panel secara otomatis.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label for="gen-model" class="ta-label">Nama Model <span class="text-error-500">*</span></label>
                    <input type="text" id="gen-model" name="model" value="{{ old('model') }}"
                           class="ta-input @error('model') !border-error-500 @enderror" placeholder="Product">
                    @error('model')
                        <p class="ta-help !text-error-500">{{ $message }}</p>
                    @else
                        <p class="ta-help">Gunakan bentuk tunggal PascalCase, contoh: Product.</p>
                    @enderror
                </div>

                <div>
                    <label for="gen-label" class="ta-label">Label Menu</label>
                    <input type="text" id="gen-label" name="label" value="{{ old('label') }}" class="ta-input" placeholder="Produk">
                    <p class="ta-help">Kosongkan untuk otomatis dari nama model.</p>
                </div>

                <div>
                    <label for="gen-group" class="ta-label">Grup Menu</label>
                    <input type="text" id="gen-group" name="navigation_group" value="{{ old('navigation_group') }}" class="ta-input" placeholder="Master Data">
                    <p class="ta-help">Menaruh menu di bawah grup, contoh: Master Data.</p>
                </div>

                <div>
                    <label for="gen-description" class="ta-label">Deskripsi</label>
                    <input type="text" id="gen-description" name="description" value="{{ old('description') }}" class="ta-input" placeholder="Deskripsi singkat modul">
                </div>

                <div>
                    <label for="gen-icon" class="ta-label">Ikon</label>
                    <div class="flex items-center gap-3" data-icon-picker>
                        <span data-icon-preview
                              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-white/5 dark:text-gray-200">
                            {!! \App\Support\Panel\IconLibrary::render(old('icon') ?: 'table-cells', 'h-6 w-6') !!}
                        </span>
                        <select id="gen-icon" name="icon" data-icon-select class="ta-select">
                            <option value="">— Pilih —</option>
                            @foreach ($icons as $value => $label)
                                <option value="{{ $value }}" @selected(old('icon') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <script type="application/json" data-icon-map>@json($iconMap)</script>
                </div>
            </div>
        </div>

        <div class="ta-card">
            <div class="ta-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Field</h2>
                    <p class="text-xs text-gray-400">Definisikan kolom yang akan dibuat untuk modul ini.</p>
                </div>
                <button type="button" data-add-field class="ta-btn-ghost !py-2 !text-xs">
                    <x-panel-icon name="plus" class="h-4 w-4" />
                    Tambah Field
                </button>
            </div>

            <div class="space-y-4 p-5" data-fields data-existing="{{ json_encode($oldFields) }}"></div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="ta-btn-primary">
                <x-panel-icon name="sparkles" class="h-4 w-4" />
                Generate Modul
            </button>
        </div>
    </form>

    <template id="field-row-template">
        <div data-field-row class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="ta-label">Nama Kolom <span class="text-error-500">*</span></label>
                    <input type="text" name="fields[__IDX__][name]" class="ta-input" placeholder="product_name">
                </div>
                <div>
                    <label class="ta-label">Tipe</label>
                    <select name="fields[__IDX__][type]" class="ta-select">
                        @foreach ($fieldTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ta-label">Label</label>
                    <input type="text" name="fields[__IDX__][label]" class="ta-input" placeholder="Product Name">
                </div>
                <div>
                    <label class="ta-label">Opsi Dropdown</label>
                    <input type="text" name="fields[__IDX__][options]" class="ta-input" placeholder="draft, published">
                </div>
                <div>
                    <label class="ta-label">Nilai Default</label>
                    <input type="text" name="fields[__IDX__][default]" class="ta-input">
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-5">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="hidden" name="fields[__IDX__][nullable]" value="0">
                    <input type="checkbox" name="fields[__IDX__][nullable]" value="1"
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                    Nullable
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="hidden" name="fields[__IDX__][in_form]" value="0">
                    <input type="checkbox" name="fields[__IDX__][in_form]" value="1" checked
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                    Tampil di Form
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="hidden" name="fields[__IDX__][in_table]" value="0">
                    <input type="checkbox" name="fields[__IDX__][in_table]" value="1" checked
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-900">
                    Tampil di Tabel
                </label>

                <button type="button" data-remove-field class="ta-btn-ghost ml-auto !py-2 !text-xs !text-error-600">
                    <x-panel-icon name="trash" class="h-4 w-4" />
                    Hapus
                </button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
<script>
    (function () {
        const container = document.querySelector('[data-fields]');
        const template = document.getElementById('field-row-template');
        const addButton = document.querySelector('[data-add-field]');
        if (!container || !template) return;

        let counter = 0;

        const addRow = (data = {}) => {
            const holder = document.createElement('div');
            holder.innerHTML = template.innerHTML.replaceAll('__IDX__', String(counter++)).trim();
            const row = holder.firstElementChild;

            if (data.name) row.querySelector('[name$="[name]"]').value = data.name;
            if (data.label) row.querySelector('[name$="[label]"]').value = data.label;
            if (data.options) row.querySelector('[name$="[options]"]').value = data.options;
            if (data.default) row.querySelector('[name$="[default]"]').value = data.default;

            if (data.type) {
                const select = row.querySelector('select');
                if (select) select.value = data.type;
            }

            row.querySelectorAll('input[type="checkbox"]').forEach((box) => {
                const key = box.name.match(/\[(\w+)\]$/)?.[1];
                if (key && key in data) {
                    box.checked = ['1', 'true', 1, true].includes(data[key]);
                }
            });

            row.querySelector('[data-remove-field]')?.addEventListener('click', () => {
                if (container.querySelectorAll('[data-field-row]').length > 1) {
                    row.remove();
                }
            });

            container.appendChild(row);
        };

        let existing = [];
        try { existing = JSON.parse(container.dataset.existing || '[]'); } catch (error) { existing = []; }
        if (!Array.isArray(existing) || existing.length === 0) existing = [{}];
        existing.forEach((field) => addRow(field));

        addButton?.addEventListener('click', () => addRow({}));
    })();
</script>
@endpush
