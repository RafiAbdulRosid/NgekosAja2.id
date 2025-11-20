<?php
session_start();

require_once __DIR__ . '/db.php';

$baseUrl = '/NgekosAja2.id/';

// parameter search & paging
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

// base query (tetap sama)
$sqlBase = "
    FROM kos k
    JOIN users u ON u.id = k.owner_id
    WHERE 1=1
";

// search
$params = [];
if ($q !== '') {
    $sqlBase .= " AND (k.name LIKE ? OR k.city LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

// count
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt " . $sqlBase);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

// fetch kos data
$sqlFetch = "
    SELECT k.id, k.name, k.city, k.price, k.type, u.fullname AS owner_name,
      (SELECT filename FROM kos_images WHERE kos_images.kos_id = k.id ORDER BY id ASC LIMIT 1) AS thumb
    $sqlBase
    ORDER BY k.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sqlFetch);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>NgekosAja.id — Cari Kos Terbaik</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

<style>
:root {
    --blue: #71B6D5;
    --cream: #FAF7F4;
    --dark: #1E1E1E;
    --text: #4A4A4A;
}

/* RESET */
body {
    margin:0;
    font-family:'Nunito Sans', sans-serif;
    background:#FFFFFF;
    color:var(--dark);
}

/* NAVBAR */
header {
    background: var(--blue);
    padding: 15px 0;
    box-shadow: 0 3px 6px rgba(0,0,0,0.08);
}
.navbar {
    max-width: 1200px;
    margin:auto;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.logo img {
    max-height:50px;
}
.nav-links a {
    font-weight:600;
    text-decoration:none;
    margin-left:15px;
}

.btn-outline {
    padding:8px 20px;
    border-radius:10px;
    border:2px solid #fff;
    background:transparent;
    color:#fff;
}

.btn-white {
    padding:8px 20px;
    border-radius:10px;
    background:#fff;
    color:#000;
    font-weight:600;
}

/* HERO */
.hero {
    width:100%;
    height:380px;
    background:url('https://www.gordenjogja.co.id/wp-content/uploads/2021/12/desain-kamar-kost-2.jpg') center/cover no-repeat;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
}

.hero::after {
    content:'';
    position:absolute;
    inset:0;
    background:rgba(0, 0, 0, 0.16);
}

.hero-content {
    position:relative;
    z-index:3;
    text-align:center;
    color:#fff;
    max-width:650px;
}

.hero-content h2 {
    font-size:42px;
    font-weight:700;
    font-family:'Poppins';
    line-height:1.2;
    margin-bottom:20px;
}

/* SEARCH */
.search-box {
    background:#fff;
    width:600px;
    padding:15px 20px;
    border-radius:12px;
    display:flex;
    align-items:center;
    margin:20px auto 0;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.search-box input {
    flex:1;
    padding:8px;
    border:none;
    font-size:16px;
}
.search-box button {
    background:#4aa3c7;
    border:none;
    padding:10px 20px;
    border-radius:10px;
    color:#fff;
    font-weight:600;
}

/* GRID */
.grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));
    gap:30px;
}
.card-kos {
    border-radius:16px;
    background:#fff;
    overflow:hidden;
    border:1px solid #eee;
    box-shadow:0 6px 15px rgba(0,0,0,0.08);
    transition:0.3s;
}
.card-kos:hover {
    transform:translateY(-6px);
    box-shadow:0 20px 40px rgba(0,0,0,0.12);
}
.card-kos img {
    width:100%;
    height:200px;
    object-fit:cover;
}
.card-body {
    padding:18px;
}
.card-title {
    font-family:'Poppins';
    font-size:20px;
    font-weight:700;
}
.card-meta {
    color:#666;
    font-size:14px;
}
.price {
    margin-top:8px;
    font-size:22px;
    font-weight:700;
    color:#2E7D32;
}

/* FOOTER */
footer {
    background:var(--blue);
    text-align:center;
    color:#fff;
    padding:30px 0;
    margin-top:50px;
}
.footer-logo {
    font-size:22px;
    font-family:'Poppins';
    font-weight:700;
    margin-bottom:5px;
}
.footer-sub {
    margin-bottom:10px;
    opacity:0.9;
}
</style>
</head>

<body>

<!-- HEADER BARU -->
<header>
    <div class="navbar">
        <a href="<?= $baseUrl ?>index.php" class="logo">
            <img src="<?= $baseUrl ?>assets/uploads/logo.png" alt="NgekosAja.id">
        </a>
        <div class="nav-links">
        <?php if(!empty($_SESSION['user_id'])): ?> Halo, <b><?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']) ?></b>      
            <a href="<?= $baseUrl . (($_SESSION['role'] ?? '') === 'pemilik' ? 'dashboard_owner.php' : 'dashboard_user.php') ?>" class="btn-outline">Dashboard</a>  
            <a href="<?= $baseUrl ?>logout.php" class="btn-outline">Logout</a> <?php else: ?> <a href="<?= $baseUrl ?>login.php" class="btn-outline">Login</a> 
            <a href="<?= $baseUrl ?>register.php" class="btn-white" >Daftar</a> <?php endif; ?>
        </div>
    </div>
</header>

<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <h2>Temukan kost terbaik dekat kampusmu!</h2>

        <form method="get" class="search-box">
            <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari kos atau kota...">
            <button type="submit">Cari</button>
        </form>
    </div>
</div>

<!-- LIST KOS -->
<div class="kos-section" style="max-width:1200px;margin:50px auto;">

<?php if (empty($rows)): ?>

    <div class="card-kos" style="
        padding:40px;
        text-align:center;
        box-shadow:none;
        border:2px dashed #ccc;
        border-radius:16px;
        background:#fff;
    ">
        <h3 style="color:#4aa3c7;margin-bottom:10px;">Kos Tidak Ditemukan</h3>
        <p style="font-size:16px;color:#555;">
            Coba kata kunci lain atau 
            <a href="<?= $baseUrl ?>index.php" style="color:#4aa3c7;font-weight:600;text-decoration:none;">
                Lihat Semua Kos
            </a>.
        </p>
    </div>

<?php else: ?>

    <div class="grid">
        <?php foreach($rows as $r):

            $thumb = !empty($r['thumb'])
                ? $baseUrl . ltrim($r['thumb'],'/')
                : "https://picsum.photos/seed/kos{$r['id']}/400/300";
        ?>
        <div class="card-kos">
            <a href="<?= $baseUrl ?>kos/detail.php?id=<?= (int)$r['id'] ?>" style="text-decoration:none;color:inherit;">
                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($r['name']) ?></div>

                    <div class="card-meta">
                        <?= htmlspecialchars($r['city']) ?> • Tipe: <?= htmlspecialchars($r['type']) ?>
                        <div style="font-size:13px;margin-top:4px;color:#777;">
                            Oleh: <?= htmlspecialchars($r['owner_name'] ?? 'Anonim') ?>
                        </div>
                    </div>

                    <div class="price">
                        Rp <?= number_format($r['price'],0,',','.') ?>
                        <span style="font-size:14px;font-weight:400;color:#666;">/ bulan</span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<!-- PAGINATION -->
<?php if ($pages > 1): ?>
    <div style="text-align:center;margin-top:30px;display:flex;justify-content:center;gap:20px;align-items:center;">

        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(['q'=>$q,'page'=>$page-1]) ?>"
               style="padding:10px 18px;border-radius:10px;background:#4aa3c7;color:#fff;text-decoration:none;">
               &larr; Sebelumnya
            </a>
        <?php endif; ?>

        <div style="font-weight:600;color:#333;">
            Halaman <?= $page ?> dari <?= $pages ?>
        </div>

        <?php if ($page < $pages): ?>
            <a href="?<?= http_build_query(['q'=>$q,'page'=>$page+1]) ?>"
               style="padding:10px 18px;border-radius:10px;background:#4aa3c7;color:#fff;text-decoration:none;">
               Selanjutnya &rarr;
            </a>
        <?php endif; ?>

    </div>
<?php endif; ?>

</div>

<!-- FOOTER BARU -->
<footer>
    <div class="footer-wrap">
        <div class="footer-logo">NgekosAja.id</div>
        <div class="footer-sub">the easiest way to find your place</div>
    </div>
    © <?= date('Y') ?> NgekosAja.id — All rights reserved.
     <div style="margin-top:10px;font-size:13px;">
        <a href="#" style="color:white;text-decoration:none;margin:0 8px;">Kebijakan Privasi</a> |
        <a href="#" style="color:white;text-decoration:none;margin:0 8px;">Kontak Kami</a>
    </div>
</footer>

</body>
</html>