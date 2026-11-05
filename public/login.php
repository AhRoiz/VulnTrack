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
        flash('success', 'Selamat datang kembali, ' . Auth::user()['name'] . '.');
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

$pageTitle = 'Masuk';
$bodyClass = 'vt-login-body';
$withNavbar = false;
$demoAccounts = [
    ['admin@vulntrack.com', 'Admin', 'bi-person-gear'],
    ['analyst@vulntrack.com', 'Security Analyst', 'bi-shield-lock'],
    ['dev@vulntrack.com', 'Developer', 'bi-code-square'],
];
require VIEW_PATH . '/layout/header.php';
?>
<main id="main-content" class="vt-login-shell">
    <div class="vt-login-card">
        <div class="row g-0">
            <div class="col-md-6 vt-login-aside">
                <div>
                    <div class="d-flex align-items-center gap-2 fs-5 fw-bold mb-3">
                        <span class="vt-logo bg-white text-primary" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                        <span>VulnTrack</span>
                    </div>
                    <p class="fs-5 fw-bold mb-2">Tata kelola remediasi celah keamanan internal terpusat.</p>
                    <p class="vt-login-aside-sub mb-0">
                        Menjembatani pelaporan Security Analyst dan perbaikan Developer melalui alur verifikasi yang terukur.
                    </p>
                </div>

                <div class="vt-login-workflow">
                    <div class="small fw-semibold text-uppercase mb-2 vt-login-aside-sub">Alur Remediasi Wajib</div>
                    <ol class="vt-login-workflow-list">
                        <li>
                            <span class="vt-login-step-num" aria-hidden="true">1</span>
                            <span><strong>Open:</strong> Pelaporan temuan &amp; skoring CVSS v3.1</span>
                        </li>
                        <li>
                            <span class="vt-login-step-num" aria-hidden="true">2</span>
                            <span><strong>Triaged:</strong> Konfirmasi aset &amp; penugasan developer</span>
                        </li>
                        <li>
                            <span class="vt-login-step-num" aria-hidden="true">3</span>
                            <span><strong>In Remediation:</strong> Patching kode &amp; tautan PR</span>
                        </li>
                        <li>
                            <span class="vt-login-step-num" aria-hidden="true">4</span>
                            <span><strong>Re-test &amp; Closed:</strong> Validasi ulang sebelum tiket ditutup</span>
                        </li>
                    </ol>
                </div>

                <div class="small vt-login-aside-sub">
                    Target SLA: Critical 24 jam, High 72 jam, Medium 7 hari, Low 14 hari.
                </div>
            </div>

            <div class="col-md-6 vt-login-form-panel">
                <h1 class="h4 fw-bold mb-1">Masuk ke VulnTrack</h1>
                <p class="text-secondary small mb-4">Gunakan akun internal perusahaan sesuai hak akses Anda.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate autocomplete="on">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
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
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Kata Sandi</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-key"></i></span>
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

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        <span>Masuk ke Dashboard</span>
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top">
                    <h2 class="small fw-semibold text-secondary mb-2">Akun Simulasi (Kata sandi: <span class="vt-mono">password123</span>)</h2>
                    <div class="d-grid gap-2">
                        <?php foreach ($demoAccounts as [$demoEmail, $label, $icon]): ?>
                            <button type="button" class="vt-demo-btn js-demo-login" data-email="<?= e($demoEmail) ?>">
                                <span class="d-flex align-items-center gap-2">
                                    <i class="bi <?= e($icon) ?> text-primary" aria-hidden="true"></i>
                                    <strong><?= e($label) ?></strong>
                                </span>
                                <span class="vt-mono text-secondary small"><?= e($demoEmail) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
$scripts = ['assets/js/login.js'];
require VIEW_PATH . '/layout/footer.php';
