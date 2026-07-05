<?php
include('db.php');

$perusahaan = [
    ['nama' => 'PT Telkom Indonesia', 'bidang' => 'Telekomunikasi', 'alamat' => 'Jl. Jenderal Sudirman No. 1, Jakarta', 'telp' => '021-1500123'],
    ['nama' => 'PT Bank Mandiri', 'bidang' => 'Perbankan', 'alamat' => 'Jl. Gatot Subroto No. 36-38, Jakarta', 'telp' => '021-5299999'],
    ['nama' => 'PT Pertamina', 'bidang' => 'Energi & Minyak', 'alamat' => 'Jl. Medan Merdeka Timur No. 1A, Jakarta', 'telp' => '021-3815000'],
    ['nama' => 'PT Garuda Indonesia', 'bidang' => 'Penerbangan', 'alamat' => 'Jl. Medan Merdeka Selatan No. 13, Jakarta', 'telp' => '021-2310808'],
    ['nama' => 'PT Indofood Sukses Makmur', 'bidang' => 'Makanan & Minuman', 'alamat' => 'Jl. Panjang No. 5, Jakarta', 'telp' => '021-5363888'],
    ['nama' => 'PT Unilever Indonesia', 'bidang' => 'Konsumen & Retail', 'alamat' => 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta', 'telp' => '021-5799999'],
    ['nama' => 'PT Astra International', 'bidang' => 'Otomotif', 'alamat' => 'Jl. Gaya Motor Raya No. 8, Jakarta', 'telp' => '021-5289999'],
    ['nama' => 'PT Semen Indonesia', 'bidang' => 'Konstruksi & Material', 'alamat' => 'Jl. Veteran No. 1, Gresik', 'telp' => '031-3981111'],
    ['nama' => 'PT PLN (Persero)', 'bidang' => 'Listrik & Energi', 'alamat' => 'Jl. Trunojoyo No. 1, Jakarta', 'telp' => '021-5980000'],
    ['nama' => 'PT Bank BCA', 'bidang' => 'Perbankan', 'alamat' => 'Jl. MH. Thamrin No. 1, Jakarta', 'telp' => '021-2358888'],
    ['nama' => 'PT Jasa Marga', 'bidang' => 'Infrastruktur & Jalan Tol', 'alamat' => 'Jl. Gatot Subroto Kav. 52, Jakarta', 'telp' => '021-5200888'],
    ['nama' => 'PT Mitra Adiperkasa', 'bidang' => 'Retail & Fashion', 'alamat' => 'Jl. Benda No. 1, Jakarta', 'telp' => '021-7208888'],
    ['nama' => 'PT Medco Energi Internasional', 'bidang' => 'Energi & Minyak', 'alamat' => 'Jl. Jenderal Sudirman Kav. 62, Jakarta', 'telp' => '021-5299888'],
    ['nama' => 'PT Multipolar Technology', 'bidang' => 'Teknologi & IT', 'alamat' => 'Jl. Benda Raya No. 1, Jakarta', 'telp' => '021-7208000'],
    ['nama' => 'PT Summarecon Agung', 'bidang' => 'Real Estate & Properti', 'alamat' => 'Jl. Benda No. 1, Jakarta', 'telp' => '021-7208888'],
];

echo "<h2>Menambahkan Data Perusahaan</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>No</th><th>Nama Perusahaan</th><th>Status</th></tr>";

$count = 0;
foreach ($perusahaan as $p) {
    $nama = $conn->real_escape_string($p['nama']);
    $bidang = $conn->real_escape_string($p['bidang']);
    $alamat = $conn->real_escape_string($p['alamat']);
    $telp = $conn->real_escape_string($p['telp']);
    
    // Cek apakah sudah ada
    $check = $conn->query("SELECT id FROM perusahaan WHERE nama_perusahaan = '$nama'");
    
    if ($check && $check->num_rows > 0) {
        echo "<tr><td>" . ($count + 1) . "</td><td>$nama</td><td style='color:orange;'>Sudah ada</td></tr>";
    } else {
        $sql = "INSERT INTO perusahaan (nama_perusahaan, bidang_usaha, alamat, no_telp) VALUES ('$nama', '$bidang', '$alamat', '$telp')";
        if ($conn->query($sql)) {
            echo "<tr><td>" . ($count + 1) . "</td><td>$nama</td><td style='color:green;'>✓ Ditambahkan</td></tr>";
            $count++;
        } else {
            echo "<tr><td>" . ($count + 1) . "</td><td>$nama</td><td style='color:red;'>✗ Error: " . $conn->error . "</td></tr>";
        }
    }
}

echo "</table>";
echo "<p><br><strong>Total perusahaan baru ditambahkan: $count</strong></p>";
echo "<p><a href='daftar_pkl.php'>Kembali ke Daftar PKL</a></p>";

$conn->close();
?>
