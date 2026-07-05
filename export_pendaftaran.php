<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { 
    header("Location: index.html"); 
    exit(); 
}

include('db.php');

// Filters
$filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
if ($filter) $where[] = "p.status = '" . $conn->real_escape_string($filter) . "'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where[] = "(s.nama_lengkap LIKE '%$s%' OR s.nisn LIKE '%$s%' OR pr.nama_perusahaan LIKE '%$s%')";
}
$whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT 
    s.nama_lengkap as 'Nama Siswa',
    s.nisn as 'NISN',
    s.kelas as 'Kelas',
    s.jurusan as 'Jurusan',
    pr.nama_perusahaan as 'Perusahaan',
    pr.bidang_usaha as 'Bidang Usaha',
    pb.nama_lengkap as 'Pembimbing',
    p.tanggal_mulai as 'Tanggal Mulai',
    p.tanggal_selesai as 'Tanggal Selesai',
    p.status as 'Status',
    p.created_at as 'Tanggal Daftar'
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    $whereSQL
    ORDER BY p.created_at DESC";

$result = $conn->query($query);

// Generate filename
$filename = 'Pendaftaran_PKL_' . date('Y-m-d_His') . '.csv';

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output CSV
$output = fopen('php://output', 'w');

// BOM for Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($result->num_rows > 0) {
    // Header
    $firstRow = $result->fetch_assoc();
    fputcsv($output, array_keys($firstRow));
    
    // Data
    fputcsv($output, $firstRow);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>
