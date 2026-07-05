<?php
// Activity Log Function
function logActivity($conn, $user_id, $action, $table_name = null, $record_id = null, $description = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

// Get Pending Tasks for Admin
function getAdminTasks($conn) {
    $tasks = [];
    
    // 1. Pendaftaran menunggu verifikasi
    $result = $conn->query("SELECT COUNT(*) as count FROM pendaftaran WHERE status = 'Menunggu Verifikasi'");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $tasks[] = [
                'title' => 'Verifikasi Pendaftaran',
                'count' => $count,
                'icon' => 'bxs-file-doc',
                'color' => 'orange',
                'link' => 'admin_pendaftaran.php?status=Menunggu+Verifikasi',
                'priority' => 'high',
                'deadline' => 'Segera'
            ];
        }
    }
    
    // 2. Siswa diterima belum dapat pembimbing
    $result = $conn->query("SELECT COUNT(*) as count FROM pendaftaran WHERE status IN ('Diterima', 'Sedang PKL') AND pembimbing_id IS NULL");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $tasks[] = [
                'title' => 'Tugaskan Pembimbing',
                'count' => $count,
                'icon' => 'bxs-user-check',
                'color' => 'blue',
                'link' => 'admin_tugaskan_pembimbing.php',
                'priority' => 'medium',
                'deadline' => '3 hari'
            ];
        }
    }
    
    // 3. PKL selesai belum upload sertifikat
    $result = $conn->query("SELECT COUNT(*) as count FROM pendaftaran p LEFT JOIN sertifikat s ON s.pendaftaran_id = p.id WHERE p.status = 'Selesai' AND s.id IS NULL");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $tasks[] = [
                'title' => 'Generate Sertifikat',
                'count' => $count,
                'icon' => 'bxs-award',
                'color' => 'green',
                'link' => 'admin_sertifikat.php',
                'priority' => 'low',
                'deadline' => '7 hari'
            ];
        }
    }
    
    // 4. Logbook menunggu persetujuan
    $checkLogbook = $conn->query("SHOW TABLES LIKE 'logbook'");
    if ($checkLogbook && $checkLogbook->num_rows > 0) {
        // status di DB adalah 'Menunggu' bukan 'Menunggu Persetujuan'
        $result = $conn->query("SELECT COUNT(*) as count FROM logbook WHERE status_verifikasi = 'Menunggu'");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            if ($count > 0) {
                $tasks[] = [
                    'title' => 'Review Logbook',
                    'count' => $count,
                    'icon' => 'bxs-book',
                    'color' => 'blue',
                    'link' => 'detail_logbook.php',
                    'priority' => 'medium',
                    'deadline' => '2 hari'
                ];
            }
        }
    }
    
    return $tasks;
}

// Get Statistics for Charts
function getStatisticsData($conn) {
    $stats = [];
    
    // Status distribution
    $result = $conn->query("SELECT status, COUNT(*) as count FROM pendaftaran GROUP BY status");
    $statusData = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $statusData[$row['status']] = $row['count'];
        }
    }
    $stats['status'] = $statusData;
    
    // Monthly registrations (last 6 months)
    $result = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                            FROM pendaftaran 
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            GROUP BY month 
                            ORDER BY month");
    $monthlyData = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $monthlyData[$row['month']] = $row['count'];
        }
    }
    $stats['monthly'] = $monthlyData;
    
    // Pembimbing load
    $result = $conn->query("SELECT pb.nama_lengkap, pb.kuota, 
                            COUNT(p.id) as siswa_aktif 
                            FROM pembimbing pb 
                            LEFT JOIN pendaftaran p ON p.pembimbing_id = pb.id AND p.status IN ('Sedang PKL', 'Menunggu Penilaian')
                            GROUP BY pb.id");
    $pembimbingLoad = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pembimbingLoad[] = [
                'nama' => $row['nama_lengkap'],
                'aktif' => $row['siswa_aktif'],
                'kuota' => $row['kuota']
            ];
        }
    }
    $stats['pembimbing'] = $pembimbingLoad;
    
    return $stats;
}

// Get Recent Activities
function getRecentActivities($conn, $limit = 10) {
    // Check if activity_log table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($checkTable->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $result = $conn->query("SELECT al.*, u.username 
                            FROM activity_log al 
                            JOIN user u ON al.user_id = u.id 
                            ORDER BY al.created_at DESC 
                            LIMIT $limit");
    
    if (!$result) {
        return []; // Return empty array on query error
    }
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    return $activities;
}

// Get Pembimbing Recommendations
function getPembimbingRecommendations($conn, $siswa_id) {
    // Get pembimbing sorted by workload
    $result = $conn->query("SELECT pb.id, pb.nama_lengkap, pb.keahlian, pb.spesialisasi, pb.kuota,
                            COUNT(p.id) as current_load,
                            (pb.kuota - COUNT(p.id)) as available_slots
                            FROM pembimbing pb
                            LEFT JOIN pendaftaran p ON p.pembimbing_id = pb.id AND p.status IN ('Sedang PKL', 'Menunggu Penilaian')
                            GROUP BY pb.id
                            HAVING available_slots > 0
                            ORDER BY current_load ASC, pb.nama_lengkap ASC");
    
    $recommendations = [];
    while ($row = $result->fetch_assoc()) {
        $workloadPercent = round(($row['current_load'] / $row['kuota']) * 100);
        $row['workload_percent'] = $workloadPercent;
        $row['priority'] = $workloadPercent < 50 ? 'high' : ($workloadPercent < 80 ? 'medium' : 'low');
        $recommendations[] = $row;
    }
    
    return $recommendations;
}

// Search Function
function searchData($conn, $table, $query, $fields = []) {
    if (empty($query) || empty($fields)) return null;
    
    $conditions = [];
    foreach ($fields as $field) {
        $conditions[] = "$field LIKE '%" . $conn->real_escape_string($query) . "%'";
    }
    
    $where = implode(' OR ', $conditions);
    return $conn->query("SELECT * FROM $table WHERE $where");
}

// Export to CSV
function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

// Pagination Helper
function paginate($conn, $query, $page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    // Get total
    $countQuery = preg_replace('/SELECT .* FROM/i', 'SELECT COUNT(*) as total FROM', $query);
    $result = $conn->query($countQuery);
    $total = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $total = $row ? $row['total'] : 0;
    }
    
    // Get data
    $data = $conn->query($query . " LIMIT $perPage OFFSET $offset");
    
    return [
        'data' => $data,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current_page' => $page
    ];
}

// Format Date Indonesia
function formatDateID($date) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = date('j', strtotime($date));
    $m = date('n', strtotime($date));
    $y = date('Y', strtotime($date));
    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

// Time Ago Function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' detik lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    
    return date('d M Y', $time);
}

// Get Dashboard Notifications
function getDashboardNotifications($conn) {
    $notifications = [];
    
    // New registrations today
    $result = $conn->query("SELECT COUNT(*) as count FROM pendaftaran WHERE DATE(created_at) = CURDATE()");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'bxs-user-plus',
                'message' => "$count pendaftaran baru hari ini",
                'time' => 'Hari ini'
            ];
        }
    }
    
    // Overload pembimbing
    $result = $conn->query("SELECT pb.nama_lengkap, COUNT(p.id) as load, pb.kuota 
                            FROM pembimbing pb 
                            LEFT JOIN pendaftaran p ON p.pembimbing_id = pb.id AND p.status IN ('Sedang PKL', 'Menunggu Penilaian')
                            GROUP BY pb.id 
                            HAVING load >= pb.kuota");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bxs-error',
                'message' => "Pembimbing {$row['nama_lengkap']} sudah mencapai kuota maksimal",
                'time' => ''
            ];
        }
    }
    
    return $notifications;
}

// Get Performance Metrics
function getPerformanceMetrics($conn) {
    $metrics = [];
    
    // Response time (avg days from registration to acceptance)
    $result = $conn->query("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days 
                            FROM pendaftaran 
                            WHERE status = 'Diterima' AND updated_at IS NOT NULL");
    
    if ($result) {
        $avgDays = $result->fetch_assoc()['avg_days'];
        $metrics['response_time'] = round($avgDays ?? 0, 1);
    } else {
        $metrics['response_time'] = 0;
    }
    
    // Success rate (accepted/total)
    $result = $conn->query("SELECT 
                            COUNT(CASE WHEN status = 'Diterima' OR status = 'Sedang PKL' OR status = 'Selesai' THEN 1 END) as accepted,
                            COUNT(*) as total 
                            FROM pendaftaran");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $metrics['success_rate'] = $row['total'] > 0 ? round(($row['accepted'] / $row['total']) * 100, 1) : 0;
    } else {
        $metrics['success_rate'] = 0;
    }
    
    return $metrics;
}

// Batch Action Functions
function batchUpdateStatus($conn, $ids, $status, $user_id) {
    if (empty($ids) || !in_array($status, ['Menunggu Verifikasi','Diterima','Ditolak','Sedang PKL','Menunggu Penilaian','Selesai'])) {
        return false;
    }
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $conn->prepare("UPDATE pendaftaran SET status = ? WHERE id IN ($placeholders)");
    
    $types = 's' . str_repeat('i', count($ids));
    $stmt->bind_param($types, $status, ...$ids);
    $result = $stmt->execute();
    
    if ($result) {
        logActivity($conn, $user_id, 'Batch Update Status', 'pendaftaran', null, "Updated " . count($ids) . " records to $status");
    }
    
    $stmt->close();
    return $result;
}
?>
