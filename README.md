# SPK Rekomendasi Kost (SPKKOST)

Decision Support System (Sistem Pendukung Keputusan) untuk rekomendasi pemilihan tempat kost terbaik berbasis web, dibangun menggunakan framework **CodeIgniter 4**. Aplikasi ini dirancang untuk membantu calon penghuni kost dalam menentukan pilihan optimal berdasarkan kriteria-kriteria yang telah ditentukan (seperti harga, jarak, fasilitas, dan keamanan).

---

## Fitur Utama

- **Sistem Pendukung Keputusan:** Implementasi metode SPK (seperti SAW / AHP / TOPSIS) untuk perhitungan rekomendasi yang objektif.
- **Manajemen Data Kost:** Pengelolaan data kos-kosan, fasilitas, harga, dan lokasi secara dinamis.
- **Manajemen Kriteria & Bobot:** Fleksibilitas dalam mengubah kriteria penilaian dan bobot preferensi.
- **Dashboard Interaktif:** Halaman ringkasan informasi yang bersih dan responsif untuk admin maupun pengguna.

---

## Arsitektur & Keamanan Informasi

Aplikasi ini dibangun dengan memperhatikan standar penulisan kode yang aman (*Secure Coding Principles*) dan praktik terbaik Git:

- **SQL Injection Prevention:** Menggunakan *Query Builder* dan *Data Binding* bawaan CodeIgniter 4 untuk menangani komunikasi basis data dengan aman.
- **Cross-Site Scripting (XSS) Protection:** Implementasi *auto-escaping* pada *views* untuk mencegah injeksi skrip berbahaya.

---

## Prasyarat Sistem

Sebelum menjalankan proyek ini di lingkungan lokal, pastikan perangkat sudah memenuhi spesifikasi berikut:

- **PHP:** Versi 8.1 atau yang lebih baru (dengan ekstensi `intl`, `mbstring`, `sqlsrv` atau `mysqli` aktif)[cite: 2]
- **Database:** MySQL / MariaDB[cite: 2]
- **Dependency Manager:** Composer[cite: 2]
- **Local Server:** Laragon / XAMPP[cite: 2]

## Menyiapkan lingkungan lokal

1. Buat database MySQL/MariaDB bernama `kost_db` (atau sesuaikan namanya di `.env`).
2. Salin `.env.example` menjadi `.env`, lalu sesuaikan URL, kredensial database, dan kunci enkripsi lokal.
3. Pasang dependency dengan `composer install`.
4. Pada database baru yang masih kosong, jalankan `php spark migrate` lalu `php spark db:seed InitialCatalogSeeder`.

Migration membuat tabel aplikasi dan tabel sesi database. Seeder hanya mengisi katalog kampus, kriteria, dan fasilitas ketika tabelnya kosong; seeder tidak membuat akun atau mengimpor data operasional. Untuk database yang sudah berisi data, backup dan cocokkan skema terlebih dahulu sebelum menjalankan migration awal.

Foto properti disimpan di `public/uploads/kosts`, sedangkan dokumen identitas dan bukti pembayaran disimpan di `writable/uploads` dan dilayani hanya melalui route terotorisasi. Server aplikasi/deployment harus memberi izin tulis pada kedua direktori tersebut. File unggahan runtime diabaikan Git; `.gitignore` tidak otomatis melepas file yang sudah terlanjur dilacak.

Pada deployment yang sudah memiliki dokumen sensitif di `public/uploads/identities` atau `public/uploads/payments`, jalankan `php spark app:migrate-private-uploads` saat aplikasi dalam mode pemeliharaan. Perintah ini memindahkan file lama ke `writable/uploads` dengan nama tetap sehingga referensi database tidak perlu diubah. Aturan `.htaccess` juga menolak akses langsung ke file lama pada Apache; untuk Nginx atau server lain, pastikan URL `/uploads/identities/` dan `/uploads/payments/` diblokir di konfigurasi server.

---
<owner1@gmail.com>
<owner2@gmail.com>
<owner3@gmail.com>


---
