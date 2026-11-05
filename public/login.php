<?php
/**
 * Halaman Login.
 */
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('dashboard.php');
}

// ---------- Pemrosesan form ----------
$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    // Throttling sederhana berbasis session (mitigasi brute force)
    $attempts = $_SESSION['_login_attempts'] ?? ['count' => 0, 'until' => 0];
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($attempts['until'] > time()) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($attempts['until'] - time()) . ' detik.';
    } elseif (Auth::attempt($email, (string) ($_POST['password'] ?? ''))) {
        unset($_SESSION['_login_attempts']);
        flash('success', 'Selamat datang kembali, ' . Auth::user()['name'] . '!');
        redirect('dashboard.php');
    } else {
        $attempts['count']++;
        if ($attempts['count'] >= 5) {
            $attempts = ['count' => 0, 'until' => time() + 60];
        }
        $_SESSION['_login_attempts'] = $attempts;
        $error = 'Email atau password salah.';
    }
}

// ---------- Tampilan ----------
$pageTitle = 'Login';
$bodyClass = 'vt-login-body';
$withNavbar = false;
$demoAccounts = [
    ['admin@vulntrack.com', 'Admin', 'bi-person-gear'],
    ['analyst@vulntrack.com', 'Security Analyst', 'bi-incognito'],
    ['dev@vulntrack.com', 'Developer', 'bi-code-slash'],
];
require VIEW_PATH . '/layout/header.php';
?>
<div class="container">
    <div class="row min-vh-100 align-items-center justify-content-center py-5">
        <div class="col-lg-10 col-xl-9">
            <div class="card vt-login-card border-0 shadow-lg overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-6 vt-login-hero p-5 d-none d-md-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center gap-2 fs-4 fw-bold">
                            <span class="vt-logo"><i class="bi bi-shield-lock-fill"></i></span>
                            Vuln<span class="text-info">Track</span>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-3">Kelola siklus hidup kerentanan dengan tata kelola yang ketat.</h2>
                            <ul class="list-unstyled vt-hero-list mb-0">
                                <li><i class="bi bi-calculator"></i>Skoring CVSS v3.1 terstandar</li>
                                <li><i class="bi bi-diagram-3"></i>Strict state-machine workflow</li>
                                <li><i class="bi bi-stopwatch"></i>Pelacakan SLA otomatis</li>
                                <li><i class="bi bi-journal-check"></i>Audit log immutable</li>
                            </ul>
                        </div>
                        <div class="small opacity-75">Internal DevSecOps Platform</div>
                    </div>

                    <div class="col-md-6 p-4 p-lg-5 bg-white">
                        <h1 class="h4 fw-bold mb-1">Masuk ke akun Anda</h1>
                        <p class="text-muted small mb-4">Gunakan kredensial internal perusahaan.</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
                                <i class="bi bi-exclamation-octagon-fill"></i><?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="login.php" novalidate autocomplete="on">
                            <?= Csrf::field() ?>
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@vulntrack.com" required autofocus>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label small fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </button>
                        </form>

                        <div class="mt-4">
                            <div class="small text-muted mb-2 fw-semibold text-uppercase vt-eyebrow">Akun demo (password: password123)</div>
                            <div class="d-grid gap-2">
                                <?php foreach ($demoAccounts as [$demoEmail, $label, $icon]): ?>
                                    <button type="button" class="btn btn-sm btn-light text-start border js-demo-login" data-email="<?= e($demoEmail) ?>">
                                        <i class="bi <?= e($icon) ?> me-2"></i><strong><?= e($label) ?></strong>
                                        <span class="text-muted ms-1"><?= e($demoEmail) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = ['assets/js/login.js'];
require VIEW_PATH . '/layout/footer.php';

