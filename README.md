# 💼 SIPEG API - Sistem Informasi Penggajian Karyawan

RESTful API backend untuk **SIPEG (Sistem Informasi Penggajian Karyawan)** yang dibangun menggunakan **Laravel**. Sistem ini dirancang dengan arsitektur terpisah (*decoupled architecture*) untuk melayani Single Page Application (SPA) Vue 3 yang di-deploy di Cloudflare Pages.

Backend ini menangani otentikasi berbasis Sanctum, verifikasi header `X-API-KEY`, navigasi menu dinamis berbasis database, pengelolaan master karyawan/departemen, kalkulasi payroll bulanan, hingga rekapitulasi laporan.

---

## 🚀 Fitur Utama

- **Keamanan & Autentikasi Berlapis**:
  - Otentikasi berbasis token dengan **Laravel Sanctum** (`Authorization: Bearer <token>`).
  - Pembatasan akses request khusus menggunakan header kustom **`X-API-KEY`**.
  - Konfigurasi CORS tersentralisasi untuk integrasi lintas domain frontend.
- **Dynamic Database-Driven Menu**:
  - Navigasi menu dinamis bersumber dari tabel database `menus` (`order_index`, ikon Font Awesome, dan status aktif).
- **Master Data Karyawan & Departemen**:
  - Auto-generate kode unik karyawan (`EMP-xxxx`) dan departemen (`DPT-xxx`).
  - Pencarian multi-parameter, penyaringan (*filter*) departemen, serta paginasi data.
  - Import massal karyawan via file CSV dengan verifikasi kode departemen otomatis.
- **Transaksi Payroll Bulanan**:
  - Generate slip gaji massal satu klik untuk seluruh karyawan aktif pada periode bulan/tahun berjalan.
  - Dukungan penyesuaian potongan (*deductions*) dan perubahan status pembayaran (*draft* / *paid*).
  - Penomoran kode transaksi otomatis berformat `SLR-YYYYMM-xxxx`.
- **Rekapitulasi Laporan Penggajian**:
  - Kalkulasi agregat grand total (Gaji Pokok, Tunjangan, Potongan, Gaji Bersih) per periode dan departemen.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 11 / 10
- **Language**: PHP ^8.2
- **Database**: MySQL
- **Authentication**: Laravel Sanctum

---

## 📋 Struktur Basis Data Utama

1. **`users` / `employees`**: Data profil karyawan, departemen, jabatan, gaji pokok, dan tunjangan.
2. **`departments`**: Data unit kerja dengan atribut `department_code`, `name`, dan `sub_dept`.
3. **`salary_transactions`**: Riwayat transaksi penggajian, status pembayaran, serta rincian nominal gaji bersih.git ini