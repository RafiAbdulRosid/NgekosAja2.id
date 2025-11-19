
<?php

// index.php - tampilan lebih ceria

session_start();

require_once __DIR__ . '/db.php';



$baseUrl = '/NgekosAja.id/'; // sesuaikan jika project di root '/'



// parameter search & paging sederhana

$q = trim($_GET['q'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));

$perPage = 9;

$offset = ($page - 1) * $perPage;



// dasar query: hanya kos aktif dan owner terdaftar

$sqlBase = "

    FROM kos k

    JOIN users u ON u.id = k.owner_id

    WHERE 1=1

";

// Optional: Tambah filter status jika kolom 'status' ada di tabel 'kos'

// $sqlBase .= " AND k.status = 'aktif'";



// tambah filter pencarian (nama / city)

$params = [];

if ($q !== '') {

    $sqlBase .= " AND (k.name LIKE ? OR k.city LIKE ?)";

    $params[] = "%$q%";

    $params[] = "%$q%";

}



// hitung total

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt " . $sqlBase);

$stmt->execute($params);

$total = (int)$stmt->fetchColumn();

$pages = max(1, ceil($total / $perPage));



// ambil data kos beserta satu thumbnail (subquery)

$sqlFetch = "

    SELECT k.id, k.name, k.city, k.price, k.type, u.fullname AS owner_name,

      (SELECT filename FROM kos_images WHERE kos_images.kos_id = k.id ORDER BY id ASC LIMIT 1) AS thumb

    " . $sqlBase . "

    ORDER BY k.created_at DESC

    LIMIT $perPage OFFSET $offset

";

$stmt = $pdo->prepare($sqlFetch);

$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);



$pdo = null; // Tutup koneksi

?>

<!doctype html>

<html lang="id">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>NgekosAja.id — Cari Kos Terbaik</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

    <style>

        /* CSS Variabel & Reset */

        :root{

            --primary:#00BFA6; /* Hijau Toska */

            --secondary:#FF8A65; /* Oranye */

            --light:#FFFFFF; /* Putih Cerah */

            --bg-light:#F4F8F9; /* Background Super Light Blue */

            --dark:#263238;

            --text-muted:#607D8B;

        }

        *{box-sizing:border-box}

        body{font-family:'Nunito Sans',sans-serif;margin:0;background:var(--bg-light);color:var(--dark)}

       

        /* Layout */

        .wrap{max-width:1200px;margin:0 auto;padding:0 20px}

        header{background:var(--light);box-shadow:0 2px 4px rgba(0,0,0,0.05);position:sticky;top:0;z-index:100}

        .navbar{display:flex;justify-content:space-between;align-items:center;padding:12px 0}

        .logo{font-family:'Poppins',sans-serif;font-weight:700;font-size:24px;color:var(--primary);text-decoration:none}

       

        /* Hero Section */

        .hero{

            background: linear-gradient(135deg, #00BFA6, #33C7B5);

            padding:60px 50px;

            border-radius:20px;

            color:var(--light);

            text-align:center;

            margin-top:25px;

            box-shadow: 0 15px 30px rgba(0, 191, 166, 0.2);

        }

        .hero h2{font-family:'Poppins',sans-serif;margin:0 0 10px;font-size:36px;text-shadow: 1px 1px 2px rgba(0,0,0,0.1);}

        .hero p{margin:0 0 25px;font-size:18px;opacity:.95}



        /* Search Form */

        .search-form{display:flex;justify-content:center;gap:12px;max-width:700px;margin:0 auto}

        .search-input{

            flex:1;

            padding:14px 20px;

            border-radius:12px;

            border:none;

            font-size:16px;

            box-shadow:0 6px 15px rgba(0,0,0,0.1);

            transition: box-shadow .3s;

        }

        .search-input:focus {

            box-shadow: 0 0 0 3px var(--secondary);

            outline: none;

        }

       

        /* Buttons */

        .btn{padding:14px 25px;border-radius:12px;text-decoration:none;font-weight:700;cursor:pointer;transition:all .2s;text-align:center;}

        .btn-primary{background:var(--secondary);color:#fff;border:none}

        .btn-primary:hover{background:#FF7043;box-shadow:0 4px 10px rgba(255, 138, 101, 0.4);}

        .btn-link{color:var(--primary);text-decoration:none;font-weight:600}

        .btn-link:hover{text-decoration:underline}

       

        /* Kos Grid & Card */

        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:25px;margin-top:35px}

        .card-kos{

            background:var(--light);

            border-radius:15px;

            overflow:hidden;

            box-shadow:0 8px 25px rgba(0,0,0,0.08);

            transition:transform .3s, box-shadow .3s;

            border: 1px solid #eee;

        }

        .card-kos:hover{transform:translateY(-7px);box-shadow:0 18px 40px rgba(0,0,0,0.15);}

        .card-kos a{text-decoration:none;color:inherit}

        .card-kos img{width:100%;height:200px;object-fit:cover;transition:transform .5s}

        .card-kos:hover img{transform:scale(1.05)}

        .card-body{padding:18px}

        .card-title{font-family:'Poppins',sans-serif;font-weight:700;font-size:19px;margin-bottom:4px;color:var(--dark)}

        .card-meta{color:var(--text-muted);font-size:14px;margin-bottom:8px}

        .card-owner{color:var(--primary);font-size:12px;margin-top:4px;font-weight:600}

        .price{font-weight:700;font-size:24px;color:#388E3C;margin-top:8px}

       

        /* Paging */

        .pager{margin:40px 0;display:flex;gap:15px;justify-content:center}

        .pager .btn-page{

            padding:10px 18px;

            border-radius:10px;

            border:1px solid var(--primary);

            color:var(--primary);

            text-decoration:none;

            font-weight:600;

            transition:background-color .2s;

        }

        .pager .btn-page:hover{background:var(--primary);color:#fff}

        .pager .current{align-self:center;color:var(--text-muted);font-weight:600}



        /* FOOTER STYLE */

        footer{

            background:var(--light);

            padding:30px 0;

            margin-top:50px;

            border-top:1px solid #eee;

            color:var(--text-muted);

            text-align:center;

            font-size:14px;

        }

        footer .logo{font-size:20px;}

    </style>

</head>

<body>

    <header>

        <div class="wrap navbar">

            <a href="<?= $baseUrl ?>index.php" class="logo">NgekosAja.id</a>

            <div class="nav-links">

                <?php if(!empty($_SESSION['user_id'])): ?>

                    Halo, <b><?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']) ?></b> |

                    <a href="<?= $baseUrl . (($_SESSION['role'] ?? '') === 'pemilik' ? 'dashboard_owner.php' : 'dashboard_user.php') ?>" class="btn-link">Dashboard</a> |

                    <a href="<?= $baseUrl ?>logout.php" class="btn-link">Logout</a>

                <?php else: ?>

                    <a href="<?= $baseUrl ?>login.php" class="btn-link">Login</a>

                    <a href="<?= $baseUrl ?>register.php" class="btn-primary btn" style="margin-left:15px; padding: 10px 18px;">Daftar</a>

                <?php endif; ?>

            </div>

        </div>

    </header>



    <div class="wrap">

        <section class="hero">

            <h2>🏠 Temukan Hunian Idealmu dengan Mudah</h2>

            <p>Jelajahi ribuan pilihan kos tepercaya yang ditambahkan langsung oleh pemilik.</p>



            <form method="get" class="search-form">

                <input class="search-input" name="q" placeholder="Cari nama kos atau kota (e.g. 'Putra' atau 'Semarang')..." value="<?= htmlspecialchars($q) ?>">

                <button class="btn btn-primary" type="submit">Cari</button>

            </form>

        </section>



        <main style="margin-top:45px">

            <h3 style="font-family:'Poppins',sans-serif;margin-bottom:15px;color:var(--dark);">

                Daftar Kos Tersedia

            </h3>

           

            <?php if (empty($rows)): ?>

              <div class="card-kos" style="padding:40px;text-align:center;box-shadow:none;border:2px dashed #ccc;">

                <h4 style="color:var(--secondary)">😔 Kos Tidak Ditemukan</h4>

                <p style="font-size:16px;">Coba kata kunci lain atau <a href="<?= $baseUrl ?>index.php" class="btn-link">Lihat Semua Kos</a>.</p>

              </div>

            <?php else: ?>

                <div class="grid">

                    <?php foreach($rows as $r):

                        // tentukan URL thumbnail

                        if (!empty($r['thumb'])) {

                            $thumb = $baseUrl . ltrim($r['thumb'],'/');

                        } else {

                            $thumb = "https://picsum.photos/seed/kos{$r['id']}/400/300";

                        }

                    ?>

                      <div class="card-kos">

                        <a href="<?= $baseUrl ?>kos/detail.php?id=<?= (int)$r['id'] ?>">

                          <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($r['name']) ?>">

                          <div class="card-body">

                            <h4 class="card-title"><?= htmlspecialchars($r['name']) ?></h4>

                            <div class="card-meta">

                                <?= htmlspecialchars($r['city']) ?> • Tipe: <?= htmlspecialchars($r['type']) ?>

                                <div class="card-owner">Oleh: <?= htmlspecialchars($r['owner_name'] ?? 'Anonim') ?></div>

                            </div>

                            <div class="price">Rp <?= number_format($r['price'],0,',','.') ?> <span style="font-size:16px;font-weight:400;color:var(--text-muted)">/ bulan</span></div>

                          </div>

                        </a>

                      </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>



            <?php if ($pages > 1): ?>

                <div class="pager" aria-label="pagination">

                    <?php if ($page>1): ?>

                        <a class="btn-page" href="?<?= http_build_query(['q'=>$q,'page'=>$page-1]) ?>">&larr; Sebelumnya</a>

                    <?php endif; ?>

                    <div class="current">Halaman <?= $page ?> dari <?= $pages ?></div>

                    <?php if ($page < $pages): ?>

                        <a class="btn-page" href="?<?= http_build_query(['q'=>$q,'page'=>$page+1]) ?>">Selanjutnya &rarr;</a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </main>

    </div>

   

    <footer>

        <div class="wrap">

            <div style="font-weight:700;margin-bottom:5px;">

                <a href="<?= $baseUrl ?>index.php" class="logo">NgekosAja.id</a>

            </div>

            <p style="margin:5px 0 0;font-size:15px;">Temukan kos terbaikmu di sini. Cepat, Mudah, dan Langsung dari Pemilik.</p>

            <p style="margin-top:10px;font-size:13px;">&copy; <?= date('Y') ?> NgekosAja.id. All rights reserved.</p>

            <div style="margin-top:10px;font-size:13px;">

                <a href="#" class="btn-link" style="margin:0 8px;">Kebijakan Privasi</a> |

                <a href="#" class="btn-link" style="margin:0 8px;">Kontak Kami</a>

            </div>

        </div>

    </footer>

    </body>

</html>