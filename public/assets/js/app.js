(function () {
    'use strict';

    const wrapper = document.getElementById('appWrapper');
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const desktopBtn = document.getElementById('desktopSidebarBtn');
    const desktopMedia = window.matchMedia('(min-width: 992px)');

    function setMobileDrawer(open) {
        if (!wrapper || !backdrop || !mobileBtn) return;
        wrapper.classList.toggle('is-drawer-open', open);
        backdrop.hidden = !open;
        mobileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('vt-drawer-lock', open);

        if (open) {
            const firstLink = sidebar?.querySelector('button, a[href]');
            firstLink?.focus();
        }
    }

    if (wrapper && desktopBtn) {
        try {
            if (window.localStorage.getItem('vt_sidebar_collapsed') === '1') {
                wrapper.classList.add('is-sidebar-collapsed');
                desktopBtn.setAttribute('aria-expanded', 'false');
            }
        } catch (_) {
            // Storage unavailable in restricted browser contexts.
        }

        desktopBtn.addEventListener('click', () => {
            const collapsed = wrapper.classList.toggle('is-sidebar-collapsed');
            desktopBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            try {
                window.localStorage.setItem('vt_sidebar_collapsed', collapsed ? '1' : '0');
            } catch (_) {
                // Storage unavailable in restricted browser contexts.
            }
        });
    }

    mobileBtn?.addEventListener('click', () => {
        const isOpen = wrapper?.classList.contains('is-drawer-open');
        setMobileDrawer(!isOpen);
    });

    closeBtn?.addEventListener('click', () => {
        setMobileDrawer(false);
        mobileBtn?.focus();
    });

    backdrop?.addEventListener('click', () => {
        setMobileDrawer(false);
        mobileBtn?.focus();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && wrapper?.classList.contains('is-drawer-open')) {
            setMobileDrawer(false);
            mobileBtn?.focus();
        }
    });

    desktopMedia.addEventListener('change', (e) => {
        if (e.matches && wrapper?.classList.contains('is-drawer-open')) {
            setMobileDrawer(false);
        }
    });

    if (window.bootstrap) {
        document.querySelectorAll('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el).show());
        document.querySelectorAll('.modal[data-open-on-load="1"]').forEach((el) => bootstrap.Modal.getOrCreateInstance(el).show());
        document.querySelectorAll('.badge[title]').forEach((el) => new bootstrap.Tooltip(el));
    }

    if (window.DataTable) {
        document.querySelectorAll('table.js-datatable').forEach((table) => {
            const col = parseInt(table.dataset.orderCol || '0', 10);
            const dir = table.dataset.orderDir || 'desc';
            const dt = new DataTable(table, {
                order: [[col, dir]],
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: 'Cari cepat:',
                    searchPlaceholder: 'Ketik kata kunci...',
                    lengthMenu: 'Tampilkan _MENU_ baris',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    zeroRecords: 'Tidak ada baris yang cocok dengan kata kunci pencarian.',
                    emptyTable: 'Belum ada data tercatat.',
                    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' },
                },
            });
            const container = dt.table().container();
            const searchInput = container?.querySelector('.dt-search input');
            if (searchInput && !searchInput.getAttribute('aria-label')) {
                searchInput.setAttribute('aria-label', 'Cari cepat di tabel');
            }
        });
    }

    document.querySelectorAll('form.js-confirm').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            const btn = form.querySelector('button[type="submit"], button:not([type])');
            if (btn) {
                setTimeout(() => {
                    btn.disabled = true;
                    btn.setAttribute('aria-busy', 'true');
                    if ((form.method || '').toLowerCase() === 'post') {
                        btn.dataset.originalHtml = btn.innerHTML;
                        btn.textContent = 'Memproses...';
                    }
                }, 0);
            }
        });
    });
})();
