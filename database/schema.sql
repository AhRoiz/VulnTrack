-- VulnTrack Checkpoint 2: skema MySQL / MariaDB (InnoDB, utf8mb4) beserta data awal.
-- Jalankan: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS vulntrack
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vulntrack;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS forum_subscriptions;
DROP TABLE IF EXISTS forum_posts;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS remediation_notes;
DROP TABLE IF EXISTS vulnerabilities;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'analyst', 'developer') NOT NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_name  VARCHAR(100) NOT NULL,
    environment ENUM('production', 'staging', 'development') NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assets_environment (environment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vulnerabilities (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    asset_id     INT UNSIGNED NOT NULL,
    reporter_id  INT UNSIGNED NOT NULL,
    assignee_id  INT UNSIGNED NULL,
    cvss_vector  VARCHAR(64) NOT NULL,
    cvss_score   DECIMAL(3,1) NOT NULL,
    severity     ENUM('Critical', 'High', 'Medium', 'Low', 'None') NOT NULL,
    status       ENUM('Open', 'Triaged', 'In Remediation', 'Re-test', 'Closed') NOT NULL DEFAULT 'Open',
    poc_file     VARCHAR(255) NULL,
    poc_original VARCHAR(255) NULL,
    sla_deadline DATETIME NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_vuln_status (status),
    KEY idx_vuln_severity (severity),
    KEY idx_vuln_assignee (assignee_id),
    KEY idx_vuln_asset (asset_id),
    KEY idx_vuln_sla (sla_deadline),
    CONSTRAINT fk_vuln_asset    FOREIGN KEY (asset_id)    REFERENCES assets (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vuln_reporter FOREIGN KEY (reporter_id) REFERENCES users (id)  ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vuln_assignee FOREIGN KEY (assignee_id) REFERENCES users (id)  ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_vuln_score   CHECK (cvss_score BETWEEN 0.0 AND 10.0),
    CONSTRAINT chk_vuln_closed  CHECK ((status = 'Closed') = (closed_at IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE remediation_notes (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vulnerability_id INT UNSIGNED NOT NULL,
    user_id          INT UNSIGNED NOT NULL,
    patch_summary    TEXT NOT NULL,
    commit_url       VARCHAR(500) NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notes_vuln (vulnerability_id),
    CONSTRAINT fk_notes_vuln FOREIGN KEY (vulnerability_id) REFERENCES vulnerabilities (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id)          REFERENCES users (id)           ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RESTRICT pada kedua FK: tiket atau user yang punya jejak audit tidak boleh dihapus.
CREATE TABLE audit_logs (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vulnerability_id INT UNSIGNED NOT NULL,
    user_id          INT UNSIGNED NOT NULL,
    action           VARCHAR(255) NOT NULL,
    old_status       ENUM('Open', 'Triaged', 'In Remediation', 'Re-test', 'Closed') NULL,
    new_status       ENUM('Open', 'Triaged', 'In Remediation', 'Re-test', 'Closed') NOT NULL,
    timestamp        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_vuln (vulnerability_id),
    KEY idx_audit_time (timestamp),
    CONSTRAINT fk_audit_vuln FOREIGN KEY (vulnerability_id) REFERENCES vulnerabilities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id)          REFERENCES users (id)           ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu tabel untuk topik dan balasan: parent_id NULL = topik (thread), terisi = balasan.
CREATE TABLE forum_posts (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id  INT UNSIGNED NULL,
    user_id    INT UNSIGNED NOT NULL,
    category   ENUM('general-updates', 'patching-coordination', 'sla-warnings') NULL,
    subject    VARCHAR(150) NULL,
    message    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_forum_parent (parent_id),
    KEY idx_forum_category_activity (category, updated_at),
    CONSTRAINT fk_forum_parent FOREIGN KEY (parent_id) REFERENCES forum_posts (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_forum_user   FOREIGN KEY (user_id)   REFERENCES users (id)       ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_forum_shape CHECK (
        (parent_id IS NULL AND category IS NOT NULL AND subject IS NOT NULL)
        OR (parent_id IS NOT NULL AND category IS NULL AND subject IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE forum_subscriptions (
    post_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    KEY idx_forum_sub_user (user_id),
    CONSTRAINT fk_forum_sub_post FOREIGN KEY (post_id) REFERENCES forum_posts (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_forum_sub_user FOREIGN KEY (user_id) REFERENCES users (id)       ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log append-only: blokir UPDATE dan DELETE di level database.
CREATE TRIGGER trg_audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs bersifat immutable: UPDATE tidak diizinkan.';

CREATE TRIGGER trg_audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs bersifat immutable: DELETE tidak diizinkan.';

-- Akun awal pengganti app/users.php. Hash Bcrypt untuk kata sandi: password123
INSERT INTO users (id, name, email, password_hash, role, created_at) VALUES
    (1, 'Administrator',  'admin@vulntrack.com',   '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO', 'admin',     '2026-01-01 08:00:00'),
    (2, 'Sarah Analyst',  'analyst@vulntrack.com', '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO', 'analyst',   '2026-01-01 08:00:00'),
    (3, 'Budi Developer', 'dev@vulntrack.com',     '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO', 'developer', '2026-01-01 08:00:00');

INSERT INTO assets (id, asset_name, environment, description, created_at) VALUES
    (1, 'Customer Portal',     'production',  'Portal web pelanggan (portal.example.com).',      '2026-01-02 09:00:00'),
    (2, 'Payment API Gateway', 'production',  'REST API pemrosesan pembayaran & invoice.',       '2026-01-02 09:05:00'),
    (3, 'Internal HR System',  'staging',     'Aplikasi HRIS internal karyawan.',                '2026-01-02 09:10:00'),
    (4, 'Mobile Backend API',  'staging',     'Backend untuk aplikasi Android & iOS.',           '2026-01-02 09:15:00'),
    (5, 'Marketing CMS',       'development', 'CMS landing page & blog perusahaan.',             '2026-01-02 09:20:00');

INSERT INTO vulnerabilities (id, title, description, asset_id, reporter_id, assignee_id, cvss_vector, cvss_score, severity, status, poc_file, poc_original, sla_deadline, created_at, updated_at, closed_at) VALUES
    (1, 'IDOR pada endpoint /api/v1/invoices/{id}',
        'Endpoint mengembalikan data invoice milik pengguna lain hanya dengan mengganti parameter ID. Tidak ada validasi kepemilikan objek di sisi server.\n\nLangkah reproduksi:\n1. Login sebagai user A\n2. GET /api/v1/invoices/1043 (milik user B)\n3. Response 200 berisi data sensitif user B',
        2, 2, 3, 'CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N', 8.1, 'High', 'In Remediation', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 40 HOUR), INTERVAL 72 HOUR),
        DATE_SUB(NOW(), INTERVAL 40 HOUR),
        DATE_SUB(NOW(), INTERVAL 10 HOUR),
        NULL),
    (2, 'SQL Injection pada form login Customer Portal',
        'Parameter `username` pada POST /login dirangkai langsung ke query SQL. Payload `'' OR 1=1-- -` berhasil melewati autentikasi.',
        1, 2, 3, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H', 9.8, 'Critical', 'Re-test', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 20 HOUR), INTERVAL 24 HOUR),
        DATE_SUB(NOW(), INTERVAL 20 HOUR),
        DATE_SUB(NOW(), INTERVAL 6 HOUR),
        NULL),
    (3, 'Stored XSS pada kolom komentar tiket support',
        'Input komentar tidak di-encode saat dirender sehingga payload <script> tersimpan dan tereksekusi di browser agen support.',
        1, 2, 3, 'CVSS:3.1/AV:N/AC:L/PR:L/UI:R/S:C/C:L/I:L/A:N', 5.4, 'Medium', 'Triaged', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 72 HOUR), INTERVAL 168 HOUR),
        DATE_SUB(NOW(), INTERVAL 72 HOUR),
        DATE_SUB(NOW(), INTERVAL 70 HOUR),
        NULL),
    (4, 'Tidak ada rate limiting pada endpoint verifikasi OTP',
        'Endpoint POST /auth/otp/verify tidak membatasi jumlah percobaan sehingga OTP 6 digit dapat di-brute force.',
        4, 2, 3, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N', 5.3, 'Medium', 'Open', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 216 HOUR), INTERVAL 168 HOUR),
        DATE_SUB(NOW(), INTERVAL 216 HOUR),
        DATE_SUB(NOW(), INTERVAL 216 HOUR),
        NULL),
    (5, 'Verbose error menampilkan stack trace & path server',
        'Halaman error 500 menampilkan stack trace lengkap, versi framework, dan absolute path server.',
        3, 2, 3, 'CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:L/I:N/A:N', 3.7, 'Low', 'Closed', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 240 HOUR), INTERVAL 336 HOUR),
        DATE_SUB(NOW(), INTERVAL 240 HOUR),
        DATE_SUB(NOW(), INTERVAL 120 HOUR),
        DATE_SUB(NOW(), INTERVAL 120 HOUR)),
    (6, 'SSRF pada fitur fetch avatar dari URL',
        'Parameter `avatar_url` diproses server tanpa allowlist sehingga dapat mengakses metadata cloud (169.254.169.254).',
        4, 2, 3, 'CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:N/A:N', 7.7, 'High', 'Open', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 HOUR), INTERVAL 72 HOUR),
        DATE_SUB(NOW(), INTERVAL 2 HOUR),
        DATE_SUB(NOW(), INTERVAL 2 HOUR),
        NULL),
    (7, 'Library jQuery 1.12 usang dengan CVE publik',
        'Marketing CMS memuat jQuery 1.12.4 yang rentan terhadap CVE-2020-11022/11023.',
        5, 2, 3, 'CVSS:3.1/AV:N/AC:H/PR:N/UI:R/S:U/C:L/I:L/A:N', 4.2, 'Medium', 'Closed', NULL, NULL,
        DATE_ADD(DATE_SUB(NOW(), INTERVAL 480 HOUR), INTERVAL 168 HOUR),
        DATE_SUB(NOW(), INTERVAL 480 HOUR),
        DATE_SUB(NOW(), INTERVAL 370 HOUR),
        DATE_SUB(NOW(), INTERVAL 370 HOUR));

INSERT INTO remediation_notes (id, vulnerability_id, user_id, patch_summary, commit_url, created_at) VALUES
    (1, 1, 3, 'Menambahkan pengecekan ownership pada InvoiceController@show.', 'https://github.com/example/payment-api/pull/212', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
    (2, 2, 3, 'Mengganti query string concatenation dengan PDO prepared statements di AuthRepository.', 'https://github.com/example/customer-portal/pull/88', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
    (3, 5, 3, 'Set APP_DEBUG=false di staging dan menambahkan custom error handler.', 'https://github.com/example/hris/commit/9f3c2ab', DATE_SUB(NOW(), INTERVAL 140 HOUR)),
    (4, 7, 3, 'Upgrade jQuery ke 3.7.1 dan menghapus plugin yang tidak kompatibel.', 'https://github.com/example/marketing-cms/pull/31', DATE_SUB(NOW(), INTERVAL 380 HOUR));

INSERT INTO audit_logs (id, vulnerability_id, user_id, action, old_status, new_status, timestamp) VALUES
    (1,  7, 2, 'Tiket dibuat (CVSS 4.2, Medium)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 480 HOUR)),
    (2,  7, 1, 'Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', DATE_SUB(NOW(), INTERVAL 470 HOUR)),
    (3,  7, 3, 'Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', DATE_SUB(NOW(), INTERVAL 400 HOUR)),
    (4,  7, 3, 'Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', DATE_SUB(NOW(), INTERVAL 380 HOUR)),
    (5,  7, 2, 'Re-test LULUS, tiket ditutup', 'Re-test', 'Closed', DATE_SUB(NOW(), INTERVAL 370 HOUR)),
    (6,  5, 2, 'Tiket dibuat (CVSS 3.7, Low)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 240 HOUR)),
    (7,  5, 2, 'Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', DATE_SUB(NOW(), INTERVAL 236 HOUR)),
    (8,  4, 2, 'Tiket dibuat (CVSS 5.3, Medium)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 216 HOUR)),
    (9,  5, 3, 'Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', DATE_SUB(NOW(), INTERVAL 200 HOUR)),
    (10, 5, 3, 'Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', DATE_SUB(NOW(), INTERVAL 140 HOUR)),
    (11, 5, 2, 'Re-test LULUS, tiket ditutup', 'Re-test', 'Closed', DATE_SUB(NOW(), INTERVAL 120 HOUR)),
    (12, 3, 2, 'Tiket dibuat (CVSS 5.4, Medium)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 72 HOUR)),
    (13, 3, 2, 'Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', DATE_SUB(NOW(), INTERVAL 70 HOUR)),
    (14, 1, 2, 'Tiket dibuat (CVSS 8.1, High)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 40 HOUR)),
    (15, 1, 2, 'Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', DATE_SUB(NOW(), INTERVAL 39 HOUR)),
    (16, 1, 3, 'Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', DATE_SUB(NOW(), INTERVAL 30 HOUR)),
    (17, 1, 3, 'Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
    (18, 2, 2, 'Tiket dibuat (CVSS 9.8, Critical)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
    (19, 2, 2, 'Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', DATE_SUB(NOW(), INTERVAL 1170 MINUTE)),
    (20, 2, 3, 'Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
    (21, 1, 2, 'Re-test GAGAL: Endpoint /invoices/{id}/pdf masih bisa diakses lintas user.', 'Re-test', 'In Remediation', DATE_SUB(NOW(), INTERVAL 10 HOUR)),
    (22, 2, 3, 'Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
    (23, 6, 2, 'Tiket dibuat (CVSS 7.7, High)', NULL, 'Open', DATE_SUB(NOW(), INTERVAL 2 HOUR));

INSERT INTO forum_posts (id, parent_id, user_id, category, subject, message, created_at, updated_at) VALUES
    (1, NULL, 1, 'general-updates',
        'Jadwal sinkronisasi deployment staging pekan ini',
        'Seluruh patch untuk layanan Customer Portal dan Payment API Gateway dijadwalkan naik ke environment staging setiap pukul 16:00 WIB sebelum verifikasi ulang.',
        DATE_SUB(NOW(), INTERVAL 28 HOUR),
        DATE_SUB(NOW(), INTERVAL 22 HOUR)),
    (2, NULL, 3, 'patching-coordination',
        'Koordinasi perbaikan VT-0001 pada endpoint cetak PDF invoice',
        'Validasi kepemilikan objek sudah diperluas ke controller unduhan PDF pada PR #212. Mohon konfirmasi apakah pengujian ulang memerlukan akun uji multi-tenant tambahan.',
        DATE_SUB(NOW(), INTERVAL 8 HOUR),
        DATE_SUB(NOW(), INTERVAL 3 HOUR)),
    (3, NULL, 2, 'sla-warnings',
        'Eskalasi SLA VT-0004 (Rate limiting verifikasi OTP) dan prioritas VT-0002',
        'Tiket VT-0004 pada Mobile Backend API telah melewati tenggat SLA Medium, sementara VT-0002 (SQL Injection Customer Portal) berada di tahap Re-test dengan batas waktu Critical.',
        DATE_SUB(NOW(), INTERVAL 150 MINUTE),
        DATE_SUB(NOW(), INTERVAL 1 HOUR)),
    (4, 1, 2, NULL, NULL,
        'Diterima. Tim analis menyiapkan skenario regresi autentikasi dan otorisasi invoice setiap selesai sinkronisasi sore.',
        DATE_SUB(NOW(), INTERVAL 22 HOUR),
        DATE_SUB(NOW(), INTERVAL 22 HOUR)),
    (5, 2, 2, NULL, NULL,
        'Gunakan dua akun uji yang sudah ada di staging. Pastikan response saat akses lintas akun mengembalikan kode HTTP yang konsisten.',
        DATE_SUB(NOW(), INTERVAL 330 MINUTE),
        DATE_SUB(NOW(), INTERVAL 330 MINUTE)),
    (6, 2, 3, NULL, NULL,
        'Sudah disesuaikan agar mengembalikan HTTP 404 seragam seperti endpoint detail invoice. Unit test selesai dijalankan dan siap masuk antrean Re-test.',
        DATE_SUB(NOW(), INTERVAL 3 HOUR),
        DATE_SUB(NOW(), INTERVAL 3 HOUR)),
    (7, 3, 1, NULL, NULL,
        'Prioritaskan penutupan verifikasi VT-0002 sore ini terlebih dahulu, lalu jadwalkan penerapan middleware rate limiter untuk VT-0004 pada sprint berjalan.',
        DATE_SUB(NOW(), INTERVAL 1 HOUR),
        DATE_SUB(NOW(), INTERVAL 1 HOUR));

INSERT INTO forum_subscriptions (post_id, user_id, created_at) VALUES
    (1, 1, DATE_SUB(NOW(), INTERVAL 28 HOUR)),
    (1, 2, DATE_SUB(NOW(), INTERVAL 22 HOUR)),
    (2, 2, DATE_SUB(NOW(), INTERVAL 330 MINUTE)),
    (2, 3, DATE_SUB(NOW(), INTERVAL 8 HOUR)),
    (3, 1, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
    (3, 2, DATE_SUB(NOW(), INTERVAL 150 MINUTE)),
    (3, 3, DATE_SUB(NOW(), INTERVAL 120 MINUTE));
