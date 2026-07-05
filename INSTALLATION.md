# 📖 Panduan Instalasi Sistem Pendaftaran PKL

## Prerequisites

Sebelum memulai, pastikan Anda sudah menginstall:
- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Git**
- **Browser modern** (Chrome, Firefox, Edge, Safari)

## Langkah-langkah Instalasi

### 1. Clone Repository

```bash
# Buka Command Prompt / Terminal
cd C:\xampp\htdocs

# Clone repository
git clone https://github.com/renatarizkiandini1/Sistem-Pendaftaran-PKL.git Pendaftaran_PKL

# Masuk ke folder project
cd Pendaftaran_PKL
```

### 2. Start XAMPP

1. Buka **XAMPP Control Panel**
2. Klik tombol **"Start"** untuk:
   - ✅ Apache
   - ✅ MySQL
3. Tunggu sampai status berubah menjadi **"Running"** (warna hijau)

### 3. Setup Database

1. Buka browser dan akses:
   ```
   http://localhost/Pendaftaran_PKL/setup_db.php
   ```

2. Tunggu sampai muncul pesan:
   ```
   ✓ Database berhasil dibuat
   ```

3. Jika ada error, buka phpMyAdmin:
   ```
   http://localhost/phpmyadmin
   ```
   - Buat database baru dengan nama `db_pkl`
   - Import file SQL jika ada

### 4. Buat Tabel Sertifikat

1. Buka:
   ```
   http://localhost/Pendaftaran_PKL/create_sertifikat_table.php
   ```

2. Tunggu sampai muncul pesan:
   ```
   ✓ Tabel sertifikat berhasil dibuat/sudah ada
   ```

### 5. Tambah Data Perusahaan

1. Buka:
   ```
   http://localhost/Pendaftaran_PKL/add_perusahaan.php
   ```

2. Tunggu sampai muncul tabel dengan status:
   ```
   Total perusahaan baru ditambahkan: 15
   ```

### 6. Setup Akun Pembimbing

1. Buka:
   ```
   http://localhost/Pendaftaran_PKL/setup_pembimbing.php
   ```

2. Tunggu sampai muncul pesan:
   ```
   ✓ Akun pembimbing berhasil dibuat!
   Username: pembimbing
   Password: pembimbing123
   ```

### 7. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/Pendaftaran_PKL/
```

## Login Credentials

### Admin
```
Username: admin
Password: admin123
```

### Pembimbing
```
Username: pembimbing
Password: pembimbing123
```

### Siswa (Contoh)
```
Username: siswa
Password: siswa123
```

## Troubleshooting

### Error: "Koneksi gagal"
**Solusi:**
1. Pastikan MySQL sudah running di XAMPP
2. Cek file `db.php`:
   ```php
   $host     = "localhost";
   $user     = "root";
   $password = "";
   $database = "db_pkl";
   ```
3. Buka phpMyAdmin dan verifikasi database `db_pkl` ada

### Error: "Tabel tidak ditemukan"
**Solusi:**
1. Jalankan `setup_db.php` lagi
2. Atau buka phpMyAdmin dan import SQL manual

### Error: "File upload gagal"
**Solusi:**
1. Buat folder `uploads`:
   ```bash
   mkdir uploads
   mkdir uploads/surat
   ```
2. Set permission:
   ```bash
   chmod 777 uploads
   chmod 777 uploads/surat
   ```

### Error: "Username tidak ditemukan"
**Solusi:**
1. Jalankan `setup_pembimbing.php`
2. Atau buat akun manual di phpMyAdmin:
   - Buka tabel `user`
   - Insert data baru dengan password di-hash menggunakan `password_hash()`

### Error: "Blank page / White screen"
**Solusi:**
1. Cek error log di `C:\xampp\apache\logs\error.log`
2. Pastikan PHP error reporting aktif
3. Cek syntax PHP dengan:
   ```bash
   php -l filename.php
   ```

## Konfigurasi Lanjutan

### Mengubah Database Name
Edit file `db.php`:
```php
$database = "nama_database_baru";
```

### Mengubah Database User/Password
Edit file `db.php`:
```php
$user     = "username_baru";
$password = "password_baru";
```

### Mengubah Upload Directory
Edit di file yang menggunakan upload (contoh: `daftar_pkl.php`):
```php
$uploadDir = 'uploads/folder_baru/';
```

### Mengubah Max Upload Size
Edit file `php.ini` di `C:\xampp\php\`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
```

## Verifikasi Instalasi

Setelah instalasi selesai, verifikasi dengan:

1. **Cek Database:**
   ```
   http://localhost/phpmyadmin
   → Database: db_pkl
   → Tabel: 10 tabel (user, siswa, pembimbing, dll)
   ```

2. **Cek Login Admin:**
   ```
   http://localhost/Pendaftaran_PKL/
   → Username: admin
   → Password: admin123
   → Berhasil masuk ke dashboard_admin.php
   ```

3. **Cek Login Pembimbing:**
   ```
   http://localhost/Pendaftaran_PKL/
   → Username: pembimbing
   → Password: pembimbing123
   → Berhasil masuk ke dashboard_pembimbing.php
   ```

4. **Cek Fitur Upload:**
   ```
   → Daftar PKL
   → Upload file
   → File tersimpan di folder uploads/surat/
   ```

## Struktur Folder

```
Pendaftaran_PKL/
├── index.html                    # Halaman login
├── login.php                     # Proses login
├── logout.php                    # Proses logout
├── db.php                        # Konfigurasi database
├── sidebar.php                   # Komponen sidebar
├── functions.php                 # Helper functions
├── style.css                     # Styling
├── script.js                     # JavaScript
│
├── Admin Pages/
│   ├── dashboard_admin.php
│   ├── admin_pendaftaran.php
│   ├── admin_pembimbing.php
│   ├── admin_siswa.php
│   ├── admin_perusahaan.php
│   ├── admin_pengumuman.php
│   ├── admin_sertifikat.php
│   └── admin_tugaskan_pembimbing.php
│
├── Pembimbing Pages/
│   ├── dashboard_pembimbing.php
│   ├── siswa_bimbingan.php
│   ├── detail_logbook.php
│   └── beri_nilai.php
│
├── Siswa Pages/
│   ├── dashboard_siswa.php
│   ├── daftar_pkl.php
│   ├── logbook.php
│   ├── nilai_siswa.php
│   ├── profil_siswa.php
│   └── pengumuman_siswa.php
│
├── Setup Scripts/
│   ├── setup_db.php
│   ├── setup_pembimbing.php
│   ├── add_perusahaan.php
│   ├── create_sertifikat_table.php
│   └── upgrade_database.php
│
├── Utility Scripts/
│   ├── export_pendaftaran.php
│   └── hapus_pendaftaran.php
│
├── Documentation/
│   ├── README.md
│   ├── INSTALLATION.md (file ini)
│   ├── ALUR_SISTEM_DETAIL.md
│   ├── PERBAIKAN_SISTEM.md
│   └── AKSES_LOGBOOK.md
│
├── uploads/                      # Folder untuk upload file
│   └── surat/                    # Folder untuk surat permohonan
│
└── .gitignore                    # Git ignore file
```

## Next Steps

Setelah instalasi berhasil:

1. **Baca Dokumentasi:**
   - `ALUR_SISTEM_DETAIL.md` - Alur sistem lengkap
   - `PERBAIKAN_SISTEM.md` - Fitur-fitur perbaikan
   - `AKSES_LOGBOOK.md` - Dokumentasi logbook

2. **Test Fitur:**
   - Login sebagai Admin
   - Login sebagai Pembimbing
   - Login sebagai Siswa
   - Test semua fitur

3. **Customize:**
   - Ubah warna/tema di `style.css`
   - Ubah data perusahaan
   - Tambah pembimbing baru
   - Tambah siswa baru

## Support

Jika ada masalah:
1. Cek file error log
2. Baca dokumentasi yang tersedia
3. Buka issue di GitHub
4. Hubungi developer

---

**Status:** ✅ Ready to Use
**Last Updated:** 2024
**Version:** 1.0.0
