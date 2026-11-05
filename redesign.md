# Instruksi Revisi UI/UX VulnTrack (Iterasi 3) & Penambahan Fitur Forum

Tolong lakukan *refactoring* kembali pada antarmuka VulnTrack (Login dan Dashboard). Desain sebelumnya terlalu polos dan kehilangan struktur aslinya, serta masih ada *bug* visual di komponen tabel. Terapkan spesifikasi berikut secara presisi:

## 1. Halaman Login (Minimalis, namun tetap Split-Screen)
*   **Pertahankan Layout 2 Kolom (Split):** Jangan buat menjadi satu *card* polos di tengah. Pertahankan layout awal di mana layar atau *card* utama terbagi dua:
    *   **Bagian Kiri (Branding):** Hanya berisi Logo dan Judul Aplikasi. Gunakan latar belakang warna utama aplikasi (atau variasi warnanya) dengan teks kontras. Pastikan sangat *clean*, hapus semua paragraf panjang dan poin-poin "Alur Remediasi".
    *   **Bagian Kanan (Form Login):** Latar belakang putih bersih. Hanya berisi *input* Email, Password, tombol Login, dan tombol *auto-fill* (Akun Demo) yang ditata rapi.
*   **Penempatan Penjelasan:** Jika ada teks panduan tambahan, letakkan di tombol kecil bertuliskan "Panduan" (bisa membuka *modal* atau tab baru), jangan diumbar di layar utama.

## 2. Pembaruan Tema Warna (Cybersecurity Corporate Style)
*   **Warna Utama:** Gunakan warna **Oranye/Kemerahan** (mirip Cloudflare, CrowdStrike, atau Palo Alto). Gunakan hex seperti `#F38020` atau `#F04E23` sebagai aksen utama (untuk tombol utama, *badge* status aktif, atau elemen *highlight*).
*   **Warna Netral:** Latar belakang halaman gunakan abu-abu sangat muda/putih bersih. Buat elemen *card* memiliki *shadow* yang sangat tipis dan halus (*soft flat design*). Jangan gunakan warna neon/mencolok.

## 3. Perbaikan Bug UI (Dashboard DataTables)
*   **Fix Dropdown "Tampilkan [10] baris":** Terdapat *bug* CSS di mana angka pada *dropdown* (komponen `<select>` *length menu*) bertumpuk atau keluar dari batas garis kotaknya. Tolong perbaiki *padding*, *line-height*, atau *width* dari elemen `<select>` tersebut agar angkanya berada pas di tengah kotak tanpa memotong *border*.

## 4. Pengembangan Halaman "Forum Diskusi"
Ubah konsep *chat* sederhana menjadi sistem **Forum** yang lebih terstruktur. Buat halaman atau komponen dengan spesifikasi berikut:
*   **Hierarki Subforum:** Tampilkan daftar kategori diskusi di panel samping atau atas (Misal: "General Updates", "Patching Coordination", "SLA Warnings").
*   **Threads (Topik):** Di dalam subforum, pengguna bisa membuat Thread/Topik baru.
*   **Isi Thread:** Saat Thread dibuka, tampilkan subjek di atas, diikuti daftar balasan. Setiap balasan wajib menampilkan: **Nama Pengirim**, **Email/Role**, **Waktu (Timestamp)**, dan **Isi Pesan**.
*   **Fitur "Subscribe":** Tambahkan tombol atau *toggle* **"Subscribe"** atau **"Ikuti Topik"** di dalam Thread. Berikan indikator visual (seperti ikon lonceng) bahwa pengguna akan mendapat notifikasi pembaruan dari topik tersebut. *(Cukup buatkan UI/UX-nya terlebih dahulu)*.

**Catatan Eksekusi:** Mulailah dari membenarkan layout Login dan CSS *dropdown* tabel terlebih dahulu, baru kemudian buat rancangan komponen Forum.