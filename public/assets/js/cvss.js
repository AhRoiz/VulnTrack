/**
 * VulnTrack - Kalkulator CVSS v3.1 sisi klien (real-time).
 * Catatan: hanya untuk tampilan; server menghitung ulang skor final.
 */
(function () {
    'use strict';

    const WEIGHTS = {
        AV: { N: 0.85, A: 0.62, L: 0.55, P: 0.2 },
        AC: { L: 0.77, H: 0.44 },
        PR_U: { N: 0.85, L: 0.62, H: 0.27 },
        PR_C: { N: 0.85, L: 0.68, H: 0.5 },
        UI: { N: 0.85, R: 0.62 },
        CIA: { H: 0.56, L: 0.22, N: 0 },
    };
    const ORDER = ['AV', 'AC', 'PR', 'UI', 'S', 'C', 'I', 'A'];
    const SEV_CLASS = { Critical: 'sev-critical', High: 'sev-high', Medium: 'sev-medium', Low: 'sev-low', None: 'sev-none' };

    /** Roundup sesuai Appendix A spesifikasi CVSS v3.1 (menghindari error floating point). */
    function roundUp(value) {
        const int = Math.round(value * 100000);
        return int % 10000 === 0 ? int / 100000 : (Math.floor(int / 10000) + 1) / 10;
    }

    function calculate(m) {
        const changed = m.S === 'C';
        const iss = 1 - (1 - WEIGHTS.CIA[m.C]) * (1 - WEIGHTS.CIA[m.I]) * (1 - WEIGHTS.CIA[m.A]);
        const impact = changed ? 7.52 * (iss - 0.029) - 3.25 * Math.pow(iss - 0.02, 15) : 6.42 * iss;
        const pr = (changed ? WEIGHTS.PR_C : WEIGHTS.PR_U)[m.PR];
        const exploitability = 8.22 * WEIGHTS.AV[m.AV] * WEIGHTS.AC[m.AC] * pr * WEIGHTS.UI[m.UI];

        if (impact <= 0) return 0;
        return changed
            ? roundUp(Math.min(1.08 * (impact + exploitability), 10))
            : roundUp(Math.min(impact + exploitability, 10));
    }

    function severity(score) {
        if (score >= 9.0) return 'Critical';
        if (score >= 7.0) return 'High';
        if (score >= 4.0) return 'Medium';
        if (score > 0) return 'Low';
        return 'None';
    }

    function readMetrics() {
        const m = {};
        for (const key of ORDER) {
            const el = document.querySelector('input.js-cvss[data-metric="' + key + '"]:checked');
            if (!el) return null;
            m[key] = el.value;
        }
        return m;
    }

    function formatDeadline(hours) {
        const d = new Date(Date.now() + hours * 3600 * 1000);
        const label = hours >= 48 ? (hours / 24) + ' hari' : hours + ' jam';
        return label + ' (' + d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ')';
    }

    const slaHours = JSON.parse(document.getElementById('sla-config')?.textContent || '{}');
    const els = {
        score: document.getElementById('cvssScore'),
        circle: document.getElementById('scoreCircle'),
        sev: document.getElementById('cvssSeverity'),
        sla: document.getElementById('cvssSla'),
        vector: document.getElementById('cvssVector'),
        submit: document.getElementById('submitBtn'),
    };

    function render() {
        const m = readMetrics();
        if (!m) return;

        const score = calculate(m);
        const sev = severity(score);
        const cls = SEV_CLASS[sev];

        els.score.textContent = score.toFixed(1);
        els.sev.textContent = sev;
        els.sev.className = 'badge fs-6 ' + cls;
        els.circle.className = 'vt-score-circle ' + cls;
        els.vector.textContent = 'CVSS:3.1/' + ORDER.map((k) => k + ':' + m[k]).join('/');
        els.sla.textContent = slaHours[sev] ? formatDeadline(slaHours[sev]) : 'Tidak valid — pilih dampak C/I/A';
        els.submit.disabled = score <= 0;
    }

    document.querySelectorAll('input.js-cvss').forEach((el) => el.addEventListener('change', render));
    render();

    // Expose untuk pengujian di console
    window.VulnTrackCvss = { calculate, severity, roundUp };
})();

