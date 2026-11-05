/**
 * VulnTrack - Script global: DataTables, toast, konfirmasi aksi, modal auto-open.
 */
(function () {
    'use strict';

    // Toast flash message
    document.querySelectorAll('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el).show());

    // DataTables untuk semua tabel bertanda .js-datatable
    if (window.DataTable) {
        document.querySelectorAll('table.js-datatable').forEach((table) => {
            const col = parseInt(table.dataset.orderCol || '0', 10);
            const dir = table.dataset.orderDir || 'desc';
            new DataTable(table, {
                order: [[col, dir]],
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: '',
                    searchPlaceholder: 'Cari...',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(difilter dari _MAX_ total)',
                    zeroRecords: 'Tidak ada data yang cocok',
                    emptyTable: 'Belum ada data',
                    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                },
            });
        });
    }

    // Konfirmasi sebelum submit form sensitif
    document.querySelectorAll('form.js-confirm').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan?')) e.preventDefault();
        });
    });

    // Cegah double-submit
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            const btn = form.querySelector('button[type="submit"], button:not([type])');
            if (btn) {
                setTimeout(() => {
                    btn.disabled = true;
                }, 0);
            }
        });
    });

    // Buka modal otomatis jika ada error validasi
    document.querySelectorAll('.modal[data-open-on-load="1"]').forEach((el) => bootstrap.Modal.getOrCreateInstance(el).show());

    // Tooltip
    document.querySelectorAll('[title]').forEach((el) => {
        if (el.classList.contains('badge')) new bootstrap.Tooltip(el);
    });
})();

