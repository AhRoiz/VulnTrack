# Instruksi Pengembangan Proyek: VulnTrack (Checkpoint 1 - PHP Session MVP)

**Konteks Utama:**
Baca file `konteks.md` di root folder untuk memahami tujuan bisnis, *strict state-machine workflow*, dan aturan SLA dari aplikasi VulnTrack.

**Tujuan Checkpoint 1:**
Bangun MVP (Minimum Viable Product) fungsional menggunakan **PHP Native** (tanpa framework) **TANPA KONEKSI DATABASE (MySQL)**. 
Sebagai pengganti database, gunakan **PHP `$_SESSION`** atau **File JSON** untuk menyimpan data sementara agar data tidak hilang saat halaman di- *refresh*. Fokus pada simulasi Autentikasi, pembuatan tiket yang benar-benar berfungsi (form submit), dan antarmuka UI/UX.

## Tech Stack Checkpoint 1:
- PHP 8.x (Routing sederhana & Logika Backend)
- Penyimpanan Sementara: `$_SESSION` Arrays atau File `data.json`
- HTML5 & CSS3
- Bootstrap 5 (via CDN)
- Vanilla JavaScript (Kalkulator CVSS sisi klien)
- Chart.js (via CDN)
- DataTables.js (via CDN)

## Daftar Tugas (Task List) untuk Agent:

### 1. Sistem Autentikasi & Role-Based Access Control (RBAC)
- Buat halaman `login.php`.
- **Hardcode 3 Akun Pengguna** berikut ke dalam array PHP untuk simulasi login:
  1. **Admin:** Email `admin@vulntrack.com`, Password `password123`, Role `admin` (Akses penuh & lihat semua tiket).
  2. **Security Analyst:** Email `analyst@vulntrack.com`, Password `password123`, Role `analyst` (Bisa buat tiket, CVSS, re-test).
  3. **Developer:** Email `dev@vulntrack.com`, Password `password123`, Role `developer` (Hanya lihat tiket yang di-assign padanya, submit patch).
- Simpan data user yang login ke dalam `$_SESSION['user']`. Buat fungsi proteksi halaman (redirect ke login jika belum ada session).
- Buat mekanisme Logout.

### 2. Setup Layout Utama & Routing
- Buat file layout dasar (misal: `header.php`, `footer.php`, `sidebar.php`).
- Buat Navbar di bagian atas dengan menu: **Dashboard | Kerentanan | Aset | Audit Log**.
- Tampilkan nama dan role user yang sedang login di pojok kanan atas Navbar.
- Tampilkan atau sembunyikan menu tertentu berdasarkan Role user (misal: menu 'Aset' hanya untuk Admin).

### 3. Implementasi Halaman Dashboard (`dashboard.php`)
- **KPI Cards:** Hitung total tiket, tiket Open, In Remediation, dll., dengan membaca data dari `$_SESSION['vulnerabilities']`.
- **Charts (Chart.js):** Tampilkan Pie Chart (berdasarkan Severity) dan Bar Chart (berdasarkan Status) yang datanya di-*inject* dari PHP ke script JS.
- **Tabel Tiket (DataTables.js):** Tampilkan daftar tiket (ID, Judul, Severity, Status, SLA) yang diambil dari `$_SESSION`.
  - Jika yang login adalah *Developer*, hanya tampilkan tiket dengan `assignee_id` miliknya.

### 4. Implementasi Fitur Buat Tiket & Kalkulator CVSS (`create_ticket.php`)
- **Hak Akses:** Halaman ini hanya boleh diakses oleh *Security Analyst* (dan Admin).
- Buat Form HTML (Judul, Dropdown Aset mock, Deskripsi, Assignee mock).
- **Logika Kalkulator CVSS v3.1 (Klien - JavaScript):**
  - Buat input untuk AV, AC, PR, UI, C, I, A.
  - Buat fungsi JS yang menghitung skor dan menentukan Severity & SLA secara *real-time* saat form diisi.
- **Pemrosesan Form (Backend - PHP):**
  - Tangkap data POST (Judul, Deskripsi, Skor CVSS, Severity, Status default = 'Open').
  - Simpan array data tiket baru ke dalam `$_SESSION['vulnerabilities']`.
  - Redirect kembali ke Dashboard dengan pesan sukses (Toast/Alert Bootstrap).

### 5. Halaman Detail Tiket & State-Machine Dasar (`detail_ticket.php?id=...`)
- Tampilkan data lengkap tiket berdasarkan ID yang dipilih.
- **Render UI Berdasarkan Role & Status:**
  - Jika status `Open`/`Triaged` dan user adalah **Developer**, tampilkan form "Catatan Perbaikan & Submit Patch".
  - Jika status `In Remediation` dan user adalah **Analyst**, tampilkan tombol "Verifikasi (Lulus/Gagal)".
- (Opsional/Nilai Plus) Buat *endpoint* PHP sederhana untuk mengubah status tiket di dalam array Session saat tombol-tombol tersebut ditekan.

### Aturan Tambahan untuk Agent:
- Gunakan UI modern bergaya enterprise (Bootstrap 5). Gunakan warna badge yang sesuai (Critical = Merah, High = Oranye, Medium = Biru, Low = Abu-abu).
- Susun kode PHP dengan rapi, pisahkan logika pemrosesan form dari tampilan HTML sebisa mungkin (meskipun belum menggunakan MVC penuh).