import './bootstrap';
import DataTable from 'datatables.net';

/* --------------------------------------------------------------------------
 * Dark mode
 * ------------------------------------------------------------------------ */
const THEME_KEY = 'tailadmin-theme';

function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    localStorage.setItem(THEME_KEY, theme);
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', theme === 'dark');
    });
    document.querySelectorAll('[data-theme-icon="light"]').forEach((el) => el.classList.toggle('hidden', theme === 'dark'));
    document.querySelectorAll('[data-theme-icon="dark"]').forEach((el) => el.classList.toggle('hidden', theme !== 'dark'));
}

function initTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(stored ?? (prefersDark ? 'dark' : 'light'));

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
        });
    });
}

/* --------------------------------------------------------------------------
 * Mobile sidebar + dropdowns
 * ------------------------------------------------------------------------ */
function initShell() {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    const open = () => {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
    };
    const close = () => {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((el) => el.addEventListener('click', () => {
        backdrop?.classList.contains('hidden') ? open() : close();
    }));
    backdrop?.addEventListener('click', close);

    document.querySelectorAll('[data-dropdown-toggle]').forEach((toggle) => {
        const menu = document.getElementById(toggle.dataset.dropdownToggle);
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            menu?.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown-menu]').forEach((menu) => menu.classList.add('hidden'));
    });
}

/* --------------------------------------------------------------------------
 * Toasts
 * ------------------------------------------------------------------------ */
function toast(message, type = 'success') {
    const palette = {
        success: 'bg-success-500',
        error: 'bg-error-500',
        info: 'bg-brand-500',
    };
    const el = document.createElement('div');
    el.className = `flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg ${palette[type] ?? palette.info}`;
    el.textContent = message;
    document.getElementById('ta-toasts')?.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

/* --------------------------------------------------------------------------
 * DataTables (server-side)
 * ------------------------------------------------------------------------ */
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function collectFilters() {
    const filters = {};
    document.querySelectorAll('[data-filter]').forEach((el) => {
        if (el.value !== '') {
            filters[el.dataset.filter] = el.value;
        }
    });
    return filters;
}

function buildExportUrl(base, table, format) {
    const params = new URLSearchParams();
    Object.entries(collectFilters()).forEach(([key, value]) => params.append(`filters[${key}]`, value));
    if (table.search()) {
        params.append('search', table.search());
    }
    const separator = base.includes('?') ? '&' : '?';
    return base.replace('__FORMAT__', format) + (params.toString() ? separator + params.toString() : '');
}

function initDataTables() {
    document.querySelectorAll('[data-datatable]').forEach((el) => {
        const columns = JSON.parse(el.querySelector('[data-dt-columns]')?.textContent ?? '[]');

        const table = new DataTable(el.querySelector('table'), {
            serverSide: true,
            processing: true,
            autoWidth: false,
            order: JSON.parse(el.dataset.order ?? '[]'),
            pageLength: Number(el.dataset.pageLength ?? 10),
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: el.dataset.endpoint,
                data: (data) => Object.assign(data, { filters: collectFilters() }),
            },
            columns: columns.map((column) => ({
                data: column.data,
                title: column.title ?? '',
                orderable: column.orderable ?? false,
                searchable: false,
                className: column.className ?? '',
            })),
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            language: {
                search: '',
                searchPlaceholder: 'Cari…',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                zeroRecords: 'Data tidak ditemukan',
                emptyTable: 'Belum ada data',
                processing: 'Memuat…',
                paginate: { first: '«', previous: '‹', next: '›', last: '»' },
            },
        });

        el._dtInstance = table;

        document.querySelectorAll('[data-export]').forEach((button) => {
            button.addEventListener('click', () => {
                window.location.href = buildExportUrl(el.dataset.exportBase, table, button.dataset.export);
            });
        });
    });

    document.querySelectorAll('[data-filter]').forEach((el) => {
        el.addEventListener('change', () => {
            document.querySelectorAll('[data-datatable]').forEach((t) => t._dtInstance?.ajax.reload());
        });
    });

    document.querySelectorAll('[data-filter-reset]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-filter]').forEach((el) => {
                el.value = '';
            });
            document.querySelectorAll('[data-datatable]').forEach((t) => t._dtInstance?.ajax.reload());
        });
    });
}

/* --------------------------------------------------------------------------
 * Row actions (delete via AJAX)
 * ------------------------------------------------------------------------ */
function initRowActions() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-delete-url]');
        if (!button) {
            return;
        }

        event.preventDefault();

        if (!window.confirm(button.dataset.confirm ?? 'Hapus data ini?')) {
            return;
        }

        try {
            const response = await fetch(button.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message ?? 'Gagal menghapus data.');
            }

            toast('Data berhasil dihapus.');
            document.querySelectorAll('[data-datatable]').forEach((el) => el._dtInstance?.ajax.reload(null, false));
        } catch (error) {
            toast(error.message, 'error');
        }
    });
}

/* --------------------------------------------------------------------------
 * Icon picker preview
 * ------------------------------------------------------------------------ */
function initIconPickers() {
    document.querySelectorAll('[data-icon-picker]').forEach((picker) => {
        const select = picker.querySelector('[data-icon-select]');
        const preview = picker.querySelector('[data-icon-preview]');
        if (!select || !preview) {
            return;
        }

        let icons = {};
        try {
            icons = JSON.parse(picker.querySelector('[data-icon-map]')?.textContent ?? '{}');
        } catch (error) {
            icons = {};
        }

        const render = () => {
            const svg = icons[select.value];
            preview.innerHTML = svg ?? icons['bars-3'] ?? '';
            preview.classList.toggle('opacity-40', !svg);
        };

        select.addEventListener('change', render);
        render();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initShell();
    initDataTables();
    initRowActions();
    initIconPickers();
});

window.tailadminToast = toast;
