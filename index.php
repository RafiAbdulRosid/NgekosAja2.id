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
    $sqlBase .= " AND (k.name LIKE ? 
                   OR k.city LIKE ? 
                   OR k.address LIKE ? 
                   OR k.type LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
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
    --blue: #567C8D;
    --cream: #F5EFEB;
    --dark: #1E1E1E;
    --text: #4A4A4A;
}

/* RESET */
body {
    margin:0;
    font-family:'Nunito Sans', sans-serif;
    background:#F5EFEB;
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
    border:2px solid #ffffffc4;
    background:transparent;
    color: #ffffff;
}

.btn-white {
    padding:8px 20px;
    border-radius:10px;
    background: #ffffffc4;
    color:#000;
    font-weight:600;
}

/* HERO */
.hero {
    width:100%;
    height:380px;
    background:url('https://i.pinimg.com/1200x/29/d2/bf/29d2bf4a0f9428dc592824b687588244.jpg') center/cover no-repeat;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
}

.hero::after {
    content:'';
    position:absolute;
    inset:0;
    background:rgba(231, 231, 231, 0.46);
}

.hero-content {
    position:relative;
    z-index:3;
    text-align:center;
    color: #2e3a3fff;
    max-width:650px;
}

.hero-content h2 {
    font-size:42px;
    font-family:'Nunito Sans', sans-serif;
    line-height:1.2;
    margin-bottom:20px;
}

/* SEARCH */
.search-box {
    background: #ffffff;
    width:600px;
    padding:15px 20px;
    border-radius:12px;
    display:flex;
    align-items:center;
    margin:20px auto 0;}
    
.search-box input {
    flex:1;
    padding:8px;
    border:none;
    font-size:16px;
}
.search-box button {
    background: #567c8d;
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
    background: #bfbcb3ff;
    overflow:hidden;
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
    font-family:'Nunito Sans', sans-serif;
    font-size:20px;
    font-weight:700;
    color: #45475dff;
}
.card-meta {
    color: #1c1d27ff;
    font-size:14px;
}
.price {
    margin-top:8px;
    font-size:22px;
    font-weight:700;
    color: #262956ff;
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
    font-family:'Nunito Sans', sans-serif;
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
            <img src="<?= $baseUrl ?>assets/uploads/logo.png" alt="NgekosAja2.id">
        </a>
        <div class="nav-links">
        <?php if(!empty($_SESSION['user_id'])): ?> Halo, <b><?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']) ?></b>      
            <a href="<?= $baseUrl . (($_SESSION['role'] ?? '') === 'pemilik' ? 'dashboard_owner.php' : 'dashboard_user.php') ?>" class="btn-outline">Dashboard</a>  
            <a href="<?= $baseUrl ?>logout.php" class="btn-outline">Logout</a> <?php else: ?> <a href="<?= $baseUrl ?>login.php" class="btn-outline">Masuk</a> 
            <a href="<?= $baseUrl ?>register.php" class="btn-white" >Daftar</a> <?php endif; ?>
        </div>
    </div>
</header>

<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <h2>Temukan kost terbaik dekat kampusmu!</h2>

        <form method="get" class="search-box">
            <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari kos atau daerah...">
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
        border:5px ridge #cbc7b8ff;
        border-radius:10px;
        background:#f5efeb;
    ">
        <h3 style="color:#567C8D;margin-bottom:10px;">Kos Tidak Ditemukan</h3>
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

<!-- JAVASCRIPT: debounce search, ajax pagination, image fade-in, logout confirm -->
<script>
(function () {
  // Utility: get element safely
  const $ = (s, root = document) => root.querySelector(s);

      // if user submits via button, normal navigate (fallback)
    form.addEventListener('submit', function (ev) {
      // allow default submit for progressive enhancement
    });

    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(() => {
        const q = input.value.trim();
        const params = new URLSearchParams(window.location.search);
        if (q) params.set('q', q); else params.delete('q');
        params.set('page', '1'); // kembali ke halaman 1 saat cari baru
        // gunakan pushState agar tidak membuat back/forward aneh
        const newUrl = window.location.pathname + '?' + params.toString();
        // navigasi full page agar server masih mengembalikan HTML lengkap
        window.location.href = newUrl;
      }, delay);
    });
  })();

  // 2) AJAX pagination that fetches only kos-section partial when possible
  (function setupAjaxPagination() {
    const kosSection = document.getElementById('kosSection');
    if (!kosSection) return;

    // handle clicks inside kosSection (delegation)
    kosSection.addEventListener('click', async function (ev) {
      const a = ev.target.closest('a');
      if (!a) return;
      // only process links that look like pagination links
      if (!a.classList.contains('pag-link')) return;

      ev.preventDefault();
      const href = a.href;
      const url = new URL(href, window.location.origin);
      url.searchParams.set('ajax', '1'); // request partial when possible

      // set loading state
      const prevText = a.textContent;
      a.textContent = 'Memuat...';

      try {
        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network response not ok');

        const html = await res.text();

        // Try to find kos-section in returned HTML
        // 1. If server returns only partial (kos-section), insert directly
        // 2. Else extract <div id="kosSection">...</div> from full HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newSection = doc.getElementById('kosSection') || doc.querySelector('.kos-section') || null;

        if (newSection) {
          kosSection.innerHTML = newSection.innerHTML;
        } else {
          // fallback: if server returned partial without wrapper, try to guess .grid and pagination
          const grid = doc.querySelector('#gridList, .grid');
          const pag = doc.querySelector('#paginationBlock, .pagination, .pag-link');
          if (grid) {
            const existingGrid = kosSection.querySelector('#gridList, .grid');
            if (existingGrid) existingGrid.replaceWith(grid);
          }
          if (pag) {
            const existingPag = document.getElementById('paginationBlock') || document.querySelector('#paginationBlock');
            if (existingPag) existingPag.replaceWith(pag);
          }
        }

        // update URL in address bar
        window.history.pushState(null, '', href);
        // scroll user to top of results
        window.scrollTo({ top: kosSection.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });

        // rerun image observer for newly injected images
        runImageObserver();
      } catch (err) {
        console.error('AJAX pagination error', err);
        alert('Gagal memuat halaman. Silakan coba lagi.');
      } finally {
        a.textContent = prevText;
      }
    });

    // support back/forward to reload state
    window.addEventListener('popstate', function () {
      // simple approach: reload page so server decides what to show
      location.reload();
    });
  })();

  // 3) Image fade-in using IntersectionObserver
  let imageObserver = null;
  function runImageObserver() {
    const imgs = document.querySelectorAll('.card-kos img[loading="lazy"]');
    if (!('IntersectionObserver' in window)) {
      // fallback: show images immediately
      imgs.forEach(img => img.classList.add('visible'));
      return;
    }
    if (imageObserver) imageObserver.disconnect();

    imageObserver = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.classList.add('visible');
          obs.unobserve(img);
        }
      });
    }, { rootMargin: '50px 0px', threshold: 0.1 });

    imgs.forEach(img => {
      // if image already loaded, mark visible
      if (img.complete && img.naturalWidth !== 0) {
        img.classList.add('visible');
      } else {
        imageObserver.observe(img);
      }
    });
  }
  document.addEventListener('DOMContentLoaded', runImageObserver);

  // 4) Confirm logout
  (function setupLogoutConfirm() {
    const logout = document.getElementById('logoutLink');
    if (!logout) return;
    logout.addEventListener('click', function (ev) {
      if (!confirm('Yakin ingin logout?')) ev.preventDefault();
    });
  })();

})();
</script>

</body>
</html>
