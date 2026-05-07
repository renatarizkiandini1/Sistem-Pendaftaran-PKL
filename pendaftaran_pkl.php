<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pendaftaran_id = 'PKL-' . strtoupper(uniqid());
    $nama_sekolah = $_POST['nama_sekolah'];
    $program_studi = $_POST['program_studi'];
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $status = 'Menunggu';

    $stmt = $conn->prepare("INSERT INTO pendaftaran_pkl (pendaftaran_id, nama_sekolah, program_studi, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $pendaftaran_id, $nama_sekolah, $program_studi, $tanggal_mulai, $tanggal_selesai, $status);

    if ($stmt->execute()) {
        header("Location: tampilkan_data.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran PKL/Prakerin</title>
	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<!-- My CSS -->
	<link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 500px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
    
</head>
<body>
    <!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bxs-smile'></i>
			<span class="text">user</span>
		</a>
		<ul class="side-menu top">
	
			<li>
				<a href="pendaftaran_pkl.php"> 
					<i class='bx bxs-shopping-bag-alt' ></i>
					<span class="text">Pendaftaran PKL</span>
				</a>
			</li>
	
		</ul>
		<ul class="side-menu">
			<li>
				<a href="profile.php">
					<i class='bx bxs-cog' ></i>
					<span class="text">Profile</span>
				</a>
			</li>
			<li>
				<a href="logout.php" class="logout">
					<i class='bx bxs-log-out-circle' ></i>
					<span class="text">Logout</span>
				</a>
			</li>
		</ul>
	</section>
	<!-- SIDEBAR -->
		
    <div class="container">
        <h1>Form Pendaftaran PKL/Prakerin Siswa</h1>
        <form action="pendaftaran_pkl.php" method="POST">

            <label for="nama_sekolah">Nama Sekolah:</label>
            <input type="text" id="nama_sekolah" name="nama_sekolah" required><br>

            <label for="program_studi">Program Studi:</label>
            <input type="text" id="program_studi" name="program_studi" required><br>

            <label for="tanggal_mulai">Tanggal Mulai:</label>
            <input type="date" id="tanggal_mulai" name="tanggal_mulai" required><br>

            <label for="tanggal_selesai">Tanggal Selesai:</label>
            <input type="date" id="tanggal_selesai" name="tanggal_selesai" required><br>

            <button type="submit">Simpan Pendaftaran</button>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>
