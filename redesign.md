# Instruksi Revisi UI/UX VulnTrack (Iterasi 2) & Penambahan Fitur Forum

Tolong lakukan *refactoring* kembali pada antarmuka VulnTrack (Login dan Dashboard) berdasarkan masukan terbaru, serta tambahkan satu fitur halaman baru untuk komunikasi tim. Desain sebelumnya masih terlalu rumit, terlalu banyak teks/penjelasan yang langsung ditampilkan, dan warnanya belum mencerminkan identitas aplikasi *cybersecurity* yang premium.

Terapkan panduan desain dan spesifikasi fitur berikut:

## 1. Simplifikasi Layout & Teks (Minimalisir "Clutter")
*   **Hapus Teks Berlebihan:** Tampilan saat ini terlalu banyak kalimat panjang (terutama di halaman Login yang menampilkan "Alur Remediasi Wajib" secara gamblang).
*   **Segmen Khusus Penjelasan:** Sembunyikan semua teks panjang, instruksi, atau penjelasan alur kerja. Pindahkan teks-teks tersebut ke dalam **Modal**, **Accordion**, atau **halaman/tab khusus "Bantuan & Panduan"**. Layar utama (Login & Dashboard) harus sebersih dan sesederhana mungkin, hanya berisi elemen operasional.
*   **Copywriting:** Persingkat semua judul dan sub-judul. (Contoh: "Tata kelola remediasi celah keamanan internal terpusat" ubah menjadi sekadar "VulnTrack Login" atau hilangkan sama sekali jika tidak perlu).

## 2. Pembaruan Tema Warna (Cybersecurity Corporate Style)
*   **Warna Utama (Aksen):** Ganti dominasi warna biru dengan **Oranye Kemerahan** (terinspirasi dari *brand* seperti Cloudflare, Palo Alto, atau CrowdStrike). Contoh hex code yang bisa dipakai: `#F38020` (Oranye Cloudflare) atau `#F04E23` (Merah-Oranye Palo Alto).
*   **Warna Latar & Netral:** Gunakan latar belakang putih bersih (`#ffffff`) atau abu-abu sangat muda (`#f8f9fa`). Jangan gunakan warna yang terlalu mencolok (hindari neon).
*   **Warna Teks:** Gunakan abu-abu gelap/slate (seperti `#334155` atau `#1e293b`) alih-alih hitam pekat agar lebih nyaman dibaca.
*   **Visual Mute:** Pertahankan *flat design*, shadow sangat tipis, dan garis batas yang lembut. Antarmuka harus terasa "dingin" dan sangat profesional, dengan aksen oranye hanya pada tombol utama atau indikator penting.

## 3. Penambahan Fitur Baru: "Team Discussion / Forum"
Tambahkan satu komponen/halaman baru bernama **"Forum Diskusi"** (atau "Team Chat") yang dapat diakses oleh semua *role* untuk saling memberikan *update*.
*   **Struktur Visual Forum:** Harus berwujud seperti papan diskusi profesional atau *feed* obrolan bergaya korporat.
*   **Data yang Wajib Ditampilkan per Pesan/Thread:**
    *   **Subjek / Topik Diskusi** (Dicetak tebal/jelas).
    *   **Nama Pengirim** (Misal: Admin, Security Analyst).
    *   **Email Pengirim** (Ditampilkan lebih kecil/redup di sebelah nama).
    *   **Waktu Pengiriman** (Timestamp, misal: "Hari ini, 14:30 WIB").
    *   **Isi Pesan/Update**.
*   **Interaksi:** Tambahkan form sederhana di bagian atas atau bawah forum untuk "Buat Diskusi Baru" (berisi input *Subjek* dan textarea *Isi Pesan*). *(Cukup buatkan UI mock-nya saja untuk sekarang jika backend belum siap)*.

## Urutan Pengerjaan untuk Agen:
1.  **Update Config/CSS Variables:** Ubah skema warna utama menjadi Oranye/Kemerahan.
2.  **Refactor Login.php:** Bersihkan teks yang panjang, jadikan form login sangat minimalis di tengah layar (atau split screen yang bersih).
3.  **Refactor Dashboard.php:** Persingkat teks, ubah warna badge/tombol agar selaras dengan tema baru.
4.  **Buat Halaman/Komponen Forum:** Buat UI untuk halaman obrolan/diskusi sesuai spesifikasi di poin 3.