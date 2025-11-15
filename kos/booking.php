<?php
// kos/booking.php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php'); exit;
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pencari') {
    // bila belum login/pencari -> arahkan ke login
    header('Location: ../login.php'); exit;
}

$user_id = (int)$_SESSION['user_id'];
$kos_id = (int)($_POST['kos_id'] ?? 0);
$kamar_id = (int)($_POST['kamar_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

// validasi
if ($kos_id <= 0 || $kamar_id <= 0) {
    $_SESSION['flash_error'] = "Data pengajuan tidak valid.";
    header("Location: ../kos/detail.php?id={$kos_id}"); exit;
}

// pastikan kamar dan kos ada dan tersedia
$stmt = $pdo->prepare("SELECT k.id as kosid, km.id as kid, km.status FROM kos k JOIN kamar km ON km.kos_id = k.id WHERE k.id = ? AND km.id = ? LIMIT 1");
$stmt->execute([$kos_id, $kamar_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    $_SESSION['flash_error'] = "Kamar tidak ditemukan.";
    header("Location: ../kos/detail.php?id={$kos_id}"); exit;
}
if ($row['status'] !== 'kosong') {
    $_SESSION['flash_error'] = "Maaf, kamar sudah tidak tersedia.";
    header("Location: ../kos/detail.php?id={$kos_id}"); exit;
}

// simpan ke tabel bookings
$ins = $pdo->prepare("INSERT INTO bookings (kos_id, kamar_id, user_id, message, status) VALUES (?, ?, ?, ?, 'pending')");
try {
    $ins->execute([$kos_id, $kamar_id, $user_id, $message]);
    $_SESSION['flash_success'] = "Pengajuan berhasil dikirim. Pemilik akan menghubungi Anda.";
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Gagal mengajukan sewa: " . $e->getMessage();
}

header("Location: ../kos/detail.php?id={$kos_id}");
exit;
