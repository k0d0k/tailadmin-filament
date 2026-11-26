import './bootstrap';
import DataTable from 'datatables.net';

/* --------------------------------------------------------------------------
 * Shared configuration injected by the layout.
 * ------------------------------------------------------------------------ */
const CONFIG = window.__TA__ || {};
const STORAGE = {
    mode: 'tailadmin-mode',
    skin: 'tailadmin-skin',
    sidebar: 'tailadmin-sidebar',
};

const store = {
    get(key) {
        try {
            return localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (error) {
            /* storage unavailable */
        }
    },
};

/* --------------------------------------------------------------------------
 * Colour mode: light / dark / system
 * ------------------------------------------------------------------------ */
const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

function currentMode() {
    return store.get(STORAGE.mode) || CONFIG.defaultMode || 'system';
}

function isDark(mode) {
    return mode === 'dark' || (mode === 'system' && prefersDark());
}

function applyMode(mode) {
    const dark = isDark(mode);

    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.mode = mode;
    store.set(STORAGE.mode, mode);

    document.querySelectorAll('[data-set-mode]').forEach((button) => {
        const active = button.dataset.setMode === mode;
        button.dataset.active = active ? 'true' : 'false';
        button.setAttribute('aria-checked', active ? 'true' : 'false');
    });

    document.querySelectorAll('[data-mode-label]').forEach((el) => {
        el.textContent = CONFIG.modes?.[mode] ?? mode;
    });

    document.querySelectorAll('[data-mode-icon]').forEach((el) => {
        el.classList.toggle('hidden', el.dataset.modeIcon !== (dark ? 'dark' : 'light'));
    });
}

function initMode() {
    applyMode(currentMode());

    document.querySelectorAll('[data-set-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            applyMode(button.dataset.setMode);
            closeDropdowns();
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (currentMode() === 'system') {
            applyMode('system');
        }
    });
}

/* --------------------------------------------------------------------------
 * Skin: runtime brand palette
 * ------------------------------------------------------------------------ */
function applySkin(skin) {
    document.documentElement.dataset.skin = skin;
    store.set(STORAGE.skin, skin);

    document.querySelectorAll('[data-set-skin]').forEach((button) => {
        const active = button.dataset.setSkin === skin;
        button.dataset.active = active ? 'true' : 'false';
        button.setAttribute('aria-checked', active ? 'true' : 'false');
    });
}

function initSkin() {
    applySkin(store.get(STORAGE.skin) || CONFIG.defaultSkin || 'indigo');

    document.querySelectorAll('[data-set-skin]').forEach((button) => {
        button.addEventListener('click', () => {
            applySkin(button.dataset.setSkin);
            closeDropdowns();
        });
    });
}

/* --------------------------------------------------------------------------
 * Mobile sidebar + dropdowns
 * ------------------------------------------------------------------------ */
function closeDropdowns() {
    document.querySelectorAll('[data-dropdown-menu]').forEach((menu) => menu.classList.add('hidden'));
}

function initShell() {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    const open = () => {
        sidebar?.setAttribute('data-open', '');
        backdrop?.classList.remove('hidden');
    };
    const close = () => {
        sidebar?.removeAttribute('data-open');
        backdrop?.classList.add('hidden');
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((el) => el.addEventListener('click', () => {
        sidebar?.hasAttribute('data-open') ? close() : open();
    }));
    backdrop?.addEventListener('click', close);

    document.querySelectorAll('[data-dropdown-toggle]').forEach((toggle) => {
        const menu = document.getElementById(toggle.dataset.dropdownToggle);
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = menu?.classList.contains('hidden');
            closeDropdowns();
            if (willOpen) {
                menu?.classList.remove('hidden');
            }
        });
    });

    document.addEventListener('click', () => closeDropdowns());
}

/* --------------------------------------------------------------------------
 * Sidebar menu group filter (choose a group instead of collapsing menus)
 * ------------------------------------------------------------------------ */
function initMenuGroups() {
    const select = document.querySelector('[data-menu-group]');
    if (!select) {
        return;
    }

    const sections = document.querySelectorAll('[data-menu-section]');

    const apply = () => {
        const value = select.value;
        sections.forEach((section) => {
            section.classList.toggle('hidden', value !== '' && section.dataset.menuSection !== value);
        });
    };

    select.addEventListener('change', apply);
    apply();
}

/* --------------------------------------------------------------------------
 * Desktop sidebar collapse
 * ------------------------------------------------------------------------ */
function applySidebarCollapsed(collapsed) {
    document.documentElement.toggleAttribute('data-sidebar-collapsed', collapsed);
    store.set(STORAGE.sidebar, collapsed ? 'collapsed' : 'expanded');

    document.querySelectorAll('[data-sidebar-collapse]').forEach((button) => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute('title', collapsed
            ? (CONFIG.labels?.showMenu ?? 'Show menu')
            : (CONFIG.labels?.hideMenu ?? 'Hide menu'));
    });
    document.querySelectorAll('[data-collapse-icon="expanded"]').forEach((el) => el.classList.toggle('hidden', collapsed));
    document.querySelectorAll('[data-collapse-icon="collapsed"]').forEach((el) => el.classList.toggle('hidden', !collapsed));
}

function initSidebarCollapse() {
    applySidebarCollapsed(store.get(STORAGE.sidebar) === 'collapsed');

    document.querySelectorAll('[data-sidebar-collapse]').forEach((button) => {
        button.addEventListener('click', () => {
            applySidebarCollapsed(!document.documentElement.hasAttribute('data-sidebar-collapsed'));
        });
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
    return base
        .replace('__FORMAT__', format)
        .replace('__SEARCH__', encodeURIComponent(table.search() ?? '')) +
        (params.toString() ? separator + params.toString() : '');
}

function initDataTables() {
    const dtLang = CONFIG.datatable ?? {};

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
                searchPlaceholder: dtLang.placeholder ?? 'Search…',
                lengthMenu: dtLang.lengthMenu ?? 'Show _MENU_ entries',
                info: dtLang.info ?? 'Showing _START_–_END_ of _TOTAL_ entries',
                infoEmpty: dtLang.infoEmpty ?? 'No entries',
                infoFiltered: dtLang.infoFiltered ?? '(filtered from _MAX_ total entries)',
                zeroRecords: dtLang.zeroRecords ?? 'No matching records found',
                emptyTable: dtLang.emptyTable ?? 'No data available',
                processing: dtLang.processing ?? 'Loading…',
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

        if (!window.confirm(button.dataset.confirm ?? CONFIG.labels?.confirmDelete ?? 'Delete?')) {
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
                throw new Error(payload.message ?? 'Delete failed.');
            }

            toast(CONFIG.labels?.deleted ?? 'Deleted.', 'success');
            document.querySelectorAll('[data-datatable]').forEach((el) => el._dtInstance?.ajax.reload(null, false));
        } catch (error) {
            toast(error.message, 'error');
        }
    });
}

/* --------------------------------------------------------------------------
 * Icon picker (popover grid of inline SVGs)
 * ------------------------------------------------------------------------ */
function initIconPickers(root = document) {
    root.querySelectorAll('[data-icon-picker]').forEach((picker) => {
        if (picker.dataset.iconReady === '1') {
            return;
        }
        picker.dataset.iconReady = '1';

        const input = picker.querySelector('[data-icon-input]');
        const preview = picker.querySelector('[data-icon-preview]');
        const label = picker.querySelector('[data-icon-label]');
        const toggle = picker.querySelector('[data-icon-toggle]');
        const panel = picker.querySelector('[data-icon-panel]');
        const options = picker.querySelectorAll('[data-icon-option]');

        if (!options.length) {
            return;
        }

        const closePanel = () => panel?.classList.add('hidden');

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            panel?.classList.toggle('hidden');
        });

        options.forEach((option) => {
            option.addEventListener('click', () => {
                const value = option.dataset.iconOption;

                if (input) {
                    input.value = value;
                }
                if (preview) {
                    preview.innerHTML = option.querySelector('svg')?.outerHTML ?? '';
                }
                if (label) {
                    label.textContent = value;
                }

                options.forEach((other) => {
                    const active = other === option;
                    other.classList.toggle('border-brand-500', active);
                    other.classList.toggle('bg-brand-50', active);
                    other.classList.toggle('text-brand-600', active);
                    other.classList.toggle('dark:bg-brand-500/15', active);
                    other.classList.toggle('dark:text-brand-400', active);
                    other.classList.toggle('border-transparent', !active);
                });

                closePanel();
            });
        });

        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target)) {
                closePanel();
            }
        });
    });
}

/* --------------------------------------------------------------------------
 * Nested menu builder (drag & drop, dependency free)
 * ------------------------------------------------------------------------ */
function initMenuBuilder() {
    const tree = document.querySelector('[data-menu-tree]');
    if (!tree) {
        return;
    }

    const template = document.getElementById('menu-node-template');
    const syncUrl = document.querySelector('[data-sync-url]')?.dataset.syncUrl;

    let dragNode = null;
    let dropTarget = null;

    const serializeNode = (node) => {
        const field = (name) => {
            const el = node.querySelector(`[data-field="${name}"]`);
            return el && el.closest('[data-node]') === node ? el : null;
        };
        const children = node.querySelector(':scope > [data-menu-children]');
        const id = node.dataset.id;
        const iconInput = node.querySelector('[data-icon-input]');

        const title = {};
        node.querySelectorAll('[data-field^="title."]').forEach((input) => {
            if (input.closest('[data-node]') !== node) {
                return;
            }
            title[input.dataset.field.slice('title.'.length)] = input.value.trim();
        });

        return {
            id: /^\d+$/.test(id ?? '') ? Number(id) : null,
            title,
            key: field('key')?.value || null,
            type: field('type')?.value || null,
            icon: iconInput && iconInput.closest('[data-node]') === node ? (iconInput.value || null) : null,
            url: field('url')?.value || null,
            route_name: field('route_name')?.value || null,
            module_key: field('module_key')?.value || null,
            resource: field('resource')?.value || null,
            permission: field('permission')?.value || null,
            is_active: !!field('is_active')?.checked,
            is_visible: !!field('is_visible')?.checked,
            target_blank: !!field('target_blank')?.checked,
            children: children
                ? Array.from(children.querySelectorAll(':scope > [data-node]')).map(serializeNode)
                : [],
        };
    };

    const serializeTree = () => Array.from(tree.querySelectorAll(':scope > [data-node]')).map(serializeNode);

    const clearIndicators = () => {
        tree.querySelectorAll('.ta-drop-before, .ta-drop-after, .ta-drop-inside')
            .forEach((el) => el.classList.remove('ta-drop-before', 'ta-drop-after', 'ta-drop-inside'));
    };

    const initIconPicker = (root) => initIconPickers(root);

    const bindNode = (node, isNew) => {
        node.querySelector('[data-toggle-details]')?.addEventListener('click', () => {
            const details = node.querySelector(':scope > [data-node-details]');
            if (!details) {
                return;
            }
            const hidden = details.classList.toggle('hidden');
            details.classList.toggle('grid', !hidden);
        });

        node.querySelector('[data-remove-node]')?.addEventListener('click', () => {
            if (window.confirm(CONFIG.labels?.confirmDelete ?? 'Delete?')) {
                node.remove();
            }
        });

        node.querySelector('[data-add-child]')?.addEventListener('click', () => {
            const container = node.querySelector(':scope > [data-menu-children]');
            if (container) {
                addNode(container);
            }
        });

        if (isNew) {
            initIconPicker(node);
        }
    };

    const addNode = (container) => {
        if (!template) {
            return null;
        }

        const tempId = `tmp-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
        const holder = document.createElement('div');
        holder.innerHTML = template.innerHTML.replaceAll('__ID__', tempId).trim();
        const node = holder.firstElementChild;

        container.appendChild(node);
        bindNode(node, true);
        document.querySelectorAll('[data-empty-hint]').forEach((el) => el.remove());
        node.querySelector('[data-field^="title."]')?.focus();

        return node;
    };

    tree.querySelectorAll('[data-node]').forEach((node) => bindNode(node, false));

    document.querySelector('[data-add-root]')?.addEventListener('click', () => addNode(tree));

    tree.addEventListener('dragstart', (event) => {
        const handle = event.target.closest('[data-node-handle]');
        if (!handle) {
            return;
        }

        dragNode = handle.closest('[data-node]');
        if (!dragNode) {
            return;
        }

        dragNode.classList.add('ta-node-dragging');
        event.dataTransfer.effectAllowed = 'move';
        try {
            event.dataTransfer.setData('text/plain', dragNode.dataset.id ?? '');
            event.dataTransfer.setDragImage(dragNode, 20, 20);
        } catch (error) {
            /* drag image is best effort */
        }
    });

    tree.addEventListener('dragend', () => {
        dragNode?.classList.remove('ta-node-dragging');
        dragNode = null;
        dropTarget = null;
        clearIndicators();
    });

    tree.addEventListener('dragover', (event) => {
        if (!dragNode) {
            return;
        }

        const node = event.target.closest('[data-node]');

        if (!node) {
            event.preventDefault();
            clearIndicators();
            dropTarget = { node: null, position: 'inside-root' };
            tree.classList.add('ta-drop-inside');

            return;
        }

        if (node === dragNode || dragNode.contains(node)) {
            dropTarget = null;
            clearIndicators();

            return;
        }

        event.preventDefault();

        const rect = node.getBoundingClientRect();
        const offset = (event.clientY - rect.top) / rect.height;
        let position = 'inside';

        if (offset < 0.25) {
            position = 'before';
        } else if (offset > 0.75) {
            position = 'after';
        }

        clearIndicators();
        dropTarget = { node, position };
        node.classList.add(position === 'inside' ? 'ta-drop-inside' : `ta-drop-${position}`);
    });

    tree.addEventListener('drop', (event) => {
        if (!dragNode || !dropTarget) {
            return;
        }

        event.preventDefault();
        const { node, position } = dropTarget;

        if (node === null) {
            tree.appendChild(dragNode);
        } else if (node === dragNode || dragNode.contains(node)) {
            /* never move a node into its own subtree */
        } else if (position === 'inside') {
            node.querySelector(':scope > [data-menu-children]')?.appendChild(dragNode);
        } else if (position === 'before') {
            node.parentNode.insertBefore(dragNode, node);
        } else {
            node.parentNode.insertBefore(dragNode, node.nextSibling);
        }

        clearIndicators();
    });

    document.querySelector('[data-save-tree]')?.addEventListener('click', async () => {
        if (!syncUrl) {
            return;
        }

        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken(),
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ tree: serializeTree() }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message ?? 'Save failed.');
            }

            toast(payload.message ?? 'Saved.', 'success');
            window.location.reload();
        } catch (error) {
            toast(error.message, 'error');
        }
    });
}

/* --------------------------------------------------------------------------
 * Image lightbox
 * ------------------------------------------------------------------------ */
function initLightbox() {
    const box = document.querySelector('[data-lightbox]');
    if (!box) {
        return;
    }

    const image = box.querySelector('[data-lightbox-image]');
    const caption = box.querySelector('[data-lightbox-caption]');

    const open = (src, text) => {
        if (!src) {
            return;
        }
        image.src = src;
        caption.textContent = text ?? '';
        box.setAttribute('data-open', '');
    };
    const close = () => {
        box.removeAttribute('data-open');
        image.src = '';
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-lightbox-trigger], main img:not([data-lightbox-image])');
        if (trigger && !box.contains(trigger)) {
            const src = trigger.dataset.src || trigger.getAttribute('src');
            if (!src) {
                return;
            }
            event.preventDefault();
            open(src, trigger.dataset.caption || trigger.getAttribute('alt'));
        }
    });

    box.addEventListener('click', close);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
}

/* --------------------------------------------------------------------------
 * CSV import helper (fills the hidden file name label)
 * ------------------------------------------------------------------------ */
function initImportForms() {
    document.querySelectorAll('[data-import-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const label = input.closest('form')?.querySelector('[data-import-filename]');
            if (label) {
                label.textContent = input.files?.[0]?.name ?? '';
            }
        });
    });
}

/* --------------------------------------------------------------------------
 * CSV import modal (opens/closes and submits through fetch)
 * ------------------------------------------------------------------------ */
function initImportModal() {
    const modal = document.querySelector('[data-import-modal]');
    if (!modal) {
        return;
    }

    const form = modal.querySelector('form');
    const filename = form?.querySelector('[data-import-filename]');

    const open = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };
    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form?.reset();
        if (filename) {
            filename.textContent = '';
        }
    };

    document.querySelectorAll('[data-import-open]').forEach((button) => button.addEventListener('click', open));
    modal.querySelectorAll('[data-import-close]').forEach((button) => button.addEventListener('click', close));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });

    form?.addEventListener('submit', async (event) => {
        if (!form.checkValidity()) {
            return; // let the browser surface the native validation message
        }

        event.preventDefault();

        let response;
        try {
            response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: new FormData(form),
            });
        } catch (error) {
            form.submit(); // network failure: fall back to a regular submission
            return;
        }

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            toast(payload.message ?? form.dataset.importFailed ?? 'Import failed.', 'error');
            return;
        }

        const summary = (form.dataset.importSuccess ?? ':count').replace('__COUNT__', payload.imported ?? 0);
        toast(summary, 'success');
        close();
        document.querySelectorAll('[data-datatable]').forEach((el) => el._dtInstance?.ajax.reload(null, false));
    });
}

/* --------------------------------------------------------------------------
 * Report builder (drag & drop a data source into a report)
 * ------------------------------------------------------------------------ */
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));
}

function initReportBuilder() {
    const root = document.querySelector('[data-report-builder]');
    if (!root) {
        return;
    }

    let sources = {};
    try {
        sources = JSON.parse(root.querySelector('[data-report-sources]')?.textContent ?? '{}');
    } catch (error) {
        sources = {};
    }

    const select = root.querySelector('[data-report-source]');
    const palette = root.querySelector('[data-report-palette]');
    const primaryList = root.querySelector('[data-report-primary]');
    const relationList = root.querySelector('[data-report-relations]');
    const relationsWrap = root.querySelector('[data-report-relations-wrap]');
    const noRelations = root.querySelector('[data-report-no-relations]');
    const chooseHint = root.querySelector('[data-report-choose]');
    const dropzone = root.querySelector('[data-report-dropzone]');
    const emptyHint = root.querySelector('[data-report-empty]');
    const runButton = root.querySelector('[data-report-run]');
    const exportButtons = root.querySelectorAll('[data-report-export]');
    const limitInput = root.querySelector('[data-report-limit]');
    const results = root.querySelector('[data-report-results]');
    const resultsTable = root.querySelector('[data-report-table]');
    const totalEl = root.querySelector('[data-report-total]');
    const runUrl = root.dataset.runUrl;
    const exportBase = root.dataset.exportBase;
    const removeLabel = root.dataset.removeLabel ?? 'Remove';

    let dragSelected = null;

    const selectedKeys = () => Array.from(dropzone.querySelectorAll('[data-selected-key]'))
        .map((el) => el.dataset.selectedKey);

    const updateState = () => {
        const has = selectedKeys().length > 0;
        emptyHint?.classList.toggle('hidden', has);
        if (runButton) runButton.disabled = !has;
        exportButtons.forEach((button) => { button.disabled = !has; });
    };

    const hideResults = () => {
        results?.classList.add('hidden');
        if (resultsTable) resultsTable.innerHTML = '';
    };

    const makeSelectedChip = (field) => {
        const chip = document.createElement('div');
        chip.draggable = true;
        chip.dataset.selectedKey = field.key;
        chip.className = 'ta-report-chip ta-report-drag';
        chip.innerHTML = `<span class="text-gray-400">⠿</span><span>${escapeHtml(field.title)}</span>`;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'ms-1 text-base leading-none text-gray-400 hover:text-error-500';
        remove.setAttribute('aria-label', removeLabel);
        remove.innerHTML = '&times;';
        remove.addEventListener('click', () => {
            chip.remove();
            updateState();
        });
        chip.appendChild(remove);

        chip.addEventListener('dragstart', (event) => {
            dragSelected = chip;
            chip.classList.add('opacity-50');
            event.dataTransfer.effectAllowed = 'move';
            try {
                event.dataTransfer.setData('text/plain', JSON.stringify({ source: 'selected' }));
            } catch (error) {
                /* data transfer is best effort */
            }
        });
        chip.addEventListener('dragend', () => {
            dragSelected = null;
            chip.classList.remove('opacity-50');
        });

        return chip;
    };

    const addSelected = (field) => {
        if (dropzone.querySelector(`[data-selected-key="${field.key}"]`)) {
            return;
        }
        dropzone.appendChild(makeSelectedChip(field));
        updateState();
    };

    const buildPalette = (sourceKey) => {
        primaryList.innerHTML = '';
        relationList.innerHTML = '';

        const source = sources[sourceKey];
        if (!source) {
            return;
        }

        source.fields.forEach((field) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.draggable = true;
            chip.className = 'ta-report-chip ta-report-drag';
            chip.innerHTML = `<span>${escapeHtml(field.title)}</span>`;

            if (field.group === 'relation' && field.relation) {
                const badge = document.createElement('span');
                badge.className = 'rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400';
                badge.textContent = field.relation;
                chip.appendChild(badge);
            }

            chip.addEventListener('click', () => addSelected(field));
            chip.addEventListener('dragstart', (event) => {
                event.dataTransfer.effectAllowed = 'copy';
                try {
                    event.dataTransfer.setData('text/plain', JSON.stringify({ source: 'palette', key: field.key }));
                } catch (error) {
                    /* data transfer is best effort */
                }
            });

            (field.group === 'relation' ? relationList : primaryList).appendChild(chip);
        });

        const hasRelations = source.fields.some((field) => field.group === 'relation');
        relationsWrap?.classList.toggle('hidden', !hasRelations);
        noRelations?.classList.toggle('hidden', hasRelations);
    };

    const chipAfter = (container, y) => container.querySelectorAll('[data-selected-key]:not(.opacity-50)').length
        ? [...container.querySelectorAll('[data-selected-key]:not(.opacity-50)')].reduce((closest, chip) => {
            const box = chip.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            return offset < 0 && offset > closest.offset ? { offset, element: chip } : closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element
        : null;

    const renderResults = (payload) => {
        const head = (payload.columns ?? []).map((column) => `<th>${escapeHtml(column.title)}</th>`).join('');
        const body = (payload.rows ?? []).map((row) => `<tr>${row.map((cell) => `<td>${cell ?? ''}</td>`).join('')}</tr>`).join('');

        resultsTable.innerHTML = `<table class="ta-table w-full min-w-[560px]"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
        if (totalEl) {
            totalEl.textContent = `(${payload.total ?? 0})`;
        }
        results.classList.remove('hidden');
    };

    select?.addEventListener('change', () => {
        dropzone.querySelectorAll('[data-selected-key]').forEach((chip) => chip.remove());
        hideResults();

        if (!select.value) {
            palette.classList.add('hidden');
            chooseHint?.classList.remove('hidden');
            updateState();

            return;
        }

        chooseHint?.classList.add('hidden');
        palette.classList.remove('hidden');
        buildPalette(select.value);
        updateState();
    });

    dropzone.addEventListener('dragover', (event) => {
        if (!dragSelected) {
            return;
        }

        event.preventDefault();
        const after = chipAfter(dropzone, event.clientY);

        if (after === null) {
            dropzone.appendChild(dragSelected);
        } else {
            dropzone.insertBefore(dragSelected, after);
        }
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();

        let payload = {};
        try {
            payload = JSON.parse(event.dataTransfer.getData('text/plain') || '{}');
        } catch (error) {
            payload = {};
        }

        if (payload.source === 'palette' && payload.key) {
            const field = sources[select.value]?.fields.find((item) => item.key === payload.key);
            if (field) {
                addSelected(field);
            }
        }
    });

    runButton?.addEventListener('click', async () => {
        const columns = selectedKeys();
        if (!columns.length) {
            return;
        }

        runButton.disabled = true;

        try {
            const response = await fetch(runUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    source: select.value,
                    columns,
                    limit: Number(limitInput?.value) || 100,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message ?? 'Report failed.');
            }

            renderResults(payload);
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            runButton.disabled = selectedKeys().length === 0;
        }
    });

    exportButtons.forEach((button) => button.addEventListener('click', () => {
        const columns = selectedKeys();
        if (!columns.length) {
            return;
        }

        const params = new URLSearchParams();
        params.set('source', select.value);
        columns.forEach((key) => params.append('columns[]', key));

        window.location.href = exportBase.replace('__FORMAT__', button.dataset.reportExport) + '?' + params.toString();
    }));

    updateState();
}

document.addEventListener('DOMContentLoaded', () => {
    initMode();
    initSkin();
    initShell();
    initMenuGroups();
    initSidebarCollapse();
    initDataTables();
    initRowActions();
    initIconPickers();
    initMenuBuilder();
    initLightbox();
    initImportForms();
    initImportModal();
    initReportBuilder();
});

window.tailadminToast = toast;
