# AKSES LOGBOOK SISWA - DOKUMENTASI

## Kapan Siswa Bisa Akses Logbook?

### **Status PKL yang Memungkinkan Akses:**

| Status | Bisa Akses? | Bisa Isi? | Keterangan |
|--------|-------------|-----------|-----------|
| Menunggu Verifikasi | ❌ | ❌ | Belum diterima |
| Diterima | ✅ | ❌ | Bisa lihat, tapi tidak bisa isi |
| **Sedang PKL** | ✅ | ✅ | **BISA ISI LOGBOOK** |
| Menunggu Penilaian | ✅ | ❌ | Bisa lihat, tapi tidak bisa isi (PKL sudah selesai) |
| Selesai | ✅ | ❌ | Bisa lihat, tapi tidak bisa isi |
| Ditolak | ❌ | ❌ | Tidak bisa akses |

---

## Alur Akses Logbook:

```
1. Siswa Daftar PKL
   ↓ Status: Menunggu Verifikasi
   ❌ Tidak bisa akses logbook

2. Admin Verifikasi → Diterima
   ↓ Status: Diterima
   ✅ Bisa akses logbook (lihat saja)
   ❌ Tidak bisa isi logbook

3. Pembimbing Ubah Status → Sedang PKL
   ↓ Status: Sedang PKL
   ✅ BISA AKSES LOGBOOK
   ✅ BISA ISI LOGBOOK ← INILAH WAKTU YANG TEPAT

4. Pembimbing Ubah Status → Menunggu Penilaian
   ↓ Status: Menunggu Penilaian
   ✅ Bisa akses logbook (lihat saja)
   ❌ Tidak bisa isi logbook (PKL sudah selesai)

5. Pembimbing Ubah Status → Selesai
   ↓ Status: Selesai
   ✅ Bisa akses logbook (lihat saja)
   ❌ Tidak bisa isi logbook
```

---

## Validasi Logbook:

### **Akses Halaman:**
```php
// Siswa bisa akses logbook.php jika status:
- Diterima
- Sedang PKL
- Menunggu Penilaian
- Selesai

// Jika status lain (Menunggu Verifikasi, Ditolak):
// → Redirect ke dashboard_siswa.php
```

### **Isi Logbook:**
```php
// Siswa HANYA bisa isi logbook jika status = "Sedang PKL"
// Jika status lain:
// → Form input disabled
// → Alert: "Logbook hanya bisa diisi saat status Sedang PKL"
```

### **Validasi Tanggal:**
```php
// Tanggal logbook harus dalam range:
- Min: tanggal_mulai (dari pendaftaran)
- Max: tanggal_selesai (dari pendaftaran)

// Tidak boleh ada 2 logbook untuk tanggal yang sama
```

---

## Fitur Logbook Siswa:

### **Saat Status = Sedang PKL:**
✅ Bisa isi logbook baru
✅ Bisa lihat riwayat logbook
✅ Bisa hapus logbook yang masih "Menunggu" verifikasi
✅ Bisa lihat catatan pembimbing

### **Saat Status ≠ Sedang PKL:**
❌ Tidak bisa isi logbook baru
✅ Bisa lihat riwayat logbook
❌ Tidak bisa hapus logbook
✅ Bisa lihat catatan pembimbing

---

## Contoh Skenario:

### **Skenario 1: Siswa Baru**
```
1. Siswa daftar PKL → Status: Menunggu Verifikasi
   - Akses logbook.php → Redirect ke dashboard
   - Pesan: "Belum ada pendaftaran PKL"

2. Admin verifikasi → Status: Diterima
   - Akses logbook.php → Bisa lihat halaman
   - Form input: DISABLED
   - Pesan: "Logbook hanya bisa diisi saat status Sedang PKL"

3. Pembimbing ubah status → Status: Sedang PKL
   - Akses logbook.php → Bisa lihat halaman
   - Form input: ENABLED ✅
   - Siswa bisa isi logbook harian

4. Pembimbing ubah status → Status: Menunggu Penilaian
   - Akses logbook.php → Bisa lihat halaman
   - Form input: DISABLED
   - Pesan: "Logbook hanya bisa diisi saat status Sedang PKL"
```

### **Skenario 2: Siswa Ditolak**
```
1. Admin tolak pendaftaran → Status: Ditolak
   - Akses logbook.php → Redirect ke dashboard
   - Pesan: "Belum ada pendaftaran PKL"
   - Siswa bisa daftar ulang
```

---

## Perbaikan yang Dilakukan:

### **File: logbook.php**

**Sebelum:**
```php
// Hanya bisa akses jika status = "Diterima"
$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ? AND status = 'Diterima'");
```

**Sesudah:**
```php
// Bisa akses jika status = Diterima, Sedang PKL, atau Menunggu Penilaian
$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ? AND status IN ('Diterima','Sedang PKL','Menunggu Penilaian')");

// Tapi hanya bisa ISI jika status = "Sedang PKL"
if ($pkl['status'] !== 'Sedang PKL') {
    // Form input disabled
    // Alert: "Logbook hanya bisa diisi saat status Sedang PKL"
}
```

---

## Kesimpulan:

**Siswa bisa isi logbook HANYA KETIKA:**
1. ✅ Status PKL = **"Sedang PKL"**
2. ✅ Tanggal dalam range tanggal_mulai - tanggal_selesai
3. ✅ Belum ada logbook untuk tanggal yang sama

**Siswa bisa LIHAT logbook ketika:**
- Status = Diterima, Sedang PKL, Menunggu Penilaian, atau Selesai

---

**Status:** ✅ PERBAIKAN SELESAI
