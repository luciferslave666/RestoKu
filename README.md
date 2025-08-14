# RestoKu - Aplikasi Kasir & Manajemen Restoran Berbasis Web

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

RestoKu adalah aplikasi Point of Sale (POS) atau sistem kasir berbasis web yang dirancang untuk membantu mengelola operasional restoran atau kafe skala kecil hingga menengah. Dibangun secara prosedural menggunakan PHP Native, aplikasi ini fokus pada kemudahan penggunaan dan fungsionalitas inti yang esensial.

![Screenshot Aplikasi RestoKu](https://i.ibb.co/FbfV3D7/Screenshot-2024-03-24-114944.png)

---

## Daftar Isi
1.  [Fitur Unggulan](#fitur-unggulan)
2.  [Teknologi](#teknologi)
3.  [Prasyarat](#prasyarat)
4.  [Panduan Instalasi](#panduan-instalasi)
5.  [Alur Penggunaan](#alur-penggunaan)
6.  [Kredensial Login](#kredensial-login)
7.  [Struktur Folder](#struktur-folder)
8.  [Kontribusi](#kontribusi)
9.  [Lisensi](#lisensi)

---

## Fitur Unggulan

Aplikasi ini menyediakan berbagai fitur untuk mempermudah manajemen restoran:

* **👤 Manajemen Autentikasi**
    * Sistem **Login** dan **Logout** yang aman untuk membatasi akses ke dasbor admin.
    * Membedakan level akses pengguna (misalnya: admin, kasir).

* **🍔 Manajemen Menu**
    * **Tambah Menu**: Menambahkan item menu baru beserta nama, harga, dan kategori.
    * **Lihat Menu**: Menampilkan semua menu yang tersedia dalam format tabel yang mudah dibaca.
    * **Ubah Menu**: Memperbarui detail menu seperti harga atau nama.
    * **Hapus Menu**: Menghapus item menu yang sudah tidak tersedia.

* **🛒 Manajemen Transaksi**
    * Membuat pesanan baru untuk pelanggan.
    * Mencatat item yang dipesan dan jumlahnya.
    * Menghitung total pembayaran secara otomatis.

* **👥 Manajemen Pengguna**
    * Admin dapat mengelola data pengguna lain yang bisa mengakses sistem.

* **📈 Laporan Penjualan**
    * Melihat riwayat semua transaksi yang telah terjadi.
    * Fitur ini membantu pemilik untuk melacak pendapatan dan performa penjualan.

---

## Teknologi

* **Backend**: PHP Native (Prosedural)
* **Frontend**: HTML, Tailwind CSS, JavaScript (untuk interaktivitas minor)
* **Database**: MySQL / MariaDB
* **Web Server**: Apache / Nginx (direkomendasikan menggunakan XAMPP atau Laragon)

---

## Prasyarat

Sebelum memulai instalasi, pastikan sistem Anda telah terpasang perangkat lunak berikut:
* **Web Server Lokal**: [XAMPP](https://www.apachefriends.org/index.html) atau [Laragon](https://laragon.org/download/).
* **Web Browser**: Google Chrome, Mozilla Firefox, atau browser modern lainnya.
* **Git** (Opsional, untuk proses cloning).

---

## Panduan Instalasi

Ikuti langkah-langkah ini untuk menjalankan proyek secara lokal:

1.  **Clone Repositori**
    Buka terminal atau Git Bash, lalu jalankan perintah:
    ```bash
    git clone [https://github.com/luciferslave666/RestoKu.git](https://github.com/luciferslave666/RestoKu.git)
    ```
    Atau unduh file ZIP langsung dari halaman GitHub.

2.  **Pindahkan Folder Proyek**
    Pindahkan folder `RestoKu` ke dalam direktori web server Anda:
    -   `C:\xampp\htdocs\` jika menggunakan XAMPP.
    -   `C:\laragon\www\` jika menggunakan Laragon.

3.  **Buat dan Impor Database**
    -   Jalankan XAMPP/Laragon, lalu start service **Apache** dan **MySQL**.
    -   Buka browser dan akses **phpMyAdmin** di `http://localhost/phpmyadmin`.
    -   Buat database baru dengan nama `db_resto`.
    -   Pilih database `db_resto`, lalu klik tab **"Import"**.
    -   Pilih file `db_resto.sql` yang ada di dalam folder proyek dan klik "Go" atau "Import".

4.  **Konfigurasi Koneksi**
    -   Buka file `config/koneksi.php` menggunakan teks editor.
    -   Pastikan detail koneksi sudah sesuai dengan pengaturan MySQL Anda (umumnya pengaturan default sudah benar jika menggunakan XAMPP/Laragon).
    ```php
    <?php
    $koneksi = mysqli_connect("localhost", "root", "", "db_resto");

    if (mysqli_connect_errno()) {
        echo "Koneksi database gagal : " . mysqli_connect_error();
    }
    ?>
    ```

5.  **Jalankan Aplikasi**
    -   Buka browser Anda dan navigasi ke URL:
    ```
    http://localhost/RestoKu
    ```
    -   Aplikasi siap digunakan!

---

## Alur Penggunaan

1.  Buka aplikasi dan login menggunakan kredensial yang tersedia.
2.  Setelah masuk ke dasbor, Anda dapat memilih menu di panel navigasi.
3.  Masuk ke halaman **Menu** untuk menambah atau mengubah daftar makanan dan minuman.
4.  Masuk ke halaman **Transaksi** untuk membuat pesanan baru dari pelanggan.
5.  Gunakan halaman **Laporan** untuk mereview semua transaksi yang sudah selesai.

---

## Kredensial Login

Anda dapat menggunakan akun default di bawah ini untuk masuk ke sistem setelah instalasi selesai.

| Username | Password | Level   |
| :------- | :------- | :------ |
| **admin** | **admin** | Admin   |
| **kasir** | **kasir** | Kasir   |

---

## Struktur Folder
.
├── admin/              # Berisi file-file utama untuk halaman admin (dasbor, menu, dll.)
│   ├── index.php       # Halaman dasbor utama
│   └── ...
├── assets/             # Aset statis (gambar, CSS, JS)
├── auth/               # Skrip untuk proses autentikasi (login, logout, registrasi)
├── config/             # File konfigurasi, termasuk koneksi.php
├── index.php           # Halaman login utama
├── db_resto.sql        # File dump SQL untuk struktur dan data awal database
└── README.md           # File dokumentasi yang sedang Anda baca

## Kontribusi

Kontribusi untuk pengembangan proyek ini sangat diterima. Jika Anda ingin berkontribusi, silakan lakukan Fork pada repositori ini, buat perubahan pada branch Anda sendiri, dan ajukan Pull Request.

---

## Lisensi

Proyek ini tidak memiliki lisensi spesifik, sehingga semua hak cipta dilindungi. Anda bebas untuk menggunakan dan memodifikasi kode ini untuk keperluan belajar dan pribadi.

---

Dibuat dengan ❤️ oleh [luciferslave666](https://github.com/luciferslave666).
