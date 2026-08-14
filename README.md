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

---
<owner1@gmail.com>
<owner2@gmail.com>
<owner3@gmail.com>

