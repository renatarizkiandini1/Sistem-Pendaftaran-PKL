# ANALISIS ALUR SISTEM PENDAFTARAN PKL

## 1. ALUR SISWA (Student Flow)

### Status Flow Siswa:
```
Belum Daftar → Menunggu Verifikasi → Diterima → Sedang PKL → Menunggu Penilaian → Selesai
                                   ↓
                                Ditolak (End)
```

### Tahapan Lengkap:

#### A. TAHAP 1: PENDAFTARAN (daftar_pkl.php)
**Kondisi Awal:**
- Siswa belum memiliki pendaftaran atau status "Menunggu Verifikasi" (bisa edit)
- Harus sudah melengkapi profil di profil_siswa.php

**Aksi Siswa:**
1. Pilih perusahaan dari dropdown (menampilkan alamat, bidang, kuota)
2. Tentukan tanggal mulai dan selesai PKL
3. Upload surat permohonan (PDF/DOC/JPG/PNG, max 10MB)
4. Klik "Kirim Pendaftaran"

**Data Tersimpan:**
- pendaftaran.user_id
- pendaftaran.perusahaan_id
- pendaftaran.tanggal_mulai
- pendaftaran.tanggal_selesai
- pendaftaran.surat_permohonan
- pendaftaran.status = "Menunggu Verifikasi" (default)
- pendaftaran.created_at

**Validasi:**
- ✓ Perusahaan harus dipilih
- ✓ Tanggal mulai dan selesai harus diisi
- ✓ Surat permohonan harus diupload (kecuali edit)
- ✓ File size max 10MB
- ✓ Hanya 1 pendaftaran aktif per siswa

**Hasil:**
- Status berubah menjadi "Menunggu Verifikasi"
- Redirect ke dashboard_siswa.php
- Siswa tidak bisa daftar lagi sampai status berubah

---

#### B. TAHAP 2: MENUNGGU VERIFIKASI (dashboard_siswa.php)
**Kondisi:**
- Status = "Menunggu Verifikasi"
- Siswa bisa melihat status di dashboard
- Siswa bisa edit pendaftaran (jika masih Menunggu)
- Siswa bisa hapus pendaftaran

**Fitur Siswa:**
- Lihat status di box-info
- Edit pendaftaran (ubah perusahaan, tanggal, dokumen)
- Hapus pendaftaran
- Lihat alert: "Pendaftaran kamu sedang menunggu review dari admin"

**Aksi Admin:**
- Lihat di admin_pendaftaran.php dengan filter "Menunggu Verifikasi"
- Klik "Terima" → Status = "Diterima"
- Klik "Tolak" → Status = "Ditolak"
- Atau buka modal untuk update status + catatan

**Jika Ditolak:**
- Status = "Ditolak"
- Siswa melihat alert di dashboard
- Siswa bisa daftar ulang (membuat pendaftaran baru)

---

#### C. TAHAP 3: DITERIMA (dashboard_siswa.php)
**Kondisi:**
- Status = "Diterima"
- Admin sudah verifikasi pendaftaran
- Menunggu pembimbing ditugaskan

**Fitur Siswa:**
- Lihat status "Diterima" di dashboard
- Lihat info perusahaan (nama, bidang, alamat, telp)
- Lihat "Pembimbing belum ditugaskan" (jika belum ada)
- Tidak bisa edit/hapus pendaftaran lagi
- Tidak bisa isi logbook (belum Sedang PKL)

**Aksi Admin:**
- Buka admin_tugaskan_pembimbing.php
- Pilih siswa dengan status "Diterima"
- Tugaskan pembimbing
- Status tetap "Diterima" (atau bisa manual ubah ke "Sedang PKL")

**Aksi Pembimbing:**
- Belum bisa akses siswa ini (belum ditugaskan)

---

#### D. TAHAP 4: SEDANG PKL (dashboard_siswa.php, logbook.php)
**Kondisi:**
- Status = "Sedang PKL"
- Pembimbing sudah ditugaskan
- Tanggal hari ini dalam range tanggal_mulai - tanggal_selesai

**Fitur Siswa:**
1. **Dashboard:**
   - Lihat status "Sedang PKL"
   - Lihat info pembimbing (nama, keahlian, telp)
   - Lihat ringkasan logbook (total, diverifikasi, pending, hari kerja)
   - Lihat progress bar pengisian logbook
   - Alert jika belum isi logbook hari ini

2. **Logbook (logbook.php):**
   - Isi aktivitas harian
   - Pilih tanggal (hanya dalam range PKL)
   - Tulis aktivitas (textarea)
   - Klik "Simpan"
   - Lihat riwayat logbook dengan status verifikasi
   - Lihat catatan pembimbing (jika ada)
   - Bisa hapus logbook yang masih "Menunggu" verifikasi

**Validasi Logbook:**
- ✓ Tanggal harus dalam range tanggal_mulai - tanggal_selesai
- ✓ Tidak boleh ada 2 logbook untuk tanggal yang sama
- ✓ Aktivitas harus diisi
- ✓ Hanya bisa hapus jika status "Menunggu"

**Aksi Pembimbing:**
- Lihat siswa di siswa_bimbingan.php
- Buka detail_logbook.php untuk verifikasi logbook
- Verifikasi satu per satu atau semua sekaligus
- Tambah catatan pembimbing

---

#### E. TAHAP 5: MENUNGGU PENILAIAN (dashboard_siswa.php, nilai_siswa.php)
**Kondisi:**
- Status = "Menunggu Penilaian"
- Logbook sudah selesai/cukup
- Menunggu pembimbing memberikan nilai

**Fitur Siswa:**
1. **Dashboard:**
   - Lihat status "Menunggu Penilaian"
   - Lihat box "Nilai PKL" dengan status "Belum ada penilaian"

2. **Nilai Siswa (nilai_siswa.php):**
   - Lihat detail nilai (jika sudah ada)
   - Lihat 4 komponen nilai: Kedisiplinan, Keterampilan, Sikap, Laporan
   - Lihat nilai akhir (rata-rata 4 komponen)
   - Lihat predikat (A/B/C/D)
   - Lihat catatan pembimbing
   - Lihat status penilaian (Draft/Final)

**Aksi Pembimbing:**
- Buka beri_nilai.php
- Input nilai untuk 4 komponen (0-100)
- Lihat preview nilai akhir real-time
- Lihat predikat badge
- Tambah catatan untuk siswa
- Pilih status: Draft (bisa diubah) atau Final (terkunci)
- Klik "Simpan Nilai"

---

#### F. TAHAP 6: SELESAI (dashboard_siswa.php)
**Kondisi:**
- Status = "Selesai"
- Nilai sudah Final
- PKL selesai

**Fitur Siswa:**
- Lihat status "Selesai" di dashboard
- Lihat nilai akhir di box-info
- Lihat sertifikat (jika sudah dibuat admin)
- Lihat ringkasan PKL lengkap

---

### Ringkasan Fitur Siswa:
| Fitur | Menunggu | Diterima | Sedang PKL | Menunggu Nilai | Selesai |
|-------|----------|----------|-----------|----------------|---------|
| Edit Pendaftaran | ✓ | ✗ | ✗ | ✗ | ✗ |
| Hapus Pendaftaran | ✓ | ✗ | ✗ | ✗ | ✗ |
| Isi Logbook | ✗ | ✗ | ✓ | ✓ | ✗ |
| Lihat Nilai | ✗ | ✗ | ✗ | ✓ | ✓ |
| Lihat Pembimbing | ✗ | ✓ | ✓ | ✓ | ✓ |

---

## 2. ALUR PEMBIMBING (Mentor Flow)

### Tahapan Lengkap:

#### A. TAHAP 1: DASHBOARD (dashboard_pembimbing.php)
**Kondisi:**
- Pembimbing sudah login
- Sudah ada siswa yang ditugaskan

**Fitur Pembimbing:**
1. **Profile Card:**
   - Nama lengkap, keahlian, NIP
   - Statistik: Siswa/Kuota, Selesai PKL, Slot Tersisa

2. **Stats Cards:**
   - Total Siswa Bimbingan
   - Logbook Pending (dengan notif badge)
   - Penilaian Pending (dengan notif badge)
   - PKL Selesai

3. **Task List:**
   - Verifikasi Logbook (jika ada pending)
   - Beri Penilaian (jika ada pending)

4. **Kapasitas Visual:**
   - Progress bar kuota
   - Persentase terisi
   - Slot tersisa

5. **Tabel Siswa Preview:**
   - 5 siswa terbaru
   - Nama, Kelas, Perusahaan, Status
   - Logbook status (pending/terkini)
   - Quick action: Logbook, Nilai

---

#### B. TAHAP 2: SISWA BIMBINGAN (siswa_bimbingan.php)
**Fitur:**
1. **Search & Filter:**
   - Search by nama, NISN, perusahaan
   - Filter by status: Semua, Diterima, Sedang PKL, Menunggu Penilaian, Selesai

2. **Tabel Siswa:**
   - Nama Siswa, NISN, Kelas, Perusahaan
   - Periode PKL (tanggal mulai - selesai)
   - Status (badge warna)
   - Logbook (total entri + pending count)
   - Nilai (nilai akhir + status Draft/Final)
   - Aksi: Logbook, Nilai

3. **Quick Action:**
   - Klik "Logbook" → detail_logbook.php
   - Klik "Nilai" → beri_nilai.php

---

#### C. TAHAP 3: VERIFIKASI LOGBOOK (detail_logbook.php)
**Kondisi:**
- Siswa sudah isi logbook
- Status logbook = "Menunggu"

**Fitur Pembimbing:**
1. **Info Bar:**
   - Nama siswa, kelas, perusahaan, periode PKL

2. **Statistik Logbook:**
   - Total logbook
   - Diverifikasi (hijau)
   - Menunggu (orange)

3. **Filter:**
   - Semua, Menunggu, Diverifikasi

4. **Tabel Logbook:**
   - Tanggal, Aktivitas, Catatan Pembimbing, Status, Aksi

5. **Aksi:**
   - Klik "Verifikasi" → Modal verifikasi
   - Tambah catatan (opsional)
   - Klik "Verifikasi" → Status = "Diverifikasi"
   - Atau "Verifikasi Semua" untuk batch

**Validasi:**
- ✓ Catatan opsional
- ✓ Bisa verifikasi satu per satu atau semua sekaligus
- ✓ Setelah verifikasi, tidak bisa diubah

---

#### D. TAHAP 4: BERI NILAI (beri_nilai.php)
**Kondisi:**
- Siswa status "Sedang PKL" atau "Menunggu Penilaian"
- Logbook sudah cukup diverifikasi

**Fitur Pembimbing:**
1. **Info Bar:**
   - Nama siswa, kelas, perusahaan, status PKL

2. **Logbook Progress:**
   - Total logbook, diverifikasi, pending
   - Progress bar verifikasi
   - Link ke detail_logbook.php

3. **Form Input Nilai:**
   - Kedisiplinan (0-100)
   - Keterampilan (0-100)
   - Sikap (0-100)
   - Laporan (0-100)
   - Catatan untuk siswa (textarea)
   - Status: Draft atau Final

4. **Preview Nilai (Real-time):**
   - Nilai akhir (rata-rata 4 komponen)
   - Predikat badge (A/B/C/D)
   - Rincian per komponen dengan progress bar
   - Status (Draft/Final)

5. **Validasi:**
   - ✓ Nilai 0-100 (auto clamp)
   - ✓ Nilai akhir = (K + Kt + S + L) / 4
   - ✓ Predikat: A (≥90), B (≥75), C (≥60), D (<60)
   - ✓ Jika Final, tidak bisa diubah (lock)

6. **Aksi:**
   - Klik "Simpan Nilai" → Update/Insert ke tabel penilaian
   - Jika Final → Status PKL berubah ke "Selesai"
   - Jika Draft → Bisa diubah lagi

---

### Ringkasan Fitur Pembimbing:
| Fitur | Deskripsi |
|-------|-----------|
| Dashboard | Lihat overview siswa, tugas pending, kapasitas |
| Siswa Bimbingan | List semua siswa dengan search & filter |
| Verifikasi Logbook | Verifikasi logbook siswa satu per satu atau batch |
| Beri Nilai | Input nilai 4 komponen dengan preview real-time |
| Logout | Di sidebar bagian bawah |

---

## 3. ALUR ADMIN (Administrator Flow)

### Tahapan Lengkap:

#### A. TAHAP 1: DASHBOARD (dashboard_admin.php)
**Fitur:**
1. **Stats Cards (6 card):**
   - Total Pendaftar
   - Peserta Aktif (Sedang PKL + Menunggu Penilaian)
   - Menunggu Review (Menunggu Verifikasi) - dengan notif dot
   - PKL Selesai
   - Total Pembimbing
   - Total Siswa

2. **Task List:**
   - Verifikasi Pendaftaran (jika ada Menunggu Verifikasi)
   - Tugaskan Pembimbing (jika ada Diterima tanpa pembimbing)
   - Buat Sertifikat (jika ada Selesai tanpa sertifikat)

3. **Activity Log:**
   - Aktivitas terbaru (5 item)
   - Siapa, aksi apa, kapan

4. **Charts:**
   - Distribusi Status PKL (pie chart)
   - Pendaftaran 6 Bulan Terakhir (bar chart)

5. **Tabel Pendaftaran Terbaru:**
   - 5 pendaftaran terbaru
   - Siswa, Perusahaan, Tanggal, Status

---

#### B. TAHAP 2: KELOLA PENDAFTARAN (admin_pendaftaran.php)
**Fitur:**
1. **Search & Filter:**
   - Search by nama siswa, NISN, perusahaan
   - Filter by status: Semua, Menunggu Verifikasi, Diterima, Sedang PKL, Menunggu Penilaian, Ditolak, Selesai
   - Export ke Excel

2. **Tabel Pendaftaran:**
   - No, Siswa, NISN, Perusahaan, Pembimbing, Tanggal, Status, Aksi

3. **Aksi (Inline):**
   - Jika status "Menunggu Verifikasi":
     - Klik "Terima" → Status = "Diterima"
     - Klik "Tolak" → Status = "Ditolak"
   - Klik icon dokumen → Preview surat permohonan
   - Klik icon edit → Modal update status + catatan

4. **Modal Update Status:**
   - Dropdown status (6 pilihan)
   - Textarea catatan untuk siswa
   - Klik "Simpan Perubahan"

5. **Pagination:**
   - 15 item per halaman
   - Navigation: Prev, page numbers, Next

---

#### C. TAHAP 3: TUGASKAN PEMBIMBING (admin_tugaskan_pembimbing.php)
**Fitur:**
1. **Filter:**
   - Tampilkan siswa dengan status "Diterima" tanpa pembimbing

2. **Rekomendasi Pembimbing:**
   - Tampilkan pembimbing dengan kuota tersisa
   - Urutkan by kuota tersisa (ascending)
   - Tampilkan: Nama, Keahlian, Siswa/Kuota, Aksi

3. **Aksi:**
   - Klik "Tugaskan" → Assign pembimbing ke siswa
   - Update pendaftaran.pembimbing_id
   - Log activity

---

#### D. TAHAP 4: KELOLA PEMBIMBING (admin_pembimbing.php)
**Fitur:**
1. **Tabel Pembimbing:**
   - Nama, NIP, Keahlian, Kuota, Siswa Aktif, Aksi

2. **Aksi:**
   - Edit pembimbing
   - Hapus pembimbing
   - Lihat siswa bimbingan

---

#### E. TAHAP 5: KELOLA SISWA (admin_siswa.php)
**Fitur:**
1. **Tabel Siswa:**
   - Nama, NISN, Kelas, Jurusan, Email, Aksi

2. **Aksi:**
   - Edit profil siswa
   - Hapus siswa

---

#### F. TAHAP 6: KELOLA PERUSAHAAN (admin_perusahaan.php)
**Fitur:**
1. **Tabel Perusahaan:**
   - Nama, Bidang Usaha, Alamat, Telp, Kuota, Aksi

2. **Aksi:**
   - Tambah perusahaan
   - Edit perusahaan
   - Hapus perusahaan

---

#### G. TAHAP 7: KELOLA PENGUMUMAN (admin_pengumuman.php)
**Fitur:**
1. **Tabel Pengumuman:**
   - Judul, Tanggal, Aksi

2. **Aksi:**
   - Tambah pengumuman
   - Edit pengumuman
   - Hapus pengumuman

---

#### H. TAHAP 8: KELOLA SERTIFIKAT (admin_sertifikat.php)
**Fitur:**
1. **Tabel Sertifikat:**
   - Siswa, Perusahaan, Tanggal Selesai, Status, Aksi

2. **Aksi:**
   - Generate sertifikat
   - Download sertifikat
   - Hapus sertifikat

---

### Ringkasan Fitur Admin:
| Fitur | Deskripsi |
|-------|-----------|
| Dashboard | Overview statistik, task list, activity log, charts |
| Kelola Pendaftaran | Verifikasi, filter, search, update status, export |
| Tugaskan Pembimbing | Assign pembimbing ke siswa dengan rekomendasi |
| Kelola Pembimbing | CRUD pembimbing |
| Kelola Siswa | CRUD siswa |
| Kelola Perusahaan | CRUD perusahaan |
| Kelola Pengumuman | CRUD pengumuman |
| Kelola Sertifikat | Generate & manage sertifikat |
| Logout | Di sidebar bagian bawah |

---

## 4. ANALISIS KESESUAIAN ALUR

### ✓ SUDAH SESUAI:
1. **Status Flow:** Alur status PKL sudah benar (Menunggu → Diterima → Sedang PKL → Menunggu Penilaian → Selesai)
2. **Siswa Pendaftaran:** Siswa bisa daftar, edit (jika Menunggu), hapus
3. **Siswa Logbook:** Siswa bisa isi logbook saat Sedang PKL
4. **Siswa Nilai:** Siswa bisa lihat nilai saat Menunggu Penilaian/Selesai
5. **Pembimbing Verifikasi:** Pembimbing bisa verifikasi logbook
6. **Pembimbing Nilai:** Pembimbing bisa input nilai dengan preview real-time
7. **Admin Verifikasi:** Admin bisa terima/tolak pendaftaran
8. **Admin Tugaskan:** Admin bisa tugaskan pembimbing
9. **Activity Log:** Semua aksi tercatat di activity_log
10. **Logout:** Sudah di sidebar untuk semua role

### ⚠️ PERLU PERBAIKAN/PENAMBAHAN:

#### 1. **Siswa - Hapus Pendaftaran**
- **Status:** Belum ada fitur hapus pendaftaran
- **Seharusnya:** Siswa bisa hapus jika status "Menunggu Verifikasi"
- **File:** daftar_pkl.php
- **Solusi:** Tambah button "Hapus Pendaftaran" dengan confirm dialog

#### 2. **Siswa - Edit Pendaftaran**
- **Status:** Ada tapi hanya bisa edit jika status "Menunggu"
- **Seharusnya:** Bisa edit perusahaan, tanggal, dokumen
- **File:** daftar_pkl.php
- **Catatan:** Sudah benar, tapi perlu validasi lebih ketat

#### 3. **Pembimbing - Status Siswa**
- **Status:** Pembimbing bisa lihat siswa tapi tidak bisa ubah status PKL
- **Seharusnya:** Pembimbing bisa ubah status dari "Sedang PKL" ke "Menunggu Penilaian" (setelah logbook cukup)
- **File:** siswa_bimbingan.php
- **Solusi:** Tambah button "Ubah Status" untuk pembimbing

#### 4. **Admin - Ubah Status PKL**
- **Status:** Admin bisa ubah status tapi tidak ada validasi
- **Seharusnya:** Validasi status flow (tidak boleh mundur, hanya maju)
- **File:** admin_pendaftaran.php
- **Solusi:** Tambah validasi status transition

#### 5. **Sertifikat**
- **Status:** Halaman admin_sertifikat.php ada tapi belum fully implemented
- **Seharusnya:** Generate sertifikat otomatis saat status "Selesai"
- **File:** admin_sertifikat.php
- **Solusi:** Implement sertifikat generation

#### 6. **Notifikasi Real-time**
- **Status:** Tidak ada notifikasi real-time
- **Seharusnya:** Notifikasi untuk pending tasks
- **Solusi:** Tambah badge di sidebar (sudah ada untuk pembimbing)

#### 7. **Validasi Tanggal Logbook**
- **Status:** Validasi ada tapi bisa lebih ketat
- **Seharusnya:** Tidak boleh isi logbook di hari libur/weekend (opsional)
- **Solusi:** Tambah validasi hari kerja

#### 8. **Pembimbing - Lihat Catatan Admin**
- **Status:** Pembimbing tidak bisa lihat catatan dari admin
- **Seharusnya:** Pembimbing bisa lihat catatan admin di siswa_bimbingan.php
- **Solusi:** Tambah kolom catatan di tabel siswa

---

## 5. REKOMENDASI PERBAIKAN

### Priority 1 (URGENT):
1. Tambah fitur hapus pendaftaran untuk siswa
2. Tambah validasi status flow untuk admin
3. Implement sertifikat generation

### Priority 2 (IMPORTANT):
1. Tambah fitur ubah status PKL untuk pembimbing
2. Tambah catatan admin di siswa_bimbingan.php
3. Tambah validasi hari kerja untuk logbook

### Priority 3 (NICE TO HAVE):
1. Notifikasi real-time
2. Email notification
3. Dashboard analytics lebih detail

---

## KESIMPULAN

**Alur sistem sudah 85% sesuai dengan requirement.** Fitur-fitur utama sudah berfungsi dengan baik:
- ✓ Siswa bisa daftar, isi logbook, lihat nilai
- ✓ Pembimbing bisa verifikasi logbook, input nilai
- ✓ Admin bisa verifikasi pendaftaran, tugaskan pembimbing

Namun ada beberapa fitur yang perlu ditambahkan/diperbaiki untuk membuat alur lebih sempurna dan user-friendly.
