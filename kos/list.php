<?php
include('db.php'); // Menyertakan koneksi database

// Query untuk mengambil data kos dari database
$sql = "SELECT * FROM kos";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Kos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php
        // Mengecek apakah ada data kos
        if ($result->num_rows > 0) {
            // Loop untuk menampilkan setiap data kos
            while ($row = $result->fetch_assoc()) {
                // Mengambil path gambar dari database
                $photo_path = isset($row['name']) ? "assets/uploads/kos_" . $row['id'] . "/" . $row['name'] . " 1.jpg" : 'assets/uploads/default.jpg'; // Default jika foto tidak ada
                ?>
                <div class="card">
                    <!-- Menampilkan gambar -->
                    <img src="<?php echo $photo_path; ?>" alt="Foto Kos">
                    <div class="card-body">
                        <h3><?php echo $row['name']; ?></h3>
                        <p><strong>Alamat:</strong> <?php echo $row['address']; ?></p>
                        <p><strong>Harga:</strong> Rp. <?php echo number_format($row['price'], 0, ',', '.'); ?> / bulan</p>
                        <p><?php echo $row['description']; ?></p>
                        <!-- Tombol Preview, Edit, Hapus -->
                        <button>Preview</button>
                        <button>Edit</button>
                        <button>Hapus</button>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<p>Tidak ada data kos.</p>";
        }
        ?>
    </div>
</body>
</html>

<?php
// Menutup koneksi database
$conn->close();
?>
