# VulnTrack

> **Collaborative Vulnerability Lifecycle & Security Remediation Management Platform**  
> Checkpoint 1: Functional MVP (PHP Native, Session/JSON-backed, No Database).

---

## 📌 Ringkasan Proyek

VulnTrack adalah platform internal DevSecOps yang menjembatani komunikasi antara **Security Analyst** (pelapor celah) dan **Developer** (penambal celah) dengan memberlakukan:
1. **Skoring CVSS v3.1 Terstandar:** Perhitungan Base Metric interaktif di sisi klien (JS) dan validasi ulang di sisi server (PHP).
2. **Strict State-Machine:** Alur kerja remediasi ketat di mana Developer tidak dapat menutup tiket sendiri tanpa validasi ulang (*Re-test*) dari Security Analyst.
3. **Pelacakan SLA Otomatis:** Batas waktu penanganan berbasis keparahan (*Critical* 24 jam, *High* 72 jam, *Medium* 7 hari, *Low* 14 hari).
4. **Audit Trail *Immutable*:** Rekam jejak permanen untuk setiap perubahan status tiket.

---

## 👥 Akun Simulasi (Demo Credentials)

| Role | Email | Password | Hak Akses Utama |
|---|---|---|---|
| **Admin** | `admin@vulntrack.com` | `password123` | Akses penuh, manajemen aset, lihat semua tiket & audit log. |
| **Security Analyst** | `analyst@vulntrack.com` | `password123` | Buat tiket baru, kalkulasi CVSS, triage, verifikasi ulang (*Re-test: Lulus/Gagal*). |
| **Developer** | `dev@vulntrack.com` | `password123` | Hanya melihat tiket yang ditugaskan (*assignee*), mulai perbaikan, dan submit catatan patch. |

*(Tersedia tombol auto-fill untuk akun demo di halaman login).*

---

## 🔄 Alur Kerja (Strict State-Machine)

```text
[Open] ─── (Triage / Assign) ───▶ [Triaged]
                                      │
                                      ▼ (Mulai Perbaikan)
                              [In Remediation] ◀──┐
                                      │           │ (Re-test GAGAL)
                                      ▼           │
                                   [Re-test] ─────┘
                                      │
                                      ▼ (Re-test LULUS)
                                  [Closed]
```

- **Open:** Tiket baru dilaporkan oleh Security Analyst, batas SLA mulai berjalan.
- **Triaged:** Tiket dikonfirmasi dan ditugaskan ke Developer penanggung jawab.
- **In Remediation:** Developer sedang melakukan perbaikan celah keamanan.
- **Re-test:** Developer mengirim ringkasan patch & link PR/commit. Menunggu verifikasi Analyst.
- **Closed:** Security Analyst memverifikasi patch telah tuntas dan menutup tiket.

---

## 🚀 Cara Menjalankan Aplikasi

Pastikan PHP 8.1+ telah terpasang (misalnya via XAMPP, Laragon, atau binary PHP mandiri).

### Opsi 1: PHP Built-in Server (Rekomendasi)
Buka terminal di root folder `VulnTrack`, lalu jalankan:

```bash
php -S 127.0.0.1:8000 -t public
```

Buka peramban di: **`http://127.0.0.1:8000`**

### Opsi 2: Apache / XAMPP / Laragon
Jika menggunakan Apache (misal `htdocs/VulnTrack`):
- Langsung akses `http://localhost/VulnTrack/` (otomatis dialihkan ke folder `public/`).
- Direktori `app/` dan `storage/` sudah dilindungi dengan `.htaccess` agar tidak dapat diakses langsung via web browser.

---

## 📁 Struktur Direktori

```text
VulnTrack/
├── app/                        # Logika aplikasi (di luar document root)
│   ├── lib/                    # Kelas helper & domain
│   │   ├── AssetRepository.php      # Pengelolaan entitas aset
│   │   ├── Auth.php                 # Autentikasi Session & RBAC
│   │   ├── Csrf.php                 # Proteksi token anti-CSRF
│   │   ├── Cvss.php                 # Kalkulator CVSS v3.1 server-side
│   │   ├── JsonStore.php            # Penyimpanan file JSON dengan file lock (flock)
│   │   ├── Sla.php                  # Perhitungan deadline & status SLA
│   │   ├── ValidationException.php  # Exception validasi form
│   │   ├── VulnerabilityService.php # Layanan tiket, catatan patch & audit log
│   │   ├── Workflow.php             # Strict state-machine rules
│   │   └── helpers.php              # Sanitasi (XSS escaping), badges, dsb.
│   ├── views/                  # Template tampilan HTML/PHP
│   │   ├── layout/             # Header, navbar, footer
│   │   ├── partials/           # Komponen tabel tiket
│   │   └── error.php           # Template error (403, 404, 419, dsb.)
│   ├── bootstrap.php           # Autoloader, inisialisasi session & security headers
│   ├── config.php              # Konstanta aplikasi & batas upload
│   ├── seed.php                # Data awal tiket, aset, dan riwayat audit
│   └── users.php               # Akun pengguna dengan password hash Bcrypt
├── public/                     # Document root web server
│   ├── assets/
│   │   ├── css/app.css         # Styling modern enterprise
│   │   └── js/                 # Kalkulator CVSS, Chart.js, DataTables, Login
│   ├── assets.php              # Kelola aset (Admin)
│   ├── audit_log.php           # Log audit immutable
│   ├── create_ticket.php       # Form laporan celah + kalkulator CVSS
│   ├── dashboard.php           # KPI cards, grafik distribusi, tabel tiket
│   ├── detail_ticket.php       # Detail tiket & aksi state-machine dinamis
│   ├── download.php            # Download PoC aman (otorisasi diperiksa)
│   ├── index.php               # Entry redirect ke dashboard / login
│   ├── login.php               # Halaman login & akun demo
│   ├── logout.php              # Logout dengan proteksi POST + CSRF
│   ├── ticket_action.php       # Endpoint transisi state-machine
│   └── vulnerabilities.php     # Daftar seluruh tiket dengan filter status
├── storage/                    # Penyimpanan runtime (data.json & uploads PoC)
├── agent.md                    # Panduan pengerjaan Checkpoint 1
├── konteks.md                  # Spesifikasi & latar belakang proyek
└── README.md                   # Dokumentasi instalasi & arsitektur
```

---

## 🛡️ Aspek Keamanan yang Diterapkan
- **Proteksi XSS:** Seluruh output data user di-escape menggunakan fungsi `e()` (`htmlspecialchars` dengan flag `ENT_QUOTES`).
- **Proteksi CSRF:** Setiap form mutasi data (POST) divalidasi dengan token session acak 32-byte.
- **Proteksi IDOR & RBAC:** Pengecekan peran dan kepemilikan tiket dilakukan di sisi server sebelum merender data atau menjalankan aksi.
- **Penyimpanan Upload PoC Aman:** File PoC disimpan di luar direktori publik (`storage/uploads/`), divalidasi tipe MIME dan ekstensi whitelist, serta diberi nama hash acak.
- **Strict State Enforcement:** Tombol aksi hanya muncul jika status dan role sesuai, dan divalidasi ulang di backend sebelum transisi status diterapkan.

