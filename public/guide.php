<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

$pageTitle = 'Bantuan & Panduan';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="vt-page-header">
    <h1 class="fw-bold mb-0">Bantuan &amp; Panduan</h1>
</div>

<div class="accordion vt-accordion" id="guideAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingWorkflow">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorkflow" aria-expanded="true" aria-controls="collapseWorkflow">
                1. Alur Remediasi Tiket (State-Machine)
            </button>
        </h2>
        <div id="collapseWorkflow" class="accordion-collapse collapse show" aria-labelledby="headingWorkflow" data-bs-parent="#guideAccordion">
            <div class="accordion-body small">
                <p class="mb-2">Setiap celah keamanan mengikuti urutan status ketat agar tidak ada tiket yang ditutup tanpa pengujian ulang:</p>
                <ol class="mb-2">
                    <li class="mb-1"><strong>Open:</strong> Temuan baru dilaporkan oleh Security Analyst atau Admin beserta skor CVSS v3.1.</li>
                    <li class="mb-1"><strong>Triaged:</strong> Aset terkonfirmasi dan tiket ditugaskan ke Developer penanggung jawab.</li>
                    <li class="mb-1"><strong>In Remediation:</strong> Developer mengerjakan perbaikan kode dan menyertakan ringkasan patch serta tautan PR/commit.</li>
                    <li class="mb-1"><strong>Re-test:</strong> Patch menunggu pengujian ulang oleh Security Analyst atau Admin.</li>
                    <li><strong>Closed:</strong> Verifikasi dinyatakan lulus. Jika gagal, tiket dikembalikan ke <strong>In Remediation</strong>.</li>
                </ol>
                <p class="text-secondary mb-0">Catatan: Developer tidak memiliki aksi untuk menutup tiket secara langsung.</p>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingSla">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSla" aria-expanded="false" aria-controls="collapseSla">
                2. Target Waktu Penyelesaian (SLA)
            </button>
        </h2>
        <div id="collapseSla" class="accordion-collapse collapse" aria-labelledby="headingSla" data-bs-parent="#guideAccordion">
            <div class="accordion-body small">
                <div class="table-responsive">
                    <table class="table table-sm vt-table mb-2">
                        <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Rentang Skor CVSS</th>
                            <th>Batas Waktu SLA</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><?= severity_badge('Critical') ?></td>
                            <td class="vt-mono">9.0 - 10.0</td>
                            <td>24 jam</td>
                        </tr>
                        <tr>
                            <td><?= severity_badge('High') ?></td>
                            <td class="vt-mono">7.0 - 8.9</td>
                            <td>72 jam (3 hari)</td>
                        </tr>
                        <tr>
                            <td><?= severity_badge('Medium') ?></td>
                            <td class="vt-mono">4.0 - 6.9</td>
                            <td>168 jam (7 hari)</td>
                        </tr>
                        <tr>
                            <td><?= severity_badge('Low') ?></td>
                            <td class="vt-mono">0.1 - 3.9</td>
                            <td>336 jam (14 hari)</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-secondary mb-0">Status <strong>Segera Jatuh Tempo</strong> aktif ketika sisa waktu berada di bawah 25% dari total durasi SLA.</p>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingCvss">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCvss" aria-expanded="false" aria-controls="collapseCvss">
                3. Kalkulasi Skor CVSS v3.1
            </button>
        </h2>
        <div id="collapseCvss" class="accordion-collapse collapse" aria-labelledby="headingCvss" data-bs-parent="#guideAccordion">
            <div class="accordion-body small">
                <p class="mb-2">Formulir pembuatan tiket menghitung Base Score CVSS v3.1 secara langsung dari delapan metrik: Attack Vector (AV), Attack Complexity (AC), Privileges Required (PR), User Interaction (UI), Scope (S), Confidentiality (C), Integrity (I), dan Availability (A).</p>
                <p class="text-secondary mb-0">Skor yang tampil di peramban bersifat pratinjau; server selalu menghitung ulang skor dan vektor dari pilihan metrik saat tiket disimpan.</p>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingRbac">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRbac" aria-expanded="false" aria-controls="collapseRbac">
                4. Hak Akses Peran (RBAC) &amp; Audit Log
            </button>
        </h2>
        <div id="collapseRbac" class="accordion-collapse collapse" aria-labelledby="headingRbac" data-bs-parent="#guideAccordion">
            <div class="accordion-body small">
                <ul class="mb-2">
                    <li class="mb-1"><strong>Admin:</strong> Mengelola aset, membuat dan memverifikasi tiket, serta memantau seluruh audit log.</li>
                    <li class="mb-1"><strong>Security Analyst:</strong> Melaporkan celah baru, melakukan triage, dan mengeksekusi verifikasi ulang (Re-test).</li>
                    <li><strong>Developer:</strong> Melihat tiket yang ditugaskan kepadanya, memulai remediasi, dan mengirimkan patch.</li>
                </ul>
                <p class="text-secondary mb-0">Seluruh perubahan status tercatat secara tetap (append-only) di halaman Audit Log dan tidak dapat diubah maupun dihapus.</p>
            </div>
        </div>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

