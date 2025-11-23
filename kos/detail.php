<?php
// kos/detail.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Pastikan path ke db.php benar (naik satu folder)
require_once __DIR__ . '/../db.php';

$baseUrl = '/NgekosAja2.id/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "ID kos tidak valid."; exit;
}


// 1. Ambil data Kos + Owner
$stmt = $pdo->prepare("SELECT k.*, u.fullname AS owner_name, u.phone AS owner_phone, u.email AS owner_email FROM kos k JOIN users u ON u.id = k.owner_id WHERE k.id = ? LIMIT 1");
$stmt->execute([$id]);
$kos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kos) { 
    echo "Kos tidak ditemukan."; 
    $pdo = null; exit; 
}

// 2. Ambil Kamar
$stmt = $pdo->prepare("SELECT * FROM kamar WHERE kos_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$kamars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Ambil Gambar
$stmt = $pdo->prepare("SELECT filename FROM kos_images WHERE kos_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);


// --- START: LOGIKA REVIEW BARU ---

// 4. Ambil Reviews
$stmtReview = $pdo->prepare("
    SELECT r.*, u.fullname 
    FROM review r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.kos_id = ? 
    ORDER BY r.created_at DESC
");
$stmtReview->execute([$id]);
$reviews = $stmtReview->fetchAll(PDO::FETCH_ASSOC);

// 5. Hitung Rata-rata Rating
$avgRating = 0;
if (!empty($reviews)) {
    $totalRating = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($totalRating / count($reviews), 1);
}

// Cek Pesan dari submit_review.php
$review_message = $_GET['success'] ?? $_GET['error'] ?? '';
$message_type = isset($_GET['success']) ? 'success' : (isset($_GET['error']) ? 'error' : '');

// --- END: LOGIKA REVIEW BARU ---


// Cek Login & Role
$is_logged_in = !empty($_SESSION['user_id']);
$current_role = $_SESSION['role'] ?? null;

// Logika Tombol Kembali
if ($is_logged_in && $current_role === 'pemilik' && $_SESSION['user_id'] == $kos['owner_id']) {
    $backUrl = $baseUrl . 'dashboard_owner.php';
} else {
    $backUrl = $baseUrl . 'index.php'; // Kembali ke pencarian utama
}

$pdo = null; // Tutup koneksi
?>
<?php 
include __DIR__ . '/../includes/header.php';
?>
<!doctype html>
<html lang="id"

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail - <?= htmlspecialchars($kos['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary: #51537dff;
            --secondary: #e0dbd4ff;
            --light: #ffffffff;
            --bg-light: #f5efeb ;
            --dark: #42545dff;
            --text-muted:#607D8B;
            --success: #4f5477ff;
            --error: #C62828;
        }
        *{box-sizing:border-box}
        body{font-family:'Nunito Sans',sans-serif;margin:0;background:var(--bg-light);color:var(--dark)}
        
        .wrap{max-width:1100px;margin:20px auto;padding:0 20px}
        
        /* Header & Nav */
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:15px 0; margin-bottom:10px;}
        .logo{font-family:'Nunito Sans',sans-serif;font-weight:700;font-size:22px;color:var(--primary);text-decoration:none}
        .btn-link{color: #4e667dff;text-decoration:none;font-weight:600;}
        
        /* Layout Utama */
        .main-content{display:grid; grid-template-columns: 1fr 340px; gap:25px;}
        @media(max-width:900px){ .main-content{grid-template-columns:1fr;} }

        /* Kartu Putih */
        .card{background:var(--light); border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.05); padding:20px; overflow:hidden;}
        
        /* Gallery */
        .gallery img#mainImg{width:100%; height:400px; object-fit:cover; border-radius:12px; margin-bottom:10px;}
        .thumbs{display:flex; gap:10px; overflow-x:auto;}
        .thumbs img{width:80px; height:60px; object-fit:cover; border-radius:8px; cursor:pointer; opacity:0.7; transition:0.3s;}
        .thumbs img.active, .thumbs img:hover{opacity:1; border:2px solid var(--primary);}

        /* Info Kos */
        .info h1{font-family:'Nunito Sans',sans-serif; margin:0 0 5px; font-size:28px;}
        .meta{color:var(--text-muted); margin-bottom:15px; font-size:15px;}
        .price{font-size:24px; font-weight:700; color: #505d73ff; margin-bottom:15px;}
        .desc{line-height:1.6; color:#555; text-align: justify}

        /* Daftar Kamar */
        .kamar-list{margin-top:25px;}
        .kamar-item{
            display:flex; justify-content:space-between; align-items:center;
            padding:15px; border:1px solid #eee; border-radius:10px; margin-bottom:10px;
            background:#fff; transition:transform 0.2s;
        }
        .kamar-item:hover{transform:translateY(-3px); box-shadow:0 5px 15px rgba(0,0,0,0.05);}
        .kamar-info .k-name{font-weight:700; font-size:16px;}
        .kamar-info .k-price{color:var(--text-muted); font-size:14px;}
        
        /* Badges & Buttons */
        .badge{padding:5px 10px; border-radius:6px; font-size:12px; font-weight:700;}
        .badge.kosong{background:#E8F5E9; color:#2E7D32;}
        .badge.penuh{background:#FFEBEE; color:#C62828;}

        .btn{
            padding:10px 18px; border-radius:8px; border:none; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block;
            transition:0.2s;
        }
        .btn-primary{background:var(--secondary); color:#fff;}
        .btn-primary:hover{background:#FF7043; transform:translateY(-2px);}
        .btn-login{background:#eee; color:#555;}

        /* MODAL STYLE */
        .modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex; justify-content: center; align-items: center; z-index: 1000;
            visibility: hidden; opacity: 0; transition: visibility 0s, opacity 0.3s;
        }
        .modal.show { visibility: visible; opacity: 1; }
        .modal-box {
            background: var(--light); border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%; max-width: 420px; transform: translateY(-20px); transition: transform 0.3s ease-out;
            padding:0;
        }
        .modal.show .modal-box { transform: translateY(0); }
        .modal-header{ padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
        .modal-body{ padding:25px; }
        .modal-footer{ padding:15px 20px; background:#f9f9f9; border-radius:0 0 15px 15px; text-align:right; }
        
        .form-control-modal{ width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; }
        .close-btn{ background:none; border:none; font-size:24px; cursor:pointer; color:#999; }
        
        /* Review Section Style */
        .review-card h3 { font-family:'Nunito Sans',sans-serif; margin-top:0;}
        .star-rating { color: gold; }
        .star-empty { color: #ddd; }
        .review-item { border-top: 1px dashed #eee; padding: 15px 0; }
        .review-author { font-weight: 700; color: var(--dark); }
        .review-meta { color: var(--text-muted); font-size: 13px; margin-top: 5px; }
        
        /* Notif Style */
        .notif { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .notif.success { background: #E8F5E9; color: var(--success); }
        .notif.error { background: #FFEBEE; color: var(--error); }
    </style>
</head>
<body>

<div class="wrap">
    <?php if ($review_message): ?>
        <div class="notif <?= $message_type ?>">
            <?= htmlspecialchars($review_message) ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom:15px;">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-link">← Kembali</a>
    </div>

    <div class="main-content">
        <div class="left-col">
            <div class="card gallery">
                <?php
                    if (!empty($images)) {
                        $mainSrc = $baseUrl . ltrim($images[0], '/');
                    } else {
                        $mainSrc = "https://picsum.photos/seed/kos{$id}/800/500";
                    }
                ?>
                <img id="mainImg" src="<?= htmlspecialchars($mainSrc) ?>" alt="Foto Utama">
                <div class="thumbs">
                    <?php if($images): foreach($images as $i=>$img): $src=$baseUrl.ltrim($img,'/'); ?>
                        <img src="<?= $src ?>" onclick="changeImg('<?= $src ?>')" class="<?= $i==0?'active':'' ?>">
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="card info" style="margin-top:20px;">
                <h1><?= htmlspecialchars($kos['name']) ?></h1>
                <div class="meta">
                    <?= htmlspecialchars($kos['city']) ?> • <?= htmlspecialchars($kos['type']) ?>
                </div>
                <div class="price">
                    Rp <?= number_format($kos['price'], 0, ',', '.') ?> / bulan
                </div>
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                <h4>Deskripsi</h4>
                <div class="desc"><?= nl2br(htmlspecialchars($kos['description'])) ?></div>
            </div>

            <div class="card kamar-list">
                <h3 style="margin-top:0;">Pilih Kamar</h3>
                
                <?php if($kamars): foreach($kamars as $k): ?>
                    <div class="kamar-item">
                        <div class="kamar-info">
                            <div class="k-name"><?= htmlspecialchars($k['name']) ?></div>
                            <div class="k-price">Rp <?= number_format($k['price'],0,',','.') ?></div>
                        </div>
                        <div class="kamar-action">
                            <?php if($k['status'] == 'kosong'): ?>
                                <span class="badge kosong" style="margin-right:10px;">Tersedia</span>
                                
                                <?php if($is_logged_in && $current_role === 'pencari'): ?>
                                    <button class="btn btn-primary" onclick="openBooking(<?= $k['id'] ?>, '<?= htmlspecialchars($k['name'], ENT_QUOTES) ?>')">
                                        Ajukan Sewa
                                    </button>
                                <?php elseif(!$is_logged_in): ?>
                                    <a href="<?= $baseUrl ?>login.php" class="btn btn-login">Login utk Sewa</a>
                                <?php endif; ?>

                            <?php else: ?>
                                <span class="badge penuh">Penuh / Terisi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <p>Belum ada data kamar.</p>
                <?php endif; ?>
            </div>
            
            <div class="card review-card" style="margin-top:20px;">
                <h3>⭐️ Berikan Ulasan Anda</h3>
                <?php if ($is_logged_in && $current_role === 'pencari'): ?>
                    <form action="<?= $baseUrl ?>kos/submit_review.php" method="post">
                        <input type="hidden" name="kos_id" value="<?= $id ?>">
                        
                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:5px; font-weight:600;">Rating Bintang:</label>
                            <select name="rating" required class="form-control-modal" style="width:100%; padding:10px;">
                                <option value="">Pilih Rating</option>
                                <option value="5">5 Bintang (Sangat Baik)</option>
                                <option value="4">4 Bintang (Baik)</option>
                                <option value="3">3 Bintang (Cukup)</option>
                                <option value="2">2 Bintang (Kurang)</option>
                                <option value="1">1 Bintang (Buruk)</option>
                            </select>
                        </div>
                        
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Komentar:</label>
                        <textarea name="comment" rows="4" placeholder="Tulis pengalaman Anda menyewa di kos ini..." 
                                  style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;"></textarea>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Kirim Ulasan</button>
                    </form>
                <?php else: ?>
                    <p style="color:var(--text-muted);">Hanya pengguna yang login sebagai Pencari Kos yang dapat memberikan ulasan.</p>
                    <a href="<?= $baseUrl ?>login.php" class="btn btn-login">Login Sekarang</a>
                <?php endif; ?>
            </div>
            <div class="card review-card" style="margin-top:20px;">
                <h3>⭐ Ulasan Pengguna (<?= $avgRating ?>/5.0)</h3>
                <p style="color:var(--text-muted);"><?= count($reviews) ?> ulasan total</p>
                
                <?php if (!empty($reviews)): ?>
                    <?php foreach($reviews as $r): ?>
                        <div class="review-item">
                            <div class="review-author">
                                <?= htmlspecialchars($r['fullname']) ?> 
                                <span style="float:right;">
                                    <span class="star-rating"><?= str_repeat('★', $r['rating']) ?></span>
                                    <span class="star-empty"><?= str_repeat('★', 5 - $r['rating']) ?></span>
                                </span>
                            </div>
                            <p style="margin:5px 0;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                            <div class="review-meta">Tanggal: <?= date('d M Y', strtotime($r['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#999;">Belum ada ulasan untuk kos ini. Jadilah yang pertama!</p>
                <?php endif; ?>
            </div>
            </div>

        <aside class="right-col">
            <div class="card">
                <h4 style="margin-top:0;">Pemilik Kos</h4>
                <div style="font-weight:700; margin-bottom:5px;"><?= htmlspecialchars($kos['owner_name']) ?></div>
                <div style="font-size:14px; color:#666;">Hubungi via aplikasi untuk detail lebih lanjut.</div>
            </div>

            <div class="card" style="margin-top:20px;">
                <h4 style="margin-top:0;">Lokasi</h4>
                <?php $addr = rawurlencode($kos['address'] ?? $kos['city'] ?? ''); ?>
                <iframe src="https://maps.google.com/maps?q=<?= $addr ?>&output=embed" style="width:100%;height:200px;border:0;border-radius:8px;"></iframe>
            </div>
        </aside>
    </div>
</div>

<div id="modalBook" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h4 style="margin:0;">Ajukan Sewa</h4>
            <button class="close-btn" onclick="closeBooking()">×</button>
        </div>
        <form method="post" action="<?= $baseUrl ?>kos/booking.php">
            <div class="modal-body">
                <p style="margin-top:0;">Kamar: <strong id="bkRoomName" style="color:var(--primary)"></strong></p>
                
                <input type="hidden" name="kos_id" value="<?= $id ?>">
                <input type="hidden" name="kamar_id" id="bkKamarId" value="">
                
                <label style="display:block; margin-bottom:8px; font-weight:600;">Mulai Sewa Tanggal:</label>
                <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>" class="form-control-modal">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:transparent; color:#666;" onclick="closeBooking()">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Ganti Gambar Gallery
    function changeImg(src) {
        document.getElementById('mainImg').src = src;
    }

    // Buka Modal Booking
    function openBooking(kamarId, kamarName) {
        document.getElementById('bkKamarId').value = kamarId;
        document.getElementById('bkRoomName').textContent = kamarName;
        document.getElementById('modalBook').classList.add('show');
    }

    // Tutup Modal
    function closeBooking() {
        document.getElementById('modalBook').classList.remove('show');
    }
    
    // Tutup jika klik di luar box
    window.onclick = function(event) {
        var modal = document.getElementById('modalBook');
        if (event.target == modal) {
            closeBooking();
        }
    }
</script>

</body>
</html>