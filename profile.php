<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
include('db.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM profile WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nama_lengkap = $row['nama_lengkap'];
        $jenis_kelamin = $row['jenis_kelamin'];
        $nomor_telepon = $row['nomor_telepon'];
        $alamat = $row['alamat'];
        $nama_sekolah = $row['nama_sekolah'];
        $program_studi = $row['program_studi'];
    } else {
        echo "Data tidak ditemukan!";
    }
    $stmt->close();
} else {
    $nama_lengkap = $jenis_kelamin = $nomor_telepon = $alamat = $nama_sekolah = $program_studi = "";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<!-- My CSS -->
	<link rel="stylesheet" href="style.css">
    <title>Form Profile Siswa</title>
    <style>
        /* Umum styling untuk body */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* Styling untuk container form */
form {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    width: 100%;
    max-width: 600px;
    box-sizing: border-box;
}

/* Styling untuk judul */
h2 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

/* Styling untuk label */
label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #555;
}

/* Styling untuk input, select, dan textarea */
input[type="text"],
textarea,
select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.3s;
}

/* Hover dan focus effect untuk input */
input[type="text"]:focus,
select:focus,
textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
}

/* Styling untuk button */
button {
    background-color: #007bff;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

/* Hover effect untuk button */
button:hover {
    background-color: #0056b3;
}

/* Responsive design */
@media (max-width: 600px) {
    form {
        padding: 15px;
    }

    button {
        width: 100%;
    }
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
    <h2></h2>
    <form action="simpan_profil.php" method="post">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">
        <label for="nama_lengkap">Nama Lengkap:</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo $nama_lengkap; ?>" required><br>
        
        <label for="jenis_kelamin">Jenis Kelamin:</label>
        <select id="jenis_kelamin" name="jenis_kelamin" required>
            <option value="Laki-laki" <?php echo ($jenis_kelamin == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
            <option value="Perempuan" <?php echo ($jenis_kelamin == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
        </select><br>

        <label for="nomor_telepon">Nomor Telepon:</label>
        <input type="text" id="nomor_telepon" name="nomor_telepon" value="<?php echo $nomor_telepon; ?>" required><br>

        <label for="alamat">Alamat:</label>
        <textarea id="alamat" name="alamat" required><?php echo $alamat; ?></textarea><br>

        <label for="nama_sekolah">Nama Sekolah:</label>
        <input type="text" id="nama_sekolah" name="nama_sekolah" value="<?php echo $nama_sekolah; ?>" required><br>

        <label for="program_studi">Program Studi:</label>
        <input type="text" id="program_studi" name="program_studi" value="<?php echo $program_studi; ?>" required><br>

        <button type="submit">Simpan</button>
    </form>
</body>
</html>
