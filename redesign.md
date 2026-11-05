# Instruksi Refactoring UI/UX VulnTrack

Tolong rombak ulang seluruh antarmuka pengguna (UI) untuk aplikasi VulnTrack (halaman Login dan Dashboard). Saat ini desainnya terlalu berantakan, menggunakan tema gelap (dark mode) dengan warna neon/biru/ungu tipikal AI, terlalu banyak emoji, dan elemen card yang melayang tidak beraturan.

Saya ingin desainnya diubah total menjadi **Light Theme (Putih Terang)** yang bersih, profesional, dan simpel, mengambil inspirasi dari struktur klasik seperti **AdminLTE**.

Gunakan framework CSS yang sudah ada di proyek (Tailwind/Bootstrap/Native), namun terapkan aturan gaya berikut:

## 1. Panduan Gaya (Style Guide)
*   **Tema Utama:** Light Mode. Gunakan warna latar belakang abu-abu sangat terang (misal: `#f4f6f9` atau `bg-gray-50`) untuk *body*, dan putih bersih (`#ffffff` atau `bg-white`) untuk area konten/card.
*   **Hapus Warna Neon:** Hilangkan semua warna biru/ungu neon atau gradien yang berlebihan. 
*   **Warna Aksen Klasik:** Untuk status atau metrik, gunakan warna *flat* yang solid (Biru untuk Info, Hijau untuk Sukses/Aman, Kuning/Oranye untuk Peringatan, Merah untuk Kritis/Danger).
*   **Tipografi & Ikon:**
    *   HAPUS semua penggunaan emoji sebagai ikon.
    *   Ganti dengan ikon vektor minimalis (seperti Heroicons, FontAwesome, atau Lucide).
    *   Gunakan font sans-serif yang bersih (seperti Inter, Roboto, atau sistem default) dengan hierarki teks yang jelas (ukuran dan ketebalan).
*   **Card & Kontainer:** 
    *   Hapus efek melayang (*heavy drop shadow*) dan garis batas samping (side-borders) tebal berwarna.
    *   Gunakan card dengan border abu-abu tipis (`border-gray-200`) dan *shadow* yang sangat halus/tipis, atau desain *flat* seutuhnya.

## 2. Redesain Halaman Dashboard
*   **Struktur Layout:** Buat layout klasik dengan *Sidebar* di kiri (bisa menggunakan warna gelap solid seperti `#222d32` atau putih bersih) dan *Top Navbar* yang rapi.
*   **Metrik/KPI Cards:** Buat berjejer rapi di atas (seperti gaya AdminLTE). Setiap card memiliki warna solid yang mencerminkan statusnya, atau latar putih dengan ikon dan teks berwarna solid. Jangan buat tumpang tindih.
*   **Tabel & Grafik:** Letakkan di dalam *box/card* putih bergaris batas tipis. Pastikan jarak (padding/margin) antar elemen lega dan tidak sumpek.

## 3. Redesain Halaman Login
*   **Layout:** Gunakan layout *split-screen* (dua kolom) atau satu *box* bersih di tengah layar.
*   **Visual:** Sisi kiri (jika split-screen) bisa menggunakan warna solid atau ilustrasi datar/minimalis (bukan roket neon/ungu, gunakan warna biru korporat atau putih bersih).
*   **Form:** Sisi kanan khusus form login dengan latar putih bersih. Input field harus memiliki border abu-abu yang jelas, tanpa bayangan melayang. Tombol login gunakan warna solid (contoh: biru korporat).

## Ringkasan DOs & DON'Ts untuk Agen:
*   **DO:** Gunakan latar putih/abu-abu terang, gunakan ikon vektor, buat border tipis, perhatikan *whitespace*, gunakan warna flat standar untuk label status.
*   **DON'T:** Jangan gunakan emoji, jangan gunakan warna neon, jangan buat card melayang/glowing, jangan gunakan dark mode.

Tolong berikan *update* kodenya secara bertahap, mulai dari layout utama (Header/Sidebar/Body), lalu komponen Dashboard, dan terakhir Login.