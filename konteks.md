# Vuln Track

**Collaborative Vulnerability Lifecycle & Security Remediation Management Platform**

## 1. Konteks dan Latar Belakang Proyek
Dalam siklus pengembangan perangkat lunak (SDLC) modern, terutama di lingkungan perusahaan *startup*, pelaporan celah keamanan sering kali dikelola secara sporadis menggunakan *spreadsheet*, email, atau grup pesan instan. Pendekatan manual ini menimbulkan beberapa *pain points* utama:
- Tidak ada standardisasi penentuan skor keparahan (*severity*) secara objektif.
- Hilangnya pelacakan batas waktu perbaikan (SLA).
- Sering terjadi miskomunikasi antara *Security Analyst* (penemu celah) dan *Developer* (penambal celah).
- Sulitnya melacak rekam jejak audit (*audit trail*) tentang proses verifikasi perbaikan.

Berbeda dengan platform *bug bounty* publik seperti HackerOne atau platform *issue tracking* umum seperti Jira, **Vuln Track** secara spesifik dirancang sebagai platform internal yang menjembatani komunikasi DevSecOps dengan memberlakukan aturan tata kelola keamanan yang terstruktur dan ketat.

## 2. Tujuan Proyek
Aplikasi Vuln Track dibangun dengan tujuan:
1. **Sentralisasi Manajemen Kerentanan:** Menyediakan satu portal khusus untuk memantau seluruh siklus hidup celah keamanan dari mulai pelaporan hingga penutupan tiket.
2. **Standardisasi Penilaian Risiko:** Mengintegrasikan kalkulator CVSS v3.1 interaktif agar penentuan tingkat keparahan (*severity*) berbasis standar internasional.
3. **Penegakan Alur Kerja (Strict State-Machine):** Memastikan proses remediasi berjalan sesuai standar keamanan, di mana *Developer* tidak bisa menutup isu secara sepihak tanpa adanya validasi (Re-test) dari *Security Analyst*.
4. **Kepatuhan & Transparansi:** Menyediakan log audit *immutable* dan indikator pelacakan SLA untuk memastikan setiap celah diperbaiki sesuai tenggat waktunya.

## 3. Tech Stack
Aplikasi ini dibangun menggunakan arsitektur monolitik/MVC yang berfokus pada kecepatan pengembangan, keamanan, dan fungsionalitas UI yang interaktif.

**Frontend:**
- **HTML5 & CSS3:** Markup semantik dan *styling*.
- **Bootstrap 5:** Framework CSS utama (responsiveness, grid, modals, badges).
- **JavaScript (Vanilla ES6+):** Manipulasi DOM, kalkulasi skor CVSS sisi klien, dan Fetch API (AJAX).
- **Chart.js:** Visualisasi grafik statistik keamanan (Pie Chart, Bar Chart).
- **DataTables.js:** Tabel data interaktif dengan fitur *search*, *sort*, dan *pagination*.

**Backend & Database:**
- **PHP (Native OOP / Laravel):** Pemrosesan logika bisnis, *routing*, dan penyediaan RESTful API / SSR.
- **MySQL / MariaDB:** RDBMS untuk menjaga integritas relasi antar-data (tiket, pengguna, log).

**Keamanan & Tools Tambahan:**
- **Autentikasi:** Session & JWT Management, Bcrypt Password Hashing, Role-Based Access Control (RBAC).
- **Proteksi:** Prepared Statements (mencegah SQLi), CSRF Token, dan Input Sanitization (XSS).
- **Tools:** Git/GitHub (Version Control), Figma (UI/UX), Postman (API Testing).

## 4. Alur Kerja (Workflow)
Siklus hidup sebuah temuan kerentanan (*finding*) diatur dengan tata kelola (*state-machine*) yang ketat:

1. **Input & Skoring (`Open`):** 
   - *Security Analyst* menemukan celah (misal: IDOR, XSS), membuat laporan, memilih aset, mengunggah *Proof of Concept* (PoC), dan mengisi kalkulator metrik CVSS v3.1.
   - Sistem menetapkan tenggat waktu SLA (Critical: 24 jam, High: 72 jam, Medium: 7 hari, Low: 14 hari).
2. **Penugasan (`Triaged`):** 
   - Laporan ditugaskan ke *Developer* terkait. Notifikasi SLA berjalan.
3. **Perbaikan (`In Remediation`):** 
   - *Developer* mulai menambal celah.
   - Setelah selesai, *Developer* menyubmit catatan perbaikan (*patch notes*) dan *link commit/Pull Request*.
4. **Verifikasi Ulang (`Re-test`):** 
   - *Security Analyst* melakukan pengujian terhadap hasil perbaikan *Developer*.
   - Jika belum tuntas, tiket dikembalikan ke status `In Remediation`.
5. **Penutupan (`Closed`):** 
   - Jika perbaikan tervalidasi aman, *Analyst* menutup tiket. Seluruh histori tercatat permanen di sistem audit log.

## 5. Struktur Data Utama (Entity Schema)
Basis data dirancang untuk mendukung relasi antar-pengguna, aset, dan riwayat perbaikan secara transparan.

- **`users`**
  - Menyimpan data akun pengguna dan peran (Admin, Security Analyst, Developer).
  - Kolom: `id`, `name`, `email`, `password`, `role`, `created_at`.
- **`assets`**
  - Menyimpan data aplikasi, server, atau *environment* yang diuji.
  - Kolom: `id`, `asset_name`, `environment` (production/staging), `description`.
- **`vulnerabilities`**
  - Tabel utama untuk menyimpan laporan celah keamanan (tiket).
  - Kolom: `id`, `title`, `asset_id`, `reporter_id` (Analyst), `assignee_id` (Developer), `cvss_score`, `severity`, `status`, `poc_file`, `sla_deadline`, `created_at`, `updated_at`.
- **`remediation_notes`**
  - Menyimpan dokumentasi dari sisi *Developer* terkait cara *patching* dilakukan.
  - Kolom: `id`, `vulnerability_id`, `user_id`, `patch_summary`, `commit_url`, `created_at`.
- **`audit_logs`**
  - Menyimpan *immutable records* untuk setiap perubahan status pada tiket.
  - Kolom: `id`, `vulnerability_id`, `user_id`, `action`, `old_status`, `new_status`, `timestamp`.