<?php
// profile.php - Halaman Pengaturan Akun dan Foto Profil (versi diperbaiki)
session_start();
require_once __DIR__ . '/db.php';

$baseUrl = '/NgekosAja.id/';

// 1. Cek Autentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: {$baseUrl}login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';

// Lokasi penyimpanan foto profil (pastikan folder ini bisa ditulis oleh webserver)
$uploadDir = __DIR__ . '/uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 2. LOGIKA UPDATE FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];

        // Validasi ukuran (maks 1MB) dan mime type
        $maxBytes = 1 * 1024 * 1024;
        $finfoType = @mime_content_type($file['tmp_name']) ?: $file['type'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($finfoType, $allowedTypes, true)) {
            $errorMessage = "Hanya file JPG, PNG, GIF atau WEBP yang diizinkan.";
        } elseif ($file['size'] > $maxBytes) {
            $errorMessage = "Ukuran file terlalu besar. Maksimal 1MB.";
        } else {
            // buat nama file aman
            $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileExt = $fileExt ? strtolower($fileExt) : 'jpg';
            $newFileName = 'profile_' . $userId . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $fileExt);
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                try {
                    // Hapus foto lama jika ada (cek di DB)
                    $stmtOld = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
                    $stmtOld->execute([$userId]);
                    $old = $stmtOld->fetchColumn();
                    if ($old) {
                        $oldPath = $uploadDir . $old;
                        if (is_file($oldPath)) @unlink($oldPath);
                    }

                    // Update database
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$newFileName, $userId]);

                    // Update session (opsional)
                    $_SESSION['profile_image'] = $newFileName;
                    $successMessage = "Foto profil berhasil diubah!";
                } catch (PDOException $e) {
                    $errorMessage = "Gagal menyimpan ke database: " . $e->getMessage();
                    // Hapus file yang baru diupload jika gagal di DB
                    if (is_file($targetPath)) @unlink($targetPath);
                }
            } else {
                $errorMessage = "Gagal memindahkan file yang diunggah.";
            }
        }
    } else if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
         $errorMessage = "Terjadi kesalahan saat mengunggah file. Kode error: " . (int)$_FILES['profile_photo']['error'];
    }
}

// 3. AMBIL DATA USER TERBARU (termasuk foto profil)
$stmt = $pdo->prepare("SELECT id, username, fullname, role, profile_image FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Seharusnya tidak terjadi jika sudah lolos cek login
    session_destroy();
    header("Location: {$baseUrl}login.php");
    exit;
}

// Sinkronisasi session dengan data terbaru (opsional)
$_SESSION['profile_image'] = $user['profile_image'] ?? null;

// Fungsi helper untuk mendapatkan URL foto profil (minta $user sebagai parameter untuk menghindari undefined)
function getProfileImageUrl($imageName, $baseUrl, $user) {
    if (!empty($imageName)) {
        // periksa apakah file fisik ada, jika tidak, fallback ke avatar generator
        $filePath = __DIR__ . '/uploads/profiles/' . $imageName;
        if (is_file($filePath)) {
            return $baseUrl . 'uploads/profiles/' . $imageName;
        }
    }
    // Placeholder default menggunakan fullname/username
    $name = $user['fullname'] ?? $user['username'] ?? 'User';
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=00BFA6&color=fff';
}

$profileImageUrl = getProfileImageUrl($user['profile_image'] ?? '', $baseUrl, $user);

// optional: close pdo connection if you want
// $pdo = null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pengaturan Akun - NgekosAja.id</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary:#00BFA6;
            --secondary:#FF8A65;
            --light:#FFFFFF;
            --bg-light:#F4F8F9;
            --dark:#263238;
            --text-muted:#607D8B;
        }
        *{box-sizing:border-box}
        body{font-family:'Nunito Sans',sans-serif;margin:0;background:var(--bg-light);color:var(--dark)}
        .wrap{max-width:900px;margin:0 auto;padding:20px}
        header{background:var(--light);box-shadow:0 2px 4px rgba(0,0,0,0.05);position:sticky;top:0;z-index:100}
        .navbar{display:flex;justify-content:space-between;align-items:center;padding:12px 0}
        .logo{font-family:'Poppins',sans-serif;font-weight:700;font-size:24px;color:var(--primary);text-decoration:none}
        
        .profile-container {
            background: var(--light);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        .profile-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .profile-photo-area {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 0 0 5px rgba(0, 191, 166, 0.2);
        }
        .photo-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--secondary);
            color: var(--light);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid var(--light);
            font-size: 18px;
            line-height: 1;
        }
        .photo-upload-btn:hover {
            background: #FF7043;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color .3s;
        }
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }
        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            text-align: center;
            border: none;
            font-size: 16px;
        }
        .btn-primary{background:var(--secondary);color:#fff;}
        .btn-primary:hover{background:#FF7043;box-shadow:0 4px 10px rgba(255, 138, 101, 0.4);}

        /* Notifikasi */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        .alert-success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        .alert-error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        
        /* Modal Style Sederhana untuk Upload Foto */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: var(--light);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: var(--text-muted);
            cursor: pointer;
        }
        .file-input-wrapper {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .file-input {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header>
        <div class="wrap navbar">
            <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="logo">NgekosAja.id</a>
            <div class="nav-links">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="btn-link">Beranda</a> |
                <a href="<?= htmlspecialchars($baseUrl) . (($_SESSION['role'] ?? '') === 'pemilik' ? 'dashboard_owner.php' : 'dashboard_user.php') ?>" class="btn-link">Dashboard</a> |
                <a href="<?= htmlspecialchars($baseUrl) ?>logout.php" class="btn-link">Logout</a>
            </div>
        </div>
    </header>

    <div class="wrap">
        <h1 style="font-family:'Poppins',sans-serif;margin-top: 20px;">Pengaturan Akun</h1>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-photo-area">
                    <img id="current-profile-photo" src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Foto Profil" class="profile-photo">
                    <span class="photo-upload-btn" onclick="document.getElementById('photoModal').style.display='flex'">&#9998;</span>
                </div>
                <h3 style="margin-bottom: 5px;"><?= htmlspecialchars($user['fullname'] ?? '') ?></h3>
                <p style="color: var(--text-muted); margin-top: 0; font-size: 15px;">@<?= htmlspecialchars($user['username'] ?? '') ?> (<?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?>)</p>
            </div>

            <h4 style="font-family:'Poppins',sans-serif;border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:20px;">Informasi Dasar</h4>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal untuk Upload Foto -->
    <div id="photoModal" class="modal" role="dialog" aria-hidden="true">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('photoModal').style.display='none'">&times;</span>
            <h4 style="font-family:'Poppins',sans-serif;text-align:center;margin-top:0;">Unggah Foto Profil Baru</h4>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_photo">
                <div class="file-input-wrapper">
                    Pilih file gambar (maks 1MB)
                    <input type="file" name="profile_photo" class="file-input" accept="image/jpeg,image/png,image/gif,image/webp" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Unggah & Simpan</button>
            </form>
        </div>
    </div>

<script>
  // tutup modal saat klik di luar konten
  document.getElementById('photoModal').addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
  });
</script>

</body>
</html>
