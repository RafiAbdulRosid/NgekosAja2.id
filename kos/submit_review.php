<?php
// kos/submit_review.php
session_start();
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pencari') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kos_id = (int)$_POST['kos_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    // Validasi dasar
    if ($kos_id > 0 && $rating >= 1 && $rating <= 5) {
        
        // Cek apakah user sudah pernah me-review kos ini (opsional)
        $check = $pdo->prepare("SELECT id FROM review WHERE kos_id = ? AND user_id = ?");
        $check->execute([$kos_id, $user_id]);
        if ($check->rowCount() > 0) {
            // Jika sudah, mungkin kita batalkan atau update
            $message = "Anda sudah pernah memberikan ulasan untuk kos ini.";
            header("Location: detail.php?id=$kos_id&error=".urlencode($message));
            exit;
        }

        // Simpan Review
        $sql = "INSERT INTO review (kos_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$kos_id, $user_id, $rating, $comment])) {
            $message = "Ulasan berhasil dikirim!";
        } else {
            $message = "Gagal menyimpan ulasan.";
        }
    } else {
        $message = "Rating dan ID Kos tidak valid.";
    }
    
    $pdo = null;
    header("Location: detail.php?id=$kos_id&success=".urlencode($message));
    exit;
}
?>