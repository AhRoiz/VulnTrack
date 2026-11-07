# VulnTrack

> **Collaborative Vulnerability Lifecycle & Security Remediation Management Platform**  
> Checkpoint 2: Full-Stack PHP Native (PHP 8.1+, MySQL / MariaDB via PDO, Bootstrap 5 High-Craft Light UI).

---

## Ringkasan Proyek

VulnTrack adalah aplikasi internal DevSecOps yang menghubungkan **Security Analyst** (pelapor dan penguji celah), **Developer** (pelaksana perbaikan kode), dan **Admin** (pengelola inventaris aset serta pengawas operasional) dalam satu siklus remediasi kerentanan yang terukur.

Aplikasi ini menggabungkan kalkulasi tingkat keparahan **CVSS v3.1**, alur kerja **Strict State-Machine**, pemantauan **Service Level Agreement (SLA)** otomatis, pencatatan **Audit Log** yang tidak dapat diubah (*append-only*), serta **Forum Diskusi** terstruktur di atas basis data relasional **MySQL/MariaDB** menggunakan **PDO Prepared Statements**.

---

## Daftar Fitur Aplikasi

### 1. Autentikasi Split-Screen & Role-Based Access Control (RBAC)
- **Halaman Masuk 2 Kolom (`public/login.php`):** Tata letak terpisah untuk identitas aplikasi dan formulir autentikasi, dilengkapi tombol panduan cepat modal (`#loginGuideModal`), fitur tampil/sembunyikan kata sandi, dan tombol pengisi otomatis akun simulasi.
- **Verifikasi Kredensial Bcrypt (`app/lib/Auth.php`):** Autentikasi memeriksa tabel `users` menggunakan PDO Prepared Statements dan memvalidasi kata sandi dengan `password_verify()` terhadap hash Bcrypt, disertai regenerasi ID sesi (`session_regenerate_id(true)`) dan pembatasan percobaan gagal (*rate limiting* di sesi).
- **Pembatasan Hak Akses 3 Peran:**
  - **Admin:** Mengelola inventaris aset, membuat dan memverifikasi tiket kerentanan, melihat seluruh log audit, dan berpartisipasi di forum.
  - **Security Analyst:** Melaporkan celah keamanan baru, menghitung skor CVSS v3.1, melakukan *triage* serta penugasan Developer, dan mengeksekusi verifikasi ulang (*Re-test: Lulus/Gagal*).
  - **Developer:** Hanya melihat tiket yang ditugaskan kepadanya (proteksi IDOR di tingkat kueri dan layanan), memulai perbaikan (*In Remediation*), serta mengirimkan catatan patch dan tautan commit/PR.

### 2. Dashboard Metrik & Visualisasi Real-Time (`public/dashboard.php`)
- **6 Kotak KPI Interaktif:** Menampilkan jumlah Total Tiket (beserta jumlah tiket aktif), *Open*, *Triaged*, *In Remediation*, *Re-test*, dan *Closed*. Setiap kotak KPI berfungsi sebagai tautan filter langsung ke halaman Kerentanan.
- **Peringatan SLA Overdue:** Banner peringatan otomatis muncul di bagian atas dashboard apabila terdapat tiket aktif yang telah melewati tenggat SLA.
- **Grafik Distribusi (Chart.js):** Diagram proporsi tiket berdasarkan tingkat keparahan (*Severity*) dan diagram batang distribusi tiket berdasarkan tahap *Workflow*, lengkap dengan tampilan *empty state* dan *error fallback*.

### 3. Manajemen Tiket & Multi-Filter Kombinasi (`public/vulnerabilities.php`)
- **Komponen Filter Multi-Kriteria (`app/views/partials/vulnerability_filter.php`):**
  - Pencarian kata kunci (`q`) mencakup ID tiket (`VT-0001`), judul, deskripsi, dan string vektor CVSS.
  - Tab cepat status *Workflow* (*Semua*, *Open*, *Triaged*, *In Remediation*, *Re-test*, *Closed*) beserta penghitung jumlah tiket per status.
  - Filter kombinasi *Severity*, *Aset Terdampak*, *Developer (Assignee)* (untuk Admin/Analyst), dan *Status SLA* (*Overdue*, *Segera Jatuh Tempo*, *On Track*, *SLA Terpenuhi*, *SLA Terlewati*).
- **Chip Filter Aktif:** Menampilkan daftar filter yang sedang diterapkan dengan tombol hapus per kriteria maupun tombol *Reset Semua Filter*.
- **Tabel Interaktif DataTables:** Mendukung pengurutan kolom numerik/prioritas, pengaturan jumlah baris per halaman (`Tampilkan [n] baris`), dan penomoran halaman.

### 4. Pelaporan Celah & Kalkulator CVSS v3.1 (`public/create_ticket.php`)
- **Kalkulator CVSS v3.1 Interaktif (`public/assets/js/cvss.js` & `app/lib/Cvss.php`):** Menghitung *Base Score* (`0.0` sampai `10.0`), *Severity* (*None*, *Low*, *Medium*, *High*, *Critical*), string vektor resmi (`CVSS:3.1/AV:.../AC:.../PR:.../UI:.../S:.../C:.../I:.../A:...`), serta target durasi SLA secara langsung saat metrik dipilih.
- **Validasi Ulang Server-Side:** Server selalu menghitung ulang skor dan tingkat keparahan dari pilihan metrik yang dikirim dan mengabaikan nilai skor mentah dari sisi klien.
- **Lampiran Proof of Concept (PoC) Aman (`public/download.php`):** Mendukung unggahan bukti `.png`, `.jpg`, `.jpeg`, `.pdf`, atau `.txt` (maksimal 2 MB) dengan pemeriksaan tipe MIME (`finfo`), penyimpanan di luar folder publik (`storage/uploads/`) menggunakan nama acak 32 karakter heksadesimal, dan pengunduhan yang memvalidasi hak akses pengguna.

### 5. Alur Remediasi Ketat (Strict State-Machine) & Catatan Patch (`public/detail_ticket.php` & `public/ticket_action.php`)
```text
[Open] ---> (Triage & Tugaskan) ---> [Triaged]
                                         |
                                         v (Mulai Perbaikan)
                                 [In Remediation] <---+
                                         |            | (Verifikasi Gagal / Re-test Fail)
                                         v            |
                                     [Re-test] -------+
                                         |
                                         v (Verifikasi Lulus / Re-test Pass)
                                      [Closed]
```
- **Stepper Visual 5 Tahap:** Menampilkan posisi tiket saat ini di dalam siklus hidup kerentanan.
- **Pemisahan Wewenang Mutlak (`app/lib/Workflow.php`):** Developer tidak memiliki aksi untuk menutup tiket secara langsung. Tiket hanya dapat berstatus *Closed* setelah Security Analyst atau Admin menyatakan verifikasi ulang (*Re-test*) lulus.
- **Catatan Remediasi:** Setiap pengajuan patch oleh Developer wajib menyertakan ringkasan teknis perbaikan dan URL commit/Pull Request yang valid (`http://` atau `https://`) dan disimpan ke tabel `remediation_notes`.

### 6. Pelacakan SLA Otomatis (`app/lib/Sla.php`)
- Tenggat waktu dihitung otomatis dari waktu pembuatan tiket berdasarkan tingkat keparahan:
  - **Critical (9.0 - 10.0):** 24 jam
  - **High (7.0 - 8.9):** 72 jam (3 hari)
  - **Medium (4.0 - 6.9):** 168 jam (7 hari)
  - **Low (0.1 - 3.9):** 336 jam (14 hari)
- Status SLA diklasifikasikan otomatis menjadi: **On Track**, **Segera Jatuh Tempo** (sisa waktu `<= 25%` dari jendela SLA), **Overdue** (melewati tenggat saat tiket masih aktif), **SLA Terpenuhi** (ditutup sebelum atau tepat tenggat), dan **SLA Terlewati** (ditutup setelah melewati tenggat).

### 7. Manajemen Inventaris Aset (`public/assets.php`)
- Halaman khusus **Admin** untuk mendaftarkan dan memantau sistem/aplikasi target (*Customer Portal*, *Payment API Gateway*, *Internal HR System*, *Mobile Backend API*, *Marketing CMS*) beserta klasifikasi environment (*production*, *staging*, *development*), jumlah temuan aktif, dan total temuan per aset.

### 8. Audit Log Immutable (`public/audit_log.php`)
- Mencatat setiap pembuatan tiket dan transisi status beserta waktu kejadian, pelaksana aksi, peran, dan perubahan status lama ke status baru.
- Bersifat **Append-Only**: tidak ada fitur edit atau hapus di aplikasi, dan dilindungi oleh trigger di tingkat database MySQL/MariaDB.

### 9. Forum Diskusi Terstruktur (`public/forum.php` & `app/lib/ForumService.php`)
- **Hierarki 3 Kategori Subforum:**
  - *General Updates*: Pengumuman rilis, jadwal sinkronisasi staging, dan koordinasi umum lintas peran.
  - *Patching Coordination*: Diskusi teknis pull request perbaikan, skenario pengujian, dan kesiapan *re-test*.
  - *SLA Warnings*: Eskalasi tiket prioritas tinggi yang mendekati atau melewati tenggat SLA.
- **Topik (Thread) & Balasan (Reply):** Setiap topik menampilkan kategori, subjek, nama lengkap pengirim, badge peran, alamat email, stempel waktu WIB, isi pesan pembuka, daftar balasan berurutan, dan formulir balasan.
- **Fitur Ikuti Topik (Subscribe Toggle):** Pengguna dapat mengaktifkan atau menonaktifkan pemantauan per topik diskusi (`Ikuti Topik` / `Mengikuti Topik`) yang tersimpan secara persisten di tabel `forum_subscriptions`.

### 10. Bantuan & Panduan Terpadu (`public/guide.php`)
- Halaman dokumentasi internal berbentuk akordeon yang merinci alur *State-Machine*, tabel batas waktu SLA per *Severity*, rumus metrik CVSS v3.1, serta pembagian hak akses RBAC.

---

## Arsitektur & Detail Database (Checkpoint 2)

Pada Checkpoint 2, seluruh mekanisme penyimpanan berbasis berkas JSON (`JsonStore.php`, `storage/data.json`, `app/seed.php`) dan array akun statis (`app/users.php`) telah dihapus dan digantikan sepenuhnya oleh database relasional **MySQL / MariaDB** (`InnoDB`, `utf8mb4_unicode_ci`) yang didefinisikan pada `database/schema.sql`.

### 1. Rincian Skema Tabel (`database/schema.sql`)

| Nama Tabel | Fungsi Utama | Kolom Kunci & Tipe Data | Relasi Foreign Key & Constraint |
|---|---|---|---|
| `users` | Menyimpan akun pengguna dan peran RBAC | `id` (PK), `name`, `email` (UNIQUE), `password_hash` (VARCHAR 255), `role` (`ENUM('admin','analyst','developer')`), `is_active`, `created_at`, `updated_at` | Indeks unik pada `email` dan indeks pencarian pada `role`. |
| `assets` | Menyimpan inventaris sistem/aplikasi yang diuji | `id` (PK), `asset_name`, `environment` (`ENUM('production','staging','development')`), `description`, `created_at` | Indeks pada `environment`. |
| `vulnerabilities` | Menyimpan data utama tiket celah keamanan | `id` (PK), `title`, `description`, `asset_id` (FK), `reporter_id` (FK), `assignee_id` (FK nullable), `cvss_vector`, `cvss_score` (`DECIMAL(3,1)`), `severity`, `status`, `poc_file`, `poc_original`, `sla_deadline`, `created_at`, `updated_at`, `closed_at` | `fk_vuln_asset` -> `assets(id)` (`ON DELETE RESTRICT`); `fk_vuln_reporter` -> `users(id)` (`ON DELETE RESTRICT`); `fk_vuln_assignee` -> `users(id)` (`ON DELETE SET NULL`); `chk_vuln_score` (`cvss_score BETWEEN 0.0 AND 10.0`); `chk_vuln_closed` (`(status = 'Closed') = (closed_at IS NOT NULL)`). |
| `remediation_notes` | Menyimpan catatan patch dan tautan PR dari Developer | `id` (PK), `vulnerability_id` (FK), `user_id` (FK), `patch_summary` (TEXT), `commit_url` (VARCHAR 500), `created_at` | `fk_notes_vuln` -> `vulnerabilities(id)` (`ON DELETE CASCADE`); `fk_notes_user` -> `users(id)` (`ON DELETE RESTRICT`). |
| `audit_logs` | Menyimpan jejak historis transisi status tiket (*immutable*) | `id` (PK BIGINT), `vulnerability_id` (FK), `user_id` (FK), `action`, `old_status` (nullable), `new_status`, `timestamp` | `fk_audit_vuln` -> `vulnerabilities(id)` (`ON UPDATE RESTRICT ON DELETE RESTRICT`); `fk_audit_user` -> `users(id)` (`ON UPDATE RESTRICT ON DELETE RESTRICT`). |
| `forum_posts` | Menyimpan topik diskusi (`parent_id IS NULL`) dan balasan (`parent_id IS NOT NULL`) dalam satu tabel hierarkis | `id` (PK), `parent_id` (FK self-reference nullable), `user_id` (FK), `category` (`ENUM('general-updates','patching-coordination','sla-warnings')` nullable), `subject` (nullable), `message`, `created_at`, `updated_at` | `fk_forum_parent` -> `forum_posts(id)` (`ON DELETE CASCADE`); `fk_forum_user` -> `users(id)` (`ON DELETE RESTRICT`); `chk_forum_shape` memastikan baris topik memiliki `category` + `subject` dan baris balasan memiliki `parent_id`. |
| `forum_subscriptions` | Menyimpan langganan pengguna terhadap topik forum | `post_id` (PK, FK), `user_id` (PK, FK), `created_at` | Primary Key komposit `(post_id, user_id)`; `fk_forum_sub_post` -> `forum_posts(id)` (`ON DELETE CASCADE`); `fk_forum_sub_user` -> `users(id)` (`ON DELETE CASCADE`). |

### 2. Trigger Immutability pada `audit_logs`
Selain tidak menyediakan fungsi ubah/hapus di kode PHP, skema database memasang dua trigger di tingkat mesin MySQL/MariaDB:
- `trg_audit_logs_no_update`: Memblokir setiap perintah `UPDATE` pada tabel `audit_logs` dengan `SIGNAL SQLSTATE '45000'`.
- `trg_audit_logs_no_delete`: Memblokir setiap perintah `DELETE` pada tabel `audit_logs` dengan `SIGNAL SQLSTATE '45000'`.

### 3. Lapisan Koneksi PDO & Keamanan Kueri (`app/lib/Database.php`)
- **Native Prepared Statements:** Koneksi PDO dikonfigurasi dengan `PDO::ATTR_EMULATE_PREPARES => false`, `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, dan `PDO::ATTR_STRINGIFY_FETCHES => false`. Seluruh parameter kueri diikat menggunakan tipe data eksplisit (`PDO::PARAM_INT`, `PDO::PARAM_BOOL`, `PDO::PARAM_NULL`, `PDO::PARAM_STR`) untuk mencegah SQL Injection.
- **Transaksi ACID & Row-Level Locking:** Pembuatan tiket (`VulnerabilityService::create()`) dan perubahan status (`VulnerabilityService::applyAction()`) dibungkus di dalam `Database::transaction()`. Saat mengeksekusi aksi workflow, baris tiket dikunci dengan `SELECT * FROM vulnerabilities WHERE id = :id FOR UPDATE` agar tidak terjadi kondisi balapan (*race condition*) antar pengguna.
- **Auto-Provisioning Skema & Data Awal:** Apabila database `vulntrack` belum dibuat (kode error MySQL `1049`) atau tabel `users` belum ada saat aplikasi dijalankan, `Database::provisionMySql()` akan otomatis mengeksekusi seluruh DDL dan data awal dari `database/schema.sql`.

---

## Akun Simulasi (Seeded di Tabel `users`)

Seluruh akun awal disimpan di tabel `users` dengan hash Bcrypt untuk kata sandi **`password123`**:

| Role | Email | Kata Sandi | Hak Akses Utama |
|---|---|---|---|
| **Admin** | `admin@vulntrack.com` | `password123` | Akses penuh, kelola aset (`assets.php`), buat/triage/verifikasi tiket, lihat semua audit log & forum. |
| **Security Analyst** | `analyst@vulntrack.com` | `password123` | Buat tiket baru (`create_ticket.php`), kalkulasi CVSS, triage, verifikasi ulang (*Re-test: Lulus/Gagal*), lihat semua tiket & forum. |
| **Developer** | `dev@vulntrack.com` | `password123` | Hanya melihat tiket yang ditugaskan kepadanya, mulai perbaikan, kirim catatan patch & commit URL, serta berdiskusi di forum. |

---

## Cara Mencoba dan Menjalankan Aplikasi

### Prasyarat
1. **PHP 8.1 atau lebih baru** dengan ekstensi `pdo_mysql`, `mbstring`, dan `fileinfo` aktif (sudah tersedia secara bawaan di XAMPP / Laragon).
2. **MySQL 8.0+ atau MariaDB 10.4+** (misalnya melalui panel kontrol XAMPP / Laragon).

### Langkah 1: Nyalakan Layanan MySQL / MariaDB
Buka **XAMPP Control Panel** (atau Laragon), lalu klik **Start** pada modul **MySQL** (dan **Apache** jika ingin menjalankan melalui Apache).

### Langkah 2: Impor Skema Database (`database/schema.sql`)
Anda dapat memilih salah satu dari dua cara berikut:

- **Cara Otomatis (Tanpa Impor Manual):**  
  Jika MySQL berjalan dengan kredensial bawaan XAMPP (`host: 127.0.0.1`, `port: 3306`, `user: root`, `password: kosong`), langsung buka aplikasi di browser. Kelas `Database` akan otomatis membuat database `vulntrack`, membuat ke-7 tabel beserta trigger, dan mengisi data awal dari `database/schema.sql`.
- **Cara Manual (via Terminal atau phpMyAdmin):**  
  Jalankan perintah berikut dari direktori root proyek `VulnTrack`:
  ```bash
  mysql -u root < database/schema.sql
  ```
  *(Jika akun `root` MySQL Anda menggunakan kata sandi, tambahkan flag `-p` dan sesuaikan konfigurasi di `app/config.php`).*

### Langkah 3: Sesuaikan Konfigurasi Database (Opsional)
Pengaturan koneksi berada di `app/config.php` dan dapat dioverride menggunakan variabel environment maupun konstanta:
```php
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'vulntrack');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
```

### Langkah 4: Jalankan Web Server

#### Opsi A: Menggunakan PHP Built-in Server
Buka terminal di direktori `e:\VulnTrack`, lalu jalankan:
```bash
php -S 127.0.0.1:8000 -t public
```
Buka browser dan akses: **`http://127.0.0.1:8000`**

#### Opsi B: Menggunakan Apache (XAMPP / Laragon)
Jika folder proyek berada di dalam `htdocs/VulnTrack` (atau dipasang sebagai VirtualHost):
- Akses **`http://localhost/VulnTrack/`** di browser (file `index.php` di root akan mengarahkan ke `public/index.php`).
- Direktori `app/` dan `storage/` telah dilengkapi `.htaccess` (`Deny from all`) sehingga aman dari akses HTTP langsung.

### Langkah 5: Skenario Mencoba Fitur Lengkap
1. **Uji Peran Security Analyst (`analyst@vulntrack.com` / `password123`):**
   - Klik tombol demo **Analyst** di halaman login lalu klik **Masuk**.
   - Buka menu **Tiket Baru**, isi judul, pilih aset dan developer, ubah metrik **Kalkulator CVSS v3.1** (perhatikan skor dan SLA berubah secara langsung), lalu simpan tiket.
   - Buka tiket berstatus *Open*, lalu jalankan aksi **Triage & Tugaskan** agar status berubah menjadi *Triaged*.
2. **Uji Peran Developer (`dev@vulntrack.com` / `password123`):**
   - Logout dan masuk kembali menggunakan tombol demo **Developer**.
   - Buka tiket yang ditugaskan, klik **Mulai Perbaikan** (*Triaged* -> *In Remediation*), lalu isi **Ringkasan Patch** dan **Tautan Commit / Pull Request** dan klik **Kirim Patch untuk Re-test** (*In Remediation* -> *Re-test*).
   - Perhatikan bahwa Developer tidak memiliki tombol untuk menutup tiket dan akan ditolak (`HTTP 403`) jika mencoba mengakses `create_ticket.php` atau `assets.php`.
3. **Uji Verifikasi Re-test & Penutupan Tiket (`analyst@vulntrack.com` / `admin@vulntrack.com`):**
   - Masuk kembali sebagai **Analyst** atau **Admin**, buka tiket yang berstatus *Re-test* (misalnya `VT-0002` atau tiket yang baru dikirim patch-nya).
   - Klik **Verifikasi Gagal** untuk mengembalikan tiket ke *In Remediation* beserta alasan temuan ulang, atau klik **Verifikasi Lulus & Tutup** untuk menutup tiket menjadi *Closed*.
   - Buka menu **Audit Log** untuk memeriksa seluruh jejak transisi status yang baru tercatat.
4. **Uji Forum Diskusi & Manajemen Aset:**
   - Buka menu **Forum Diskusi**, filter berdasarkan subforum (*General Updates*, *Patching Coordination*, *SLA Warnings*), buat topik baru, kirim balasan, dan klik tombol **Ikuti Topik**.
   - Masuk sebagai **Admin** dan buka menu **Manajemen Aset** untuk menambahkan aset sistem baru.

---

## Struktur Direktori

```text
VulnTrack/
├── app/                        # Logika aplikasi (di luar document root publik)
│   ├── lib/                    # Kelas domain, repositori, dan layanan PDO
│   │   ├── AssetRepository.php      # CRUD tabel assets via PDO
│   │   ├── Auth.php                 # Autentikasi PDO Bcrypt & pemeriksaan RBAC
│   │   ├── Csrf.php                 # Pembuatan & verifikasi token anti-CSRF
│   │   ├── Cvss.php                 # Kalkulator CVSS v3.1 server-side
│   │   ├── Database.php             # Singleton koneksi PDO, prepared statements & transaksi
│   │   ├── ForumService.php         # Layanan subforum, topik, balasan & langganan via PDO
│   │   ├── Sla.php                  # Perhitungan tenggat & klasifikasi status SLA
│   │   ├── ValidationException.php  # Exception validasi input formulir
│   │   ├── VulnerabilityService.php # Layanan tiket, catatan remediasi & audit log via PDO
│   │   ├── Workflow.php             # Aturan Strict State-Machine per peran & status
│   │   └── helpers.php              # Fungsi sanitasi XSS e(), flash session, dan badge UI
│   ├── views/                  # Template tampilan HTML/PHP
│   │   ├── layout/                  # Header, sidebar navigasi, topbar, dan footer
│   │   ├── partials/                # Tabel tiket & komponen multi-filter kerentanan
│   │   └── error.php                # Halaman error HTTP (403, 404, 405, 419)
│   ├── bootstrap.php           # Autoloader kelas, konfigurasi sesi aman & helper db()
│   └── config.php              # Konstanta aplikasi, kredensial DB, SLA & batas upload PoC
├── database/
│   └── schema.sql              # Skema DDL MySQL/MariaDB (7 tabel, 2 trigger) & data seed awal
├── public/                     # Document root web server
│   ├── assets/
│   │   ├── css/app.css         # Desain High-Craft Light Mode & komponen kustom
│   │   └── js/                 # Script CVSS, Chart.js dashboard, DataTables & login
│   ├── assets.php              # Halaman manajemen inventaris aset (khusus Admin)
│   ├── audit_log.php           # Halaman riwayat audit append-only
│   ├── create_ticket.php       # Formulir pelaporan celah & kalkulator CVSS v3.1
│   ├── dashboard.php           # Halaman utama KPI, grafik distribusi & tiket terbaru
│   ├── detail_ticket.php       # Rincian tiket, stepper workflow, form aksi & riwayat
│   ├── download.php            # Endpoint unduh lampiran PoC dengan validasi otorisasi
│   ├── forum.php               # Halaman Forum Diskusi (subforum, thread, balasan, subscribe)
│   ├── guide.php               # Halaman Bantuan & Panduan operasional
│   ├── index.php               # Pengarah awal ke dashboard.php atau login.php
│   ├── login.php               # Halaman masuk Split-Screen & modal panduan cepat
│   ├── logout.php              # Endpoint keluar sesi (POST + CSRF)
│   ├── ticket_action.php       # Endpoint pemroses transisi status tiket (POST + CSRF)
│   └── vulnerabilities.php     # Daftar tiket dengan multi-filter & chip filter aktif
├── storage/
│   └── uploads/                # Direktori penyimpanan berkas PoC di luar web root
├── index.php                   # Pengarah root proyek ke public/index.php
└── README.md                   # Dokumentasi fitur, arsitektur database & panduan penggunaan
```

---

## Ringkasan Keamanan yang Diterapkan

- **Mitigasi SQL Injection:** 100% kueri database menggunakan PDO *native prepared statements* (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Mitigasi Cross-Site Scripting (XSS):** Seluruh output data ke HTML disanitasi melalui fungsi `e()` (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
- **Mitigasi Cross-Site Request Forgery (CSRF):** Seluruh aksi perubahan data (`POST`) wajib menyertakan token sesi acak 32-byte yang diverifikasi menggunakan `hash_equals()` di [`Csrf::verify()`](app/lib/Csrf.php).
- **Mitigasi IDOR & Escalation of Privilege:** Pemeriksaan peran (`Auth::requireRole()`) dan kepemilikan tiket (`VulnerabilityService::canView()` serta `Workflow::can()`) ditegakkan di sisi server dan di dalam transaksi database yang terkunci (`FOR UPDATE`).
- **Keamanan Sesi & Password:** Kata sandi di-hash menggunakan algoritma Bcrypt (`password_hash` / `password_verify`), cookie sesi memakai flag `HttpOnly` dan `SameSite=Lax`, serta header keamanan HTTP (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`) dikirim di setiap respons.
