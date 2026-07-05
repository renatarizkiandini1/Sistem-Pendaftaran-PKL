# Sistem Pendaftaran PKL

Sistem informasi manajemen Praktik Kerja Lapangan (PKL) yang komprehensif dengan fitur lengkap untuk Admin, Pembimbing, dan Siswa.

## 📋 Daftar Isi
- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Penggunaan](#penggunaan)
- [Struktur Database](#struktur-database)
- [Alur Sistem](#alur-sistem)
- [Login Credentials](#login-credentials)
- [Dokumentasi](#dokumentasi)

## ✨ Fitur Utama

### 👨‍💼 Admin
- ✅ Dashboard dengan statistik dan task list
- ✅ Kelola pendaftaran (verifikasi, tolak, update status)
- ✅ Tugaskan pembimbing ke siswa
- ✅ Kelola data siswa, pembimbing, perusahaan
- ✅ Kelola pengumuman
- ✅ Generate sertifikat
- ✅ Export data ke Excel
- ✅ Activity logging untuk audit trail

### 👨‍🏫 Pembimbing
- ✅ Dashboard dengan overview siswa bimbingan
- ✅ Lihat daftar siswa dengan search & filter
- ✅ Verifikasi logbook siswa (satu per satu atau batch)
- ✅ Input nilai dengan 4 komponen (Kedisiplinan, Keterampilan, Sikap, Laporan)
- ✅ Preview nilai real-time dengan predikat (A/B/C/D)
- ✅ Ubah status PKL siswa
- ✅ Lihat catatan admin untuk setiap siswa
- ✅ Visualisasi kapasitas bimbingan

### 👨‍🎓 Siswa
- ✅ Dashboard dengan progress PKL
- ✅ Daftar PKL dengan pilihan perusahaan
- ✅ Edit/hapus pendaftaran (saat status Menunggu)
- ✅ Isi logbook harian (saat status Sedang PKL)
- ✅ Lihat nilai dari pembimbing
- ✅ Lihat pengumuman
- ✅ Lihat profil dan data PKL

## 🛠️ Teknologi

- **Backend:** PHP Native (Procedural)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **UI Framework:** Custom CSS dengan Boxicons
- **Charts:** Chart.js
- **Responsive:** Mobile-first design

## 📦 Instalasi

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Git
- Browser modern

### Langkah-langkah

1. **Clone repository**
```bash
git clone https://github.com/renatarizkiandini1/Sistem-Pendaftaran-PKL.git
cd Sistem-Pendaftaran-PKL
```

2. **Copy ke folder XAMPP**
```bash
cp -r Sistem-Pendaftaran-PKL C:\xampp\htdocs\Pendaftaran_PKL
```

3. **Start XAMPP**
- Buka XAMPP Control Panel
- Klik "Start" untuk Apache dan MySQL

4. **Setup Database**
- Buka `http://localhost/Pendaftaran_PKL/setup_db.php`
- Database akan dibuat otomatis

5. **Buat Tabel Sertifikat**
- Buka `http://localhost/Pendaftaran_PKL/create_sertifikat_table.php`

6. **Tambah Data Perusahaan**
- Buka `http://localhost/Pendaftaran_PKL/add_perusahaan.php`

7. **Setup Akun Pembimbing**
- Buka `http://localhost/Pendaftaran_PKL/setup_pembimbing.php`

## ⚙️ Konfigurasi

### Database Configuration
Edit file `db.php`:
```php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "db_pkl";
```

### Folder Uploads
Pastikan folder `uploads/` ada dan writable:
```bash
mkdir uploads
mkdir uploads/surat
chmod 777 uploads
chmod 777 uploads/surat
```

## 🚀 Penggunaan

### Akses Aplikasi
```
http://localhost/Pendaftaran_PKL/
```

### Login Credentials

#### Admin
- Username: `admin`
- Password: `admin123`

#### Pembimbing
- Username: `pembimbing`
- Password: `pembimbing123`

#### Siswa (Contoh)
- Username: `siswa`
- Password: `siswa123`

## 🗄️ Struktur Database

### Tabel Utama
- `user` - Data user (admin, pembimbing, siswa)
- `siswa` - Data siswa
- `pembimbing` - Data pembimbing
- `perusahaan` - Data perusahaan
- `pendaftaran` - Data pendaftaran PKL
- `logbook` - Logbook harian siswa
- `penilaian` - Nilai dari pembimbing
- `pengumuman` - Pengumuman untuk siswa
- `sertifikat` - Sertifikat PKL
- `activity_log` - Log aktivitas untuk audit trail

## 📊 Alur Sistem

### Status Flow PKL
```
Menunggu Verifikasi → Diterima → Sedang PKL → Menunggu Penilaian → Selesai
                   ↓
                Ditolak
```

### Alur Lengkap
1. **Siswa Daftar** → Status: Menunggu Verifikasi
2. **Admin Verifikasi** → Status: Diterima
3. **Admin Tugaskan Pembimbing** → Pembimbing assigned
4. **Pembimbing Ubah Status** → Status: Sedang PKL
5. **Siswa Isi Logbook** → Logbook entries
6. **Pembimbing Verifikasi Logbook** → Logbook verified
7. **Pembimbing Ubah Status** → Status: Menunggu Penilaian
8. **Pembimbing Input Nilai** → Nilai Final
9. **Pembimbing Ubah Status** → Status: Selesai
10. **Admin Generate Sertifikat** → Sertifikat created

## 📚 Dokumentasi

### File Dokumentasi
- `ALUR_SISTEM_DETAIL.md` - Alur sistem lengkap untuk semua role
- `PERBAIKAN_SISTEM.md` - Ringkasan perbaikan yang dilakukan
- `AKSES_LOGBOOK.md` - Dokumentasi akses logbook siswa
- `README.md` - File ini

### Fitur Penting

#### Validasi Status Flow
- Status hanya bisa maju, tidak boleh mundur
- Hanya bisa tolak dari "Menunggu Verifikasi"
- Mencegah perubahan status yang tidak valid

#### Logbook
- Siswa hanya bisa isi logbook saat status "Sedang PKL"
- Tanggal harus dalam range tanggal_mulai - tanggal_selesai
- Tidak boleh ada 2 logbook untuk tanggal yang sama
- Pembimbing bisa verifikasi satu per satu atau batch

#### Penilaian
- 4 komponen nilai: Kedisiplinan, Keterampilan, Sikap, Laporan
- Nilai akhir = rata-rata 4 komponen
- Predikat: A (≥90), B (≥75), C (≥60), D (<60)
- Status: Draft (bisa diubah) atau Final (terkunci)

#### Sertifikat
- Generate otomatis dengan nomor unik (SERT-YYYY-00001)
- Hanya untuk PKL dengan status "Selesai"
- Admin bisa hapus sertifikat jika diperlukan

## 🔒 Keamanan

- ✅ Prepared statements untuk SQL injection prevention
- ✅ Session-based authentication
- ✅ Role-based access control (RBAC)
- ✅ Input validation dan sanitization
- ✅ Activity logging untuk audit trail
- ✅ Password hashing dengan PHP password_hash()

## 📱 Responsive Design

- ✅ Desktop (>1024px)
- ✅ Tablet (768-1024px)
- ✅ Mobile (<768px)

## 🎨 Design System

- **Primary Color:** #3C91E6 (Blue)
- **Success Color:** #10B981 (Green)
- **Warning Color:** #F97316 (Orange)
- **Danger Color:** #EF4444 (Red)
- **Font:** Poppins
- **Border Radius:** 12px
- **Shadow:** Soft shadow untuk depth

## 📝 Lisensi

MIT License - Bebas digunakan untuk keperluan komersial maupun non-komersial

## 👤 Author

**Renata Rizki Andini**
- GitHub: [@renatarizkiandini1](https://github.com/renatarizkiandini1)
- Email: renatarizkiandini1@gmail.com

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan:
1. Fork repository
2. Buat branch feature (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

## 📞 Support

Jika ada pertanyaan atau masalah, silakan buka issue di GitHub atau hubungi email di atas.

---

**Status:** ✅ Production Ready
**Last Updated:** 2024
**Version:** 1.0.0
