<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('dashboard.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    // Batasi 5 percobaan gagal per menit di session untuk memitigasi brute force.
    $attempts = $_SESSION['_login_attempts'] ?? ['count' => 0, 'until' => 0];
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($attempts['until'] > time()) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($attempts['until'] - time()) . ' detik.';
    } elseif (Auth::attempt($email, (string) ($_POST['password'] ?? ''))) {
        unset($_SESSION['_login_attempts']);
        flash('success', 'Selamat datang, ' . Auth::user()['name'] . '.');
        redirect('dashboard.php');
    } else {
        $attempts['count']++;
        if ($attempts['count'] >= 5) {
            $attempts = ['count' => 0, 'until' => time() + 60];
        }
        $_SESSION['_login_attempts'] = $attempts;
        $error = 'Email atau kata sandi tidak sesuai.';
    }
}

$pageTitle = 'Login';
$bodyClass = 'vt-login-body';
$withNavbar = false;
$demoAccounts = [
    ['admin@vulntrack.com', 'Admin'],
    ['analyst@vulntrack.com', 'Analyst'],
    ['dev@vulntrack.com', 'Developer'],
];
require VIEW_PATH . '/layout/header.php';
?>
<div class="vt-login-shell">
    <div class="vt-login-wrapper">
        <div class="vt-login-split">
            <header class="vt-login-brand">
                <div class="vt-login-brand-inner">
                    <span class="vt-login-brand-logo" aria-hidden="true">
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <h1 class="vt-login-brand-title">VulnTrack</h1>
                </div>
            </header>

            <main id="main-content" class="vt-login-form-pane">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                    <h2 class="h5 fw-bold mb-0">Masuk Akun</h2>
                    <nav aria-label="Bantuan sistem">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#loginGuideModal">
                            <i class="bi bi-question-circle" aria-hidden="true"></i>
                            <span>Panduan</span>
                        </button>
                    </nav>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate autocomplete="on">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email</label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               value="<?= e($email) ?>"
                               placeholder="nama@vulntrack.com"
                               autocomplete="username"
                               required
                               autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Kata Sandi</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="Masukkan kata sandi"
                                   autocomplete="current-password"
                                   required>
                            <button class="btn btn-outline-secondary"
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Tampilkan kata sandi"
                                    aria-pressed="false">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        <span>Masuk</span>
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-secondary">Akun Demo</span>
                        <span class="vt-mono small text-secondary">password123</span>
                    </div>
                    <div class="vt-demo-grid">
                        <?php foreach ($demoAccounts as [$demoEmail, $label]): ?>
                            <button type="button"
                                    class="vt-demo-btn js-demo-login"
                                    data-email="<?= e($demoEmail) ?>"
                                    aria-label="Isi akun demo <?= e($label) ?>">
                                <span><?= e($label) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>

        <footer class="vt-login-footer text-center small text-secondary mt-3">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; <span class="vt-mono">v<?= e(APP_VERSION) ?></span>
        </footer>
    </div>
</div>

<div class="modal fade" id="loginGuideModal" tabindex="-1" aria-labelledby="loginGuideLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h6 fw-bold" id="loginGuideLabel">Panduan Singkat VulnTrack</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup panduan"></button>
            </div>
            <div class="modal-body small">
                <div class="fw-semibold mb-2">Alur Remediasi Tiket</div>
                <ol class="ps-3 mb-3">
                    <li class="mb-1"><strong>Open:</strong> Pelaporan temuan &amp; skoring CVSS v3.1 oleh Security Analyst.</li>
                    <li class="mb-1"><strong>Triaged:</strong> Konfirmasi aset &amp; penugasan Developer.</li>
                    <li class="mb-1"><strong>In Remediation:</strong> Perbaikan kode &amp; pengiriman tautan PR oleh Developer.</li>
                    <li><strong>Re-test &amp; Closed:</strong> Verifikasi ulang oleh Security Analyst sebelum tiket ditutup.</li>
                </ol>

                <div class="fw-semibold mb-1">Target SLA berdasarkan Severity</div>
                <p class="text-secondary mb-3">Critical: 24 jam &middot; High: 72 jam &middot; Medium: 7 hari &middot; Low: 14 hari.</p>

                <div class="fw-semibold mb-1">Kredensial Simulasi</div>
                <ul class="list-unstyled mb-0 vt-mono text-secondary">
                    <li>admin@vulntrack.com (Admin)</li>
                    <li>analyst@vulntrack.com (Security Analyst)</li>
                    <li>dev@vulntrack.com (Developer)</li>
                </ul>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = ['assets/js/login.js'];
require VIEW_PATH . '/layout/footer.php';
