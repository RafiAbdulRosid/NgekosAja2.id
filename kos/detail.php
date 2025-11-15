<?php
// kos/detail.php
session_start();
require_once __DIR__ . '/../db.php'; // pastikan path ini benar

// Ambil id dari query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "ID kos tidak valid.";
    exit;
}

// Ambil data kos + pemilik
$stmt = $pdo->prepare("
    SELECT k.*, u.fullname AS owner_name, u.phone AS owner_phone, u.email AS owner_email
    FROM kos k
    JOIN users u ON u.id = k.owner_id
    WHERE k.id = ? LIMIT 1
");
$stmt->execute([$id]);
$kos = $stmt->fetch();

if (!$kos) {
    http_response_code(404);
    echo "Kos tidak ditemukan.";
    exit;
}

// Ambil daftar kamar
$stmt2 = $pdo->prepare("SELECT * FROM kamar WHERE kos_id = ? ORDER BY id ASC");
$stmt2->execute([$id]);
$kamars = $stmt2->fetchAll();

// Helper: cek login
$is_logged_in = !empty($_SESSION['user_id']);
$current_role = $_SESSION['role'] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detail - <?= htmlspecialchars($kos['name']) ?> | NgekosAja.id</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito+Sans:wght@400;500&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary:#00BFA6;
      --secondary:#FF8A65;
      --accent:#FFD54F;
      --light:#FAFAFA;
      --dark:#263238;
    }
    *{box-sizing:border-box}
    body{font-family:'Nunito Sans',sans-serif;margin:0;background:var(--light);color:var(--dark)}
    .topbar{background:linear-gradient(90deg,var(--primary),var(--secondary));padding:16px 40px;color:#fff;display:flex;justify-content:space-between;align-items:center}
    .topbar .brand{font-family:'Poppins',sans-serif;font-weight:700;font-size:20px}
    .container{max-width:1100px;margin:28px auto;padding:0 20px}
    .back{display:inline-block;margin-bottom:18px;color:var(--primary);text-decoration:none;font-weight:600}
    .layout{display:grid;grid-template-columns: 1fr 360px;gap:22px}
    @media(max-width:900px){ .layout{grid-template-columns:1fr} }

    .card{background:#fff;border-radius:18px;box-shadow:0 8px 24px rgba(0,0,0,0.08);overflow:hidden}
    .gallery{display:grid;grid-template-columns:1fr 180px;gap:10px}
    @media(max-width:700px){ .gallery{grid-template-columns:1fr; } .gallery .thumbs{display:flex;gap:8px;overflow:auto;padding:8px} .gallery .thumbs img{height:80px} }

    .gallery .main img{width:100%;height:360px;object-fit:cover;display:block}
    .gallery .thumbs img{width:100%;height:110px;object-fit:cover;border-radius:10px;cursor:pointer;opacity:.9;border:2px solid transparent}
    .gallery .thumbs img.active{border-color:var(--primary);opacity:1}

    .info{padding:20px}
    .info h1{font-family:'Poppins',sans-serif;margin:0 0 6px;font-size:22px}
    .meta{color:#607D8B;font-size:14px;margin-bottom:12px}
    .price{font-weight:700;color:var(--dark);font-size:18px;margin-bottom:8px}
    .desc{color:#455A64;line-height:1.6}

    .kamar-list{padding:16px}
    .kamar-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px dashed #eee}
    .kamar-item:last-child{border-bottom:none}
    .badge{display:inline-block;padding:6px 10px;border-radius:12px;font-weight:700;font-size:13px}
    .badge.kosong{background:#A5D6A7;color:#063; }
    .badge.terisi{background:#ECEFF1;color:#607D8B}

    .aside{position:relative}
    .contact{padding:18px}
    .contact h4{margin:0 0 8px;font-family:'Poppins',sans-serif}
    .contact p{margin:6px 0;color:#455A64}
    .btn{display:inline-block;padding:10px 16px;border-radius:12px;border:none;background:var(--secondary);color:#fff;font-weight:700;cursor:pointer;text-decoration:none}
    .btn.alt{background:#fff;color:var(--dark);border:1px solid #eee}
    .small{font-size:13px;color:#78909C;margin-top:10px}

    .features{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .feat{background:#F1F8F6;padding:8px 12px;border-radius:10px;font-size:13px;color:var(--dark);font-weight:600}

    footer{margin:40px 0 80px;text-align:center;color:#607D8B}
  </style>
</head>
<body>

  <div class="topbar">
    <div class="brand">NgekosAja.id</div>
    <div>
      <a href="/NgekosAja.id/index.php" style="color:#fff;text-decoration:none;font-weight:600;margin-right:14px">Beranda</a>
      <?php if($is_logged_in): ?>
        <span style="opacity:.9">Halo, <?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User') ?></span>
        <a href="/NgekosAja.id/logout.php" style="color:#fff;margin-left:12px;text-decoration:none;font-weight:600">Logout</a>
      <?php else: ?>
        <a href="/NgekosAja.id/login.php" style="color:#fff;margin-left:12px;text-decoration:none;font-weight:600">Login</a>
        <a href="/NgekosAja.id/register.php" style="color:#fff;margin-left:12px;text-decoration:none;font-weight:600">Daftar</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="container">
    <a class="back" href="/NgekosAja.id/index.php">&larr; Kembali ke hasil pencarian</a>

    <div class="layout">
      <div>
        <div class="card">
          <div class="gallery" style="padding:0 0 18px 0">
            <div class="main">
              <img id="mainImg" src="https://picsum.photos/seed/kos<?= $kos['id'] ?>/1200/700" alt="Foto utama">
            </div>
            <div class="thumbs" style="padding:14px">
              <?php for($i=1;$i<=4;$i++): ?>
                <?php $seed = $kos['id'] . '-' . $i; ?>
                <img src="https://picsum.photos/seed/<?= $seed ?>/400/300" data-src="https://picsum.photos/seed/<?= $seed ?>/1200/700" class="<?= $i===1 ? 'active' : '' ?>" alt="thumb<?= $i ?>">
              <?php endfor; ?>
            </div>
          </div>

          <div class="info">
            <h1><?= htmlspecialchars($kos['name']) ?></h1>
            <div class="meta"><?= htmlspecialchars($kos['city']) ?> • <?= htmlspecialchars($kos['type']) ?></div>
            <div class="price">Rp <?= number_format($kos['price'],0,',','.') ?> / bulan</div>

            <div class="features">
              <!-- contoh fasilitas statis; kalau ada tabel fasilitas, ambil dari DB -->
              <div class="feat">WiFi</div>
              <div class="feat">Kamar mandi dalam</div>
              <div class="feat">Parkir</div>
            </div>

            <hr style="margin:16px 0">

            <div class="desc"><?= nl2br(htmlspecialchars($kos['description'] ?: 'Tidak ada deskripsi tambahan.')) ?></div>
          </div>
        </div>

        <div style="height:18px"></div>

        <div class="card kamar-list" style="padding:14px 18px">
          <h3 style="margin-top:6px;margin-bottom:8px;font-family:'Poppins',sans-serif">Daftar Kamar</h3>

          <?php if($kamars): ?>
            <?php foreach($kamars as $k): ?>
              <div class="kamar-item">
                <div>
                  <div style="font-weight:700"><?= htmlspecialchars($k['name']) ?></div>
                  <div class="small">Rp <?= number_format($k['price'],0,',','.') ?></div>
                </div>
                <div style="text-align:right">
                  <span class="badge <?= $k['status']=='kosong' ? 'kosong' : 'terisi' ?>"><?= htmlspecialchars($k['status']) ?></span>
                  <?php if($k['status']=='kosong'): ?>
                    <?php if($is_logged_in): ?>
                      <?php if($current_role === 'pencari'): ?>
                        <a href="/NgekosAja.id/kos/booking.php?kos_id=<?= $id ?>&kamar_id=<?= $k['id'] ?>" class="btn" style="margin-left:10px">Ajukan Sewa</a>
                      <?php else: ?>
                        <span class="small" style="display:block;margin-top:8px;color:#999">Hanya pencari dapat booking</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <a href="/NgekosAja.id/login.php" class="btn" style="margin-left:10px">Login untuk Sewa</a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="small" style="display:block;margin-top:6px;color:#999">Tidak tersedia</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="small">Belum ada kamar yang diinput pemilik.</div>
          <?php endif; ?>
        </div>
      </div>

      <aside class="aside">
        <div class="card contact">
          <h4>Info Pemilik</h4>
          <p><strong><?= htmlspecialchars($kos['owner_name']) ?></strong></p>
          <?php if(!empty($kos['owner_phone'])): ?>
            <p>Tel: <?= htmlspecialchars($kos['owner_phone']) ?></p>
          <?php endif; ?>
          <?php if(!empty($kos['owner_email'])): ?>
            <p>Email: <?= htmlspecialchars($kos['owner_email']) ?></p>
          <?php endif; ?>

          <div style="margin-top:12px">
            <?php if($is_logged_in): ?>
              <?php if($current_role === 'pencari'): ?>
                <a class="btn" href="/NgekosAja.id/kos/booking.php?kos_id=<?= $id ?>">Ajukan Sewa</a>
              <?php else: ?>
                <a class="btn alt" href="/NgekosAja.id/dashboard_owner.php">Kelola Kos</a>
              <?php endif; ?>
            <?php else: ?>
              <a class="btn" href="/NgekosAja.id/login.php">Login untuk Ajukan</a>
            <?php endif; ?>
          </div>

          <div class="small" style="margin-top:10px">Harga dapat berubah. Hubungi pemilik untuk negosiasi.</div>
        </div>

        <div style="height:14px"></div>

        <div class="card" style="padding:14px">
          <h4 style="margin:0 0 8px">Lokasi</h4>
          <?php
            // Simple map embed via Google Maps query (alamat mungkin kosong)
            $addr = rawurlencode($kos['address'] ?? $kos['city'] ?? '');
          ?>
          <?php if(!empty($addr)): ?>
            <iframe
              src="https://www.google.com/maps?q=<?= $addr ?>&output=embed"
              style="width:100%;height:200px;border:0;border-radius:10px"
              loading="lazy"></iframe>
          <?php else: ?>
            <div class="small">Alamat belum diisi pemilik.</div>
          <?php endif; ?>
        </div>

      </aside>
    </div>
  </div>

  <footer>
    &copy; <?= date('Y') ?> NgekosAja.id — Temukan kos impianmu 🌿
  </footer>

  <script>
    // gallery thumb click -> main image swap
    document.querySelectorAll('.thumbs img').forEach(function(img){
      img.addEventListener('click', function(){
        document.getElementById('mainImg').src = this.dataset.src;
        document.querySelectorAll('.thumbs img').forEach(i=>i.classList.remove('active'));
        this.classList.add('active');
      });
    });
  </script>
</body>
</html>
