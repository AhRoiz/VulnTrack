/**
 * VulnTrack - Chart dashboard (data di-inject dari PHP via <script type="application/json">).
 */
(function () {
    'use strict';

    const raw = document.getElementById('dashboard-data');
    if (!raw || !window.Chart) return;
    const data = JSON.parse(raw.textContent);

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#64748b';

    const SEVERITY_COLORS = { Critical: '#dc3545', High: '#fd7e14', Medium: '#0d6efd', Low: '#6c757d' };
    const STATUS_COLORS = { Open: '#0ea5e9', Triaged: '#8b5cf6', 'In Remediation': '#f59e0b', 'Re-test': '#ec4899', Closed: '#10b981' };

    const total = data.severity.data.reduce((a, b) => a + b, 0);

    new Chart(document.getElementById('severityChart'), {
        type: 'pie',
        data: {
            labels: data.severity.labels,
            datasets: [{
                data: data.severity.data,
                backgroundColor: data.severity.labels.map((l) => SEVERITY_COLORS[l]),
                borderColor: '#fff',
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const pct = total ? ((ctx.parsed / total) * 100).toFixed(0) : 0;
                            return ` ${ctx.label}: ${ctx.parsed} tiket (${pct}%)`;
                        },
                    },
                },
            },
        },
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: data.status.labels,
            datasets: [{
                label: 'Jumlah Tiket',
                data: data.status.data,
                backgroundColor: data.status.labels.map((l) => STATUS_COLORS[l]),
                borderRadius: 6,
                maxBarThickness: 56,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } },
            },
        },
    });
})();

