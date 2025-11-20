<?php
// dashboard_user.php (versi lengkap dan siap pakai)
// Menampilkan dashboard untuk user role 'pencari'
// Pastikan db.php ada di root dan menyediakan $pdo

session_start();
require_once __DIR__ . '/db.php';

$baseUrl = '/NgekosAja2.id/'; // ubah menjadi '/' jika project langsung di htdocs

// Cek autentikasi & role
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pencari') {
    header('Location: ' . $baseUrl . 'login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Pencari';

// Ambil data user (termasuk profile_image)
$user_data = null;
try {
    $stmt_user = $pdo->prepare("SELECT id, username, fullname, role, profile_image, email FROM users WHERE id = ? LIMIT 1");
    $stmt_user->execute([$userId]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        // sinkronkan session (opsional)
        $_SESSION['fullname'] = $user_data['fullname'] ?? $_SESSION['fullname'];
        $_SESSION['profile_image'] = $user_data['profile_image'] ?? $_SESSION['profile_image'];
    }
} catch (PDOException $e) {
    // logging jika perlu; untuk sekarang biarkan silent
}

// helper: buat url gambar profil (cek file fisik)
function getProfileImageUrl($imageName, $fullname, $username, $baseUrl) {
    if (!empty($imageName)) {
        $filePath = __DIR__ . '/uploads/profiles/' . $imageName;
        if (is_file($filePath)) {
            return $baseUrl . 'uploads/profiles/' . $imageName;
        }
    }
    $name = urlencode($fullname ?? $username ?? 'User');
    return 'https://ui-avatars.com/api/?name=' . $name . '&background=00BFA6&color=fff&size=64';
}

$profileImageUrl = getProfileImageUrl(
    $user_data['profile_image'] ?? null,
    $user_data['fullname'] ?? $fullname,
    $user_data['username'] ?? $fullname,
    $baseUrl
);

// Ambil booking milik user (pencari) -- gunakan prepared statement
$bookings = [];
try {
    $sql = "
        SELECT b.*, k.name AS kos_name, k.city, k.type, km.name AS kamar_name, km.price
        FROM bookings b
        JOIN kos k ON b.kos_id = k.id
        JOIN kamar km ON b.kamar_id = km.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // jika error, kosongkan bookings
    $bookings = [];
}

// (opsional) tutup koneksi di sini jika ingin
// $pdo = null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Pencari - NgekosAja.id</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#00BFA6;
            --secondary:#FF8A65;
            --light:#FFFFFF;
            --bg-light:#F4F8F9;
            --dark:#263238;
            --text-muted:#607D8B;
            --success:#2ecc71;
        }
        *{box-sizing:border-box}
        body{font-family:'Nunito Sans',sans-serif;margin:0;background:var(--bg-light);color:var(--dark)}
        .wrap{max-width:1100px;margin:0 auto;padding:0 20px}

/* Header */
header{background:var(--light);box-shadow:0 2px 6px rgba(0,0,0,0.05);padding:15px 0;margin-bottom:30px;}
.navbar{display:flex;justify-content:space-between;align-items:center;}
.logo{font-family:'Poppins',sans-serif;font-weight:700;font-size:22px;color:var(--primary);text-decoration:none}

/* Buttons */
.btn{padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:600;cursor:pointer;transition:all .2s;border:none;display:inline-block;}
.btn-primary{background:var(--secondary);color:#fff;}
.btn-primary:hover{background:#FF7043;transform:translateY(-2px);}
.btn-outline{background:transparent;border:1px solid #ddd;color:var(--text-muted);}
.btn-outline:hover{border-color:var(--dark);color:var(--dark);}

/* profile dropdown */
.profile-menu-container { position: relative; display: inline-block; margin-left: 15px; }
.profile-icon { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; cursor: pointer; border: 2px solid var(--primary); transition: border-color .2s; }
.profile-icon:hover { border-color: var(--secondary); }
.dropdown-content { display: none; position: absolute; right: 0; background-color: var(--light); min-width: 200px; box-shadow: 0 8px 16px rgba(0,0,0,0.12); z-index: 1000; border-radius: 8px; overflow: hidden; margin-top:10px; }
.dropdown-content a { color: var(--dark); padding: 12px 14px; text-decoration:none; display:block; font-weight:600; }
.dropdown-content a:hover { background: #f6f8f9; }
.info-display { padding: 10px 14px; border-bottom:1px solid #eee; background:#fafafa; font-size:14px; }

/* Cards */
.card{background:var(--light);border-radius:15px;padding:25px;box-shadow:0 4px 15px rgba(0,0,0,0.05);margin-bottom:20px;}
.card h3{margin-top:0;font-family:'Poppins',sans-serif;color:var(--dark);}

/* Welcome Box */
.welcome-box{
    background: linear-gradient(135deg, var(--primary), #009688);
    color: #fff; border-radius: 15px; padding: 30px; margin-bottom: 30px;
    display: flex; justify-content: space-between; align-items: center;
    box-shadow: 0 10px 20px rgba(0, 191, 166, 0.12);
}

/* Empty State */
.empty-state{text-align:center;padding:40px 20px;}
.empty-state img{width:100px;margin-bottom:20px;opacity:0.8;}
.empty-state p{color:var(--text-muted);margin-bottom:20px;}

/* LIST BOOKING */
.booking-item { display:flex; justify-content:space-between; align-items:center; padding:18px; border:1px solid #f1f1f1; border-radius:12px; margin-bottom:14px; background:#fff; transition: transform .15s; }
.booking-item:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(0,0,0,0.05); border-color: #e6f5ef; }
.b-info h4 { margin:0 0 6px; font-family: 'Poppins', sans-serif; font-size:18px; }
.b-meta { color:var(--text-muted); font-size:14px; }
.b-date { margin-top:8px; font-weight:600; color:var(--dark); font-size:14px; }

/* Badges */
.badge { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; }
.badge.pending { background: #FFF3E0; color: #F57C00; border:1px solid #FFE0B2; }
.badge.approved { background: #E8F5E9; color: #2E7D32; border:1px solid #C8E6C9; }
.badge.rejected { background: #FFEBEE; color: #C62828; border:1px solid #FFCDD2; }

@media (max-width:700px){
    .booking-item{flex-direction:column;align-items:flex-start}
    .navbar{flex-direction:column;gap:12px}
}
    </style>
</head>
<body>

<header>
    <div class="wrap navbar">
        <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="logo">NgekosAja.id</a>

        <div style="display:flex;align-items:center;gap:12px;">
            <!-- Form pencarian: submit ke index.php -->
            <form action="<?= htmlspecialchars($baseUrl) ?>index.php" method="get" style="display:flex; align-items:center; gap:8px;">
                <input name="q" type="search" placeholder="Cari kos atau lokasi..." aria-label="Cari kos"
                    style="padding:8px 12px;border-radius:999px;border:1px solid #eee;min-width:220px;font-weight:600">
                <button type="submit" class="btn btn-primary">🔍 Cari</button>
            </form>

            <!-- Beranda cepat -->
            <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="btn btn-outline">Beranda</a>

            <!-- profil dropdown -->
            <?php if ($user_data): ?>
                <div class="profile-menu-container">
                    <img src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profil" class="profile-icon" id="profileIcon"
                         onclick="toggleProfileDropdown()">
                    <div id="profileDropdown" class="dropdown-content" aria-hidden="true">
                        <div class="info-display">
                            <?= htmlspecialchars($user_data['fullname'] ?? $fullname) ?><br>
                            <small style="color:var(--text-muted)">@<?= htmlspecialchars($user_data['username'] ?? '') ?></small>
                        </div>
                        <a href="<?= htmlspecialchars($baseUrl) ?>profile.php">Pengaturan Akun</a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>dashboard_user.php">Dashboard Saya</a>
                        <a href="<?= htmlspecialchars($baseUrl) ?>logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= htmlspecialchars($baseUrl) ?>login.php" class="btn btn-outline">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="wrap">
    <div class="welcome-box">
        <div class="welcome-text">
            <h2>Selamat Datang, <?= htmlspecialchars(explode(' ', $fullname)[0]) ?>! 👋</h2>
            <p style="margin:6px 0 0">Pantau status pengajuan sewa kosmu di sini.</p>
        </div>
        <div>
            <a href="<?= htmlspecialchars($baseUrl) ?>index.php?focus=search" class="btn btn-primary" style="background:#FF8A65;">🔎 Cari Kos</a>
        </div>
    </div>

    <div class="card">
        <h3>🏠 Status Pengajuan Sewa</h3>

        <?php if (count($bookings) > 0): ?>
            <div style="margin-top:18px;">
                <?php foreach ($bookings as $b): ?>
                    <div class="booking-item">
                        <div class="b-info">
                            <h4><?= htmlspecialchars($b['kos_name']) ?></h4>
                            <div class="b-meta">
                                <?= htmlspecialchars($b['kamar_name']) ?> • <?= htmlspecialchars($b['city']) ?> • Rp <?= number_format($b['price'],0,',','.') ?>/bln
                            </div>
                            <div class="b-date">📅 Mulai: <?= htmlspecialchars(date('d M Y', strtotime($b['start_date'] ?? $b['created_at'] ?? 'now'))) ?></div>
                        </div>

                        <div style="text-align:right;min-width:140px">
                            <?php
                                $statusClass = 'pending';
                                $statusText = 'Menunggu Konfirmasi';
                                if (($b['status'] ?? '') === 'approved') {
                                    $statusClass = 'approved';
                                    $statusText = '✅ Disetujui';
                                } elseif (($b['status'] ?? '') === 'rejected') {
                                    $statusClass = 'rejected';
                                    $statusText = '❌ Ditolak';
                                }
                            ?>
                            <div><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></div>

                            <?php if (($b['status'] ?? '') === 'approved'): ?>
                                <div style="margin-top:8px;color:var(--success);font-size:13px;">Silakan hubungi pemilik untuk pembayaran.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:28px;">
                <div style="font-size:48px;margin-bottom:12px">📂</div>
                <h4 style="margin:6px 0 8px">Belum ada pengajuan sewa</h4>
                <p style="color:var(--text-muted);max-width:640px;margin:0 auto 18px;">Kamu belum mengajukan sewa di kos manapun. Yuk mulai cari kos yang cocok untukmu.</p>
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="btn btn-primary">Mulai Mencari</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div style="text-align:center;padding:30px;color:var(--text-muted);font-size:14px;">
    &copy; <?= date('Y') ?> NgekosAja.id — Dashboard Pencari
</div>

<script>
    function toggleProfileDropdown() {
        const d = document.getElementById('profileDropdown');
        if (!d) return;
        d.style.display = (d.style.display === 'block') ? 'none' : 'block';
        d.setAttribute('aria-hidden', d.style.display !== 'block');
    }

    // tutup dropdown jika klik di luar
    window.addEventListener('click', function(e){
        const icon = document.getElementById('profileIcon');
        const dropdown = document.getElementById('profileDropdown');
        if (!dropdown) return;
        if (icon && icon.contains(e.target)) return;
        if (dropdown.contains(e.target)) return;
        dropdown.style.display = 'none';
        dropdown.setAttribute('aria-hidden', 'true');
    });
</script>

</body>
</html>
