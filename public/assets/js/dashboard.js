(function () {
    'use strict';

    const raw = document.getElementById('dashboard-data');
    const sevCanvas = document.getElementById('severityChart');
    const statusCanvas = document.getElementById('statusChart');
    if (!raw || (!sevCanvas && !statusCanvas)) return;

    if (!window.Chart) {
        if (sevCanvas) sevCanvas.hidden = true;
        if (statusCanvas) statusCanvas.hidden = true;
        const sevErr = document.getElementById('severityChartError');
        const statusErr = document.getElementById('statusChartError');
        if (sevErr) sevErr.hidden = false;
        if (statusErr) statusErr.hidden = false;
        return;
    }

    const data = JSON.parse(raw.textContent);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#475569';

    const SEVERITY_COLORS = {
        Critical: '#991b1b',
        High: '#c83713',
        Medium: '#a16207',
        Low: '#475569',
    };

    const STATUS_COLORS = {
        Open: '#64748b',
        Triaged: '#c83713',
        'In Remediation': '#b45309',
        'Re-test': '#0f766e',
        Closed: '#15803d',
    };

    const total = data.severity.data.reduce((a, b) => a + b, 0);

    if (sevCanvas) {
        new Chart(sevCanvas, {
            type: 'doughnut',
            data: {
                labels: data.severity.labels,
                datasets: [{
                    data: data.severity.data,
                    backgroundColor: data.severity.labels.map((l) => SEVERITY_COLORS[l] || '#475569'),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reducedMotion ? false : undefined,
                cutout: '54%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            padding: 16,
                            color: '#1e293b',
                            font: { weight: '600', size: 12 },
                        },
                    },
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
    }

    if (statusCanvas) {
        new Chart(statusCanvas, {
            type: 'bar',
            data: {
                labels: data.status.labels,
                datasets: [{
                    label: 'Jumlah Tiket',
                    data: data.status.data,
                    backgroundColor: data.status.labels.map((l) => STATUS_COLORS[l] || '#475569'),
                    borderRadius: 4,
                    maxBarThickness: 48,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reducedMotion ? false : undefined,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#475569' },
                        grid: { color: '#e2e8f0' },
                    },
                    x: {
                        ticks: { color: '#334155', font: { weight: '500' } },
                        grid: { display: false },
                    },
                },
            },
        });
    }
})();
