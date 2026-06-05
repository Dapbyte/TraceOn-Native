# TraceOn

![TraceOn](https://img.shields.io/badge/TraceOn-Task%20Monitoring%20System-2175B8?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Vanilla JS](https://img.shields.io/badge/Vanilla%20JS-ES%20Modules-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

TraceOn adalah sistem monitoring progress tugas / project berbasis **workspace** dan **card access**. Aplikasi ini dirancang untuk mahasiswa, baik untuk penggunaan personal maupun kerja tim, agar pembagian tugas, kontrol akses, dan progres pekerjaan bisa dipantau dengan lebih rapi, aman, dan terstruktur.

## 👨‍💻 Developer

### 1. M. Daffa Arrafi
**NPM:** 24081010035

### 2. Valentio Titolius Dwi Jaya
**NPM:** 24081010169

### 3. Ahmad Naufal Fijra Darmansyah
**NPM:** 24081010350


## ✨ Gambaran Singkat

TraceOn membantu kamu untuk:

- membuat workspace untuk satu project atau satu tim,
- membagi pekerjaan ke dalam card dan todo,
- memberi hak akses card secara spesifik ke member tertentu,
- memantau progress setiap card dan workspace,
- melihat aktivitas penting secara terpusat melalui activity log.


## 🚀 Fitur Utama

- **Autentikasi aman**: login, register, logout, session protection, dan CSRF token
- **Workspace management**: buat, rename, hapus workspace, invite code, dan join request
- **Role system**: Owner, Admin, Member
- **Card access**: kontrol akses detail per card untuk member
- **Todo management**: buat, edit, ubah status, dan hapus todo secara permanen
- **Progress tracking**: progress card dan workspace dihitung otomatis
- **Activity log**: pencatatan aktivitas penting, pagination, search, dan filter
- **Profile management**: update nama dan avatar

## 🛠️ Tech Stack

- **Backend**: PHP 8.3 Native OOP MVC
- **Database**: MySQL 8+
- **Frontend**: Vanilla JavaScript (ES Modules)
- **Styling**: CSS Custom Properties
- **Auth & Security**: Session, CSRF, PDO Prepared Statement, bcrypt


## 🔧 Cara Menjalankan Project

### 1) Siapkan environment
Pastikan PHP 8.3+ dan MySQL 8+ sudah tersedia di perangkatmu.

### 2) Install dependency
Jalankan Composer di root project:

```bash
composer install
```

### 3) Atur file `.env`
Buat atau sesuaikan file `.env` untuk konfigurasi database dan environment aplikasi.

Contoh umum:

```env
APP_ENV=local
APP_URL=http://localhost/traceon
DB_HOST=localhost
DB_NAME=traceon
DB_USER=root
DB_PASS=
BCRYPT_COST=12
```

### 4) Import database
Import file SQL / schema ke MySQL dari folder `migrations`, lalu pastikan tabel inti tersedia:
`users`, `workspaces`, `workspace_members`, `cards`, `card_access`, `todos`, `activities`, `login_attempts`.

### 5) Jalankan aplikasi
Akses aplikasi melalui web server yang mengarah ke folder `public/`.

Contoh:

```text
http://localhost/traceon/public
```

atau sesuai konfigurasi server yang kamu pakai.

## 👤 Cara Pakai Aplikasi

### Login / Register
- Daftar akun baru melalui halaman register.
- Login menggunakan email dan password.
- Session akan aktif setelah login berhasil.

### Membuat Workspace
- Setelah login, buka dashboard.
- Klik **Workspace Baru**.
- Isi nama workspace dan deadline bila diperlukan.
- Workspace akan dibuat beserta role **Owner** untuk pembuatnya.

### Join Workspace
- Klik **Join Workspace**.
- Masukkan invite code dari workspace tujuan.
- Jika valid, permohonan akan dikirim dan menunggu persetujuan.

### Mengelola Card
- Owner atau Admin dapat membuat, mengubah, dan menghapus card.
- Member hanya dapat mengubah todo pada card yang memang diberikan akses.

### Mengelola Todo
- Tambahkan todo ke dalam card.
- Ubah status todo menjadi belum, on progress, atau selesai.
- Progress card akan dihitung ulang otomatis.

### Melihat Aktivitas
- Buka tab **Activity** untuk melihat riwayat perubahan.
- Gunakan search, filter, dan pagination untuk menelusuri aktivitas.

## 🔐 Catatan Keamanan

TraceOn dirancang dengan beberapa perlindungan penting:

- PDO Prepared Statement untuk mencegah SQL Injection
- CSRF token untuk semua request mutasi
- session_regenerate_id(true) setelah login
- bcrypt untuk hashing password
- file upload avatar dibatasi dan divalidasi

## 📄 Tentang Proyek

TraceOn adalah sistem monitoring progress tugas / project berbasis workspace dan card access. Sistem ini mendukung kolaborasi mahasiswa untuk kebutuhan personal maupun tim, dengan fokus pada pembagian tugas, transparansi progres, dan kontrol akses yang lebih rapi. Konsep ini selaras dengan spesifikasi produk TraceOn yang menekankan workspace, role system, todo management, activity log, dan responsive UI. fileciteturn3file0L49-L57 fileciteturn3file0L59-L67

## 📌 Lisensi

Project ini dibuat untuk penyelesaian UAS mata kuliah Pemrograman Website.

---

**TraceOn** — *Monitor progress, control access, and collaborate with clarity.*
