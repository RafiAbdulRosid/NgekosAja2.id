<?php
// dashboard_bookings.php
session_start();
require_once __DIR__ . '/db.php';

// 1. Cek Login & Role Pemilik
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    header('Location: login.php');
    exit;
}

$owner_id = $_SESSION['user_id'];
$message = '';

// 2. PROSES KONFIRMASI (TERIMA / TOLAK)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action']; // 'approve' atau 'reject'

    // Validasi keamanan: Pastikan booking ini memang untuk kos milik si owner ini
    // Kita cek join ke tabel kos
    $checkSql = "SELECT b.id FROM bookings b 
                 JOIN kos k ON b.kos_id = k.id 
                 WHERE b.id = ? AND k.owner_id = ?";
    $stmtCheck = $pdo->prepare($checkSql);
    $stmtCheck->execute([$booking_id, $owner_id]);
    
    if ($stmtCheck->fetch()) {
        // Jika valid, update status
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        
        $updateSql = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmtUpdate = $pdo->prepare($updateSql);
        if ($stmtUpdate->execute([$newStatus, $booking_id])) {
            // Jika approve, kita bisa opsional ubah status kamar jadi 'terisi' (tergantung logika bisnis)
            // if ($newStatus === 'approved') { ... update kamar set status='terisi' ... }
            
            $message = "Berhasil memperbarui status pengajuan.";
        }
    } else {
        $message = "Terjadi kesalahan: Data tidak ditemukan atau bukan milik Anda.";
    }
}

// 3. AMBIL DAFTAR PENGAJUAN (JOIN TABEL)
// Kita ambil data booking, data user (pencari), data kos, dan data kamar
$sql = "
    SELECT 
        b.id AS booking_id, b.start_date, b.status, b.created_at,
        u.fullname AS pencari_nama, u.phone AS pencari_phone, u.email AS pencari_email,
        k.name AS nama_kos,
        km.name AS nama_kamar, km.price
    FROM bookings b
    JOIN kos k ON b.kos_id = k.id
    JOIN users u ON b.user_id = u.id
    JOIN kamar km ON b.kamar_id = km.id
    WHERE k.owner_id = ?
    ORDER BY 
        CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END, -- Tampilkan pending paling atas
        b.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$owner_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;
?>
<?php include 'includes/header.php'; ?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Pengajuan Sewa - NgekosAja.id</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root{ --primary:#00BFA6; --secondary:#FF8A65; --light:#fff; --bg:#F4F8F9; --dark:#333; }
        body{ font-family:'Nunito Sans',sans-serif; background:#f5efeb; color:var(--dark); margin:0; }
        .wrap{ max-width:1000px; margin:30px auto; padding:0 20px; }
        
        /* Header */
        .header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .btn-back{ text-decoration:none; color: #555977ff; font-weight:700; display:flex; align-items:center; gap:5px; }
        
        /* Alert */
        .alert{ padding:15px; background:#d1e7dd; color:#0f5132; border-radius:10px; margin-bottom:20px; }

        /* Card List */
        .booking-card{ background: #cbcac8e8; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; border-left:5px solid #ddd; }
        
        /* Status Colors Border */
        .booking-card.pending { border-left-color: #7c7c9eff; } /* Kuning */
        .booking-card.approved { border-left-color: #6684b0ff; } /* Hijau */
        .booking-card.rejected { border-left-color: #4b5376ff; } /* Merah */

        .info h4{ margin:0 0 5px; font-family:'Nunito Sans',sans-serif; font-size:18px; }
        .meta{ color:#666; font-size:14px; margin-bottom:5px; }
        .pencari{ font-weight:700; color:var(--primary); }
        
        .actions{ display:flex; gap:10px; align-items:center; }
        
        .btn{ padding:8px 16px; border-radius:8px; border:none; font-weight:700; cursor:pointer; transition:0.2s; text-decoration:none; display:inline-block; font-size:14px; }
        .btn-acc{ background: #6a7181ff; color:#fff; }
        .btn-acc:hover{ background:#008f7a; }
        .btn-tolak{ background: #ffffff13; border:1px solid #8c4847ff; color:#EF5350; }
        .btn-tolak:hover{ background: #FFEBEE; }
        .btn-wa{ background:#25D366; color:#fff; display:flex; align-items:center; gap:5px; }

        .badge{ padding:5px 10px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; }
        .badge.pending{ background:#FFF3E0; color:#F57C00; }
        .badge.approved{ background:#E0F2F1; color:#00695C; }
        .badge.rejected{ background:#FFEBEE; color:#C62828; }

        @media(max-width:600px){ .booking-card{ flex-direction:column; align-items:flex-start; } .actions{ width:100%; justify-content:space-between; } }
    </style>
</head>
<body>

<div class="wrap">
    <div class="header">
        <div>
            <h2 style="margin:0; font-family:'Nunito Sans',sans-serif;">Daftar Pengajuan Sewa</h2>
            <p style="margin:5px 0 0; color:#666;">Kelola siapa saja yang ingin masuk ke kos Anda.</p>
        </div>
        <a href="dashboard_owner.php" class="btn-back">&larr; Kembali ke Dashboard</a>
    </div>

    <?php if($message): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if(count($bookings) > 0): ?>
        <?php foreach($bookings as $b): ?>
            <div class="booking-card <?= $b['status'] ?>">
                <div class="info">
                    <h4><?= htmlspecialchars($b['pencari_nama']) ?> <span style="font-weight:400; font-size:14px; color:#888;">(Calon Penghuni)</span></h4>
                    
                    <div class="meta">
                        🏠 <strong><?= htmlspecialchars($b['nama_kos']) ?></strong> — <?= htmlspecialchars($b['nama_kamar']) ?>
                    </div>
                    <div class="meta">
                        📅 Mulai: <?= date('d M Y', strtotime($b['start_date'])) ?>
                    </div>
                    
                    <?php if(!empty($b['pencari_phone'])): 
                        // Format nomor WA (hapus 0 depan ganti 62)
                        $wa = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $b['pencari_phone']));
                    ?>
                    <div style="margin-top:8px;">
                        <a href="https://wa.me/<?= $wa ?>" target="_blank" class="btn btn-wa">
                            Hubungi via WhatsApp
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <?php if($b['status'] === 'pending'): ?>
                        <form method="post" onsubmit="return confirm('Yakin ingin menolak pengajuan ini?');">
                            <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-tolak">Tolak</button>
                        </form>

                        <form method="post" onsubmit="return confirm('Yakin ingin menerima pengajuan ini?');">
                            <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-acc">✔ Terima (ACC)</button>
                        </form>
                    <?php else: ?>
                        <span class="badge <?= $b['status'] ?>">
                            <?= $b['status'] === 'approved' ? 'Sudah Disetujui' : 'Ditolak' ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding:50px; background:#fff; border-radius:15px;">
            <img src="https://img.icons8.com/ios/100/dddddd/opened-folder.png" alt="Empty">
            <h3 style="color:#999;">Belum ada pengajuan baru</h3>
            <p style="color:#bbb;">Daftar pengajuan sewa akan muncul di sini.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>