<?php
$host = 'localhost';
$db   = 'kos_app';  // pastikan nama database sesuai
$user = 'root';     // default XAMPP
$pass = '';         // tidak pakai password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // tampilkan error jika ada
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // hasil array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // keamanan prepared statement
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options); 
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
