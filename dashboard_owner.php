<?php
// dashboard_owner.php (root)
session_start();
// Pastikan db.php ada di direktori root
require_once __DIR__ . '/db.php'; 

// Cek login & role pemilik
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    header('Location: login.php');
    exit;
}

$owner_id = (int)$_SESSION['user_id'];
// Ambil nama pemilik untuk ditampilkan
$owner_fullname = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Pemilik Kos'; 

$baseUrl = '/NgekosAja2.id/'; // sesuaikan jika project bukan di subfolder

// Tangani delete (opsional)
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_kos_id'])) {
    $delId = (int)$_POST['delete_kos_id'];
    
    // Pastikan kos milik owner
    $check = $pdo->prepare("SELECT id FROM kos WHERE id = ? AND owner_id = ? LIMIT 1");
    $check->execute([$delId, $owner_id]);
    
    if ($check->fetch()) {
        // ambil images untuk dihapus file fisik
        $stmtImgs = $pdo->prepare("SELECT filename FROM kos_images WHERE kos_id = ?");
        $stmtImgs->execute([$delId]);
        $imgs = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

        try {
            $pdo->beginTransaction();
            // Hapus data terkait di tabel anak dulu
            $pdo->prepare("DELETE FROM kos_images WHERE kos_id = ?")->execute([$delId]);
            $pdo->prepare("DELETE FROM kamar WHERE kos_id = ?")->execute([$delId]);
            // Jika ada tabel bookings yang terhubung ke kos, hapus juga:
            // $pdo->prepare("DELETE FROM bookings WHERE kos_id = ?")->execute([$delId]);
            
            // Hapus data kos utama
            $pdo->prepare("DELETE FROM kos WHERE id = ?")->execute([$delId]);
            $pdo->commit();

            // hapus file fisik (jika ada)
            foreach ($imgs as $f) {
                $path = __DIR__ . '/' . ltrim($f, '/');
                if (is_file($path)) @unlink($path);
            }
            $messages[] = "Kos berhasil dihapus.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $messages[] = "Gagal menghapus: " . $e->getMessage();
        }
    } else {
        $messages[] = "Kos tidak ditemukan atau bukan milik Anda.";
    }
}

// Ambil daftar kos milik owner beserta satu thumbnail dan jumlah kamar + pending booking
$stmt = $pdo->prepare("
    SELECT k.*,
      (SELECT filename FROM kos_images WHERE kos_images.kos_id = k.id ORDER BY id ASC LIMIT 1) AS thumb,
      (SELECT COUNT(*) FROM kamar km WHERE km.kos_id = k.id) AS total_kamar,
      (SELECT COUNT(*) FROM bookings b WHERE b.kos_id = k.id AND b.status = 'pending') AS pending_bookings
    FROM kos k
    WHERE k.owner_id = ?
    ORDER BY k.created_at DESC
");
$stmt->execute([$owner_id]);
$kosList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdo = null; // Tutup koneksi
// include header jika ada (opsional)
if (file_exists(__DIR__ . '/includes/header.php')) include __DIR__ . '/includes/header.php';
?>
<div class="container my-5" style="max-width:1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Dashboard Pemilik</h3>
        <div>
            <span class="me-3">Halo, <b><?= htmlspecialchars($owner_fullname) ?></b></span>
            <a href="kos/add.php" class="btn btn-primary">Tambah Kos Baru</a>
            <a href="logout.php" class="btn btn-outline-secondary ms-2">Logout</a>
        </div>
    </div>

    <?php foreach ($messages as $m): ?>
        <div class="alert alert-info"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>

    <?php if (empty($kosList)): ?>
        <div class="card p-4 mb-3">
            <p class="mb-0">Anda belum menambahkan kos. Klik "Tambah Kos Baru" untuk mulai.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($kosList as $k):
                $thumbUrl = !empty($k['thumb']) ? ($baseUrl . ltrim($k['thumb'], '/')) : "https://picsum.photos/seed/kos{$k['id']}/600/400";
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card p-0" style="border-radius:12px;overflow:hidden;">
                    <div style="height:180px;overflow:hidden;background:#f5f5f5">
                        <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="thumb" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div class="p-3">
                        <h5 class="mb-1"><?= htmlspecialchars($k['name']) ?></h5>
                        <div class="text-muted small mb-2"><?= htmlspecialchars($k['city']) ?> • <?= htmlspecialchars($k['type']) ?></div>
                        <div class="fw-bold mb-2">Rp <?= number_format($k['price'],0,',','.') ?> / bulan</div>
                        
                        <div class="d-flex gap-2">
                            <a href="kos/detail.php?id=<?= (int)$k['id'] ?>" class="btn btn-outline-primary btn-sm">Preview</a>
                            <a href="kos/edit.php?id=<?= (int)$k['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>

                            <form method="post" style="display:inline" onsubmit="return confirm('Hapus kos ini dan semua data terkait?');">
                                <input type="hidden" name="delete_kos_id" value="<?= (int)$k['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>

                        <div style="margin-top:8px;font-size:13px;color:#666">
                            <?= (int)$k['total_kamar'] ?> kamar • <a href="dashboard_bookings.php?kos_id=<?= (int)$k['id'] ?>" style="text-decoration:none;color:#d9534f;font-weight:700;">
                                <?= (int)$k['pending_bookings'] ?> pengajuan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// include footer jika ada
if (file_exists(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php';
?>