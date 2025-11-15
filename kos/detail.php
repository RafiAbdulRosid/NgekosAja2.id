<?php
// kos/detail.php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

// Pastikan jalur ke db.php sudah benar. Asumsi: kos/detail.php berada di dalam folder kos/
require_once __DIR__ . '/../db.php';

// base URL (sesuaikan folder projectmu)
$baseUrl = '/NgekosAja.id/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400); echo "ID kos tidak valid."; exit;
}

// 1. Ambil kos + owner (menggunakan tabel 'kos')
$stmt = $pdo->prepare("SELECT k.*, u.fullname AS owner_name, u.phone AS owner_phone, u.email AS owner_email FROM kos k JOIN users u ON u.id = k.owner_id WHERE k.id = ? LIMIT 1");
$stmt->execute([$id]);
$kos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kos) { 
    http_response_code(404); echo "Kos tidak ditemukan."; 
    $pdo = null; // Tutup koneksi
    exit; 
}

// 2. Ambil kamar (menggunakan tabel 'kamar')
$stmt = $pdo->prepare("SELECT * FROM kamar WHERE kos_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$kamars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Ambil images (menggunakan tabel 'kos_images')
$stmt = $pdo->prepare("SELECT filename FROM kos_images WHERE kos_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Cek login/role
$is_logged_in = !empty($_SESSION['user_id']);
$current_role = $_SESSION['role'] ?? null;

// --- LOGIKA BACK LINK BARU ---
// Penjelasan: Jika yang melihat adalah pemilik kos yang sama, kembali ke dashboard.
if ($is_logged_in && $current_role === 'pemilik' && $_SESSION['user_id'] == $kos['owner_id']) {
    $backUrl = $baseUrl . 'dashboard_owner.php';
} else {
    $backUrl = $baseUrl . 'kos/list.php';
}
// -----------------------------

// Tutup koneksi setelah selesai mengambil data
$pdo = null; 
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail - <?= htmlspecialchars($kos['name']) ?> | NgekosAja.id</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#00BFA6;--secondary:#FF8A65;--light:#FAFAFA;--dark:#263238}
        *{box-sizing:border-box}
        body{font-family:'Nunito Sans',sans-serif;margin:0;background:var(--light);color:var(--dark)}
        .wrap{max-width:1100px;margin:28px auto;padding:0 16px}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:12px 0}
        .card{background:#fff;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.06);overflow:hidden}
        .gallery{display:grid;grid-template-columns:1fr 140px;gap:12px}
        .gallery img{width:100%;height:360px;object-fit:cover;border-radius:6px}
        .thumbs{display:flex;flex-direction:column;gap:8px}
        .thumbs img{height:80px;object-fit:cover;border-radius:6px;cursor:pointer;opacity:.95;border:2px solid transparent}
        .thumbs img.active{border-color:var(--primary);opacity:1}
        .info{padding:18px}
        .info h1{font-family:'Poppins',sans-serif;margin:0 0 6px}
        .meta{color:#607D8B;font-size:14px;margin-bottom:10px}
        .price{font-weight:700;font-size:18px;margin-bottom:8px}
        .kamar-list{padding:14px}
        .kamar-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px dashed #eee}
        .badge{padding:6px 10px;border-radius:10px;font-weight:700;font-size:13px}
        .badge.kosong{background:#A5D6A7;color:#063}
        .btn{display:inline-block;padding:10px 14px;border-radius:10px;border:none;background:var(--secondary);color:#fff;font-weight:700;text-decoration:none;cursor:pointer}
        .btn.alt{background:#fff;color:var(--dark);border:1px solid #ddd}
        .aside{margin-left:20px}
        @media(max-width:900px){ .gallery{grid-template-columns:1fr} .thumbs{flex-direction:row;overflow:auto} .aside{margin-left:0} }
        .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.45)}
        .modal.show{display:flex}
        .modal .box{background:#fff;padding:18px;border-radius:10px;max-width:420px;width:100%}
        label{display:block;margin:8px 0 4px;font-weight:600}
        input,textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid #ddd}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar"><a href="<?= $baseUrl ?>index.php" style="text-decoration:none;font-weight:700;color:var(--primary)">NgekosAja.id</a>
            <div>
                <?php if($is_logged_in): ?>
                    Halo, <?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User') ?> — <a href="<?= $baseUrl ?>logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>login.php">Login</a> | <a href="<?= $baseUrl ?>register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding:0 0 18px">
            <div style="padding:16px">
                <a href="<?= htmlspecialchars($backUrl) ?>" style="color:var(--primary);text-decoration:none">← Kembali</a>
            </div>

            <div style="padding:0 18px 18px">
                <div style="display:flex;gap:18px;flex-wrap:wrap">
                    <div style="flex:1;min-width:320px">
                        <div class="gallery card" style="padding:12px">
                            <div>
                                <?php
                                    if (!empty($images)) {
                                        $mainSrc = $baseUrl . ltrim($images[0], '/');
                                    } else {
                                        $mainSrc = "https://picsum.photos/seed/kos{$id}/1200/700";
                                    }
                                ?>
                                <img id="mainImg" src="<?= htmlspecialchars($mainSrc) ?>" alt="main">
                            </div>

                            <div class="thumbs">
                                <?php if (!empty($images)):
                                    foreach($images as $i => $fn):
                                        $url = $baseUrl . ltrim($fn, '/');
                                ?>
                                    <img src="<?= htmlspecialchars($url) ?>" data-src="<?= htmlspecialchars($url) ?>" class="<?= $i===0 ? 'active' : '' ?>" alt="t<?= $i ?>">
                                <?php endforeach; else:
                                    for($i=1;$i<=4;$i++):
                                        $seed = $id . '-' . $i; ?>
                                        <img src="https://picsum.photos/seed/<?= $seed ?>/400/300" data-src="https://picsum.photos/seed/<?= $seed ?>/1200/700" class="<?= $i===1 ? 'active' : '' ?>">
                                <?php endfor; endif; ?>
                            </div>
                        </div>

                        <div class="card info" style="margin-top:14px">
                            <h1><?= htmlspecialchars($kos['name']) ?></h1>
                            <div class="meta"><?= htmlspecialchars($kos['city']) ?> • <?= htmlspecialchars($kos['type']) ?></div>
                            <div class="price">Rp <?= number_format($kos['price'],0,',','.') ?> / bulan</div>
                            <div class="keterangan"><?= nl2br(htmlspecialchars($kos['description'] ?: 'Tidak ada deskripsi.')) ?></div>
                        </div>

                        <div class="card kamar-list" style="margin-top:14px;padding:12px">
                            <h3 style="margin:6px 0 10px;font-family:'Poppins',sans-serif">Daftar Kamar</h3>
                            <?php if($kamars): foreach($kamars as $k): ?>
                                <div class="kamar-item">
                                    <div>
                                        <div style="font-weight:700"><?= htmlspecialchars($k['name']) ?></div>
                                        <div style="color:#78909C">Rp <?= number_format($k['price'],0,',','.') ?></div>
                                    </div>
                                    <div style="text-align:right">
                                        <span class="badge <?= $k['status']=='kosong' ? 'kosong' : '' ?>"><?= htmlspecialchars($k['status']) ?></span>
                                        <?php if($k['status']=='kosong'): ?>
                                            <?php if($is_logged_in && $current_role === 'pencari'): ?>
                                                <button class="btn" onclick="openBooking(<?= $k['id'] ?>,'<?= htmlspecialchars($k['name'],ENT_QUOTES) ?>')">Ajukan Sewa</button>
                                            <?php elseif(!$is_logged_in): ?>
                                                <a class="btn" href="<?= $baseUrl ?>login.php">Login untuk sewa</a>
                                            <?php else: ?>
                                                <span class="small" style="display:block;margin-top:8px;color:#999">Hanya pencari dapat booking</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="small" style="color:#999">Tidak tersedia</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <div>Belum ada kamar yang diinput.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside style="width:320px" class="aside">
                        <div class="card" style="padding:14px">
                            <h4 style="margin:0 0 8px">Info Pemilik</h4>
                            <div style="font-weight:700"><?= htmlspecialchars($kos['owner_name']) ?></div>
                            <?php if(!empty($kos['owner_phone'])): ?><div><?= htmlspecialchars($kos['owner_phone']) ?></div><?php endif; ?>
                            <?php if(!empty($kos['owner_email'])): ?><div><?= htmlspecialchars($kos['owner_email']) ?></div><?php endif; ?>
                            <div style="margin-top:12px">
                                <?php if($is_logged_in && $current_role==='pencari'): ?>
                                    <a class="btn" href="<?= $baseUrl ?>kos/booking.php?kos_id=<?= $id ?>">Ajukan Sewa</a>
                                <?php else: ?>
                                    <a class="btn" href="<?= $baseUrl ?>login.php">Login untuk Ajukan</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="height:12px"></div>

                        <div class="card" style="padding:12px">
                            <h4 style="margin-top:0">Lokasi</h4>
                            <?php $addr = rawurlencode($kos['address'] ?? $kos['city'] ?? ''); ?>
                            <?php if(!empty($addr)): ?>
                                <iframe src="https://maps.google.com/maps?q=<?= $addr ?>&output=embed" style="width:100%;height:200px;border:0;border-radius:8px"></iframe>
                            <?php else: ?>
                                <div>Alamat belum diisi.</div>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <div id="modalBook" class="modal" role="dialog" aria-hidden="true">
        <div class="box">
            <h4>Ajukan Sewa - <span id="bkRoomName"></span></h4>
            <form id="formBook" method="post" action="<?= $baseUrl ?>kos/booking.php">
                <input type="hidden" name="kos_id" value="<?= $id ?>">
                <input type="hidden" name="kamar_id" id="bkKamarId" value="">
                <label>Pesan untuk pemilik (opsional)</label>
                <textarea name="message" rows="4" placeholder="Perkenalkan diri, tanggal masuk, pertanyaan..."></textarea>
                <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn alt" onclick="closeBooking()">Batal</button>
                    <button type="submit" class="btn">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // gallery thumb handlers
        document.querySelectorAll('.thumbs img').forEach(function(img){
            img.addEventListener('click', function(){
                document.getElementById('mainImg').src = this.dataset.src || this.src;
                document.querySelectorAll('.thumbs img').forEach(i=>i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // booking modal
        function openBooking(kamarId, kamarName){
            document.getElementById('bkKamarId').value = kamarId;
            document.getElementById('bkRoomName').textContent = kamarName;
            document.getElementById('modalBook').classList.add('show');
        }
        function closeBooking(){
            document.getElementById('modalBook').classList.remove('show');
        }
    </script>
</body>
</html>