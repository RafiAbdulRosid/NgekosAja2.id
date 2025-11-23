<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>NgekosAja.id</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/NgekosAja2.id/assets/css/style.css">

  <style>
    /* ====== NAVBAR CUSTOM (MENYESUAIKAN STYLE REFERENSI) ====== */

    header {
        background: #567c8d;
        padding: 15px 0;
        box-shadow: 0 3px 6px rgba(0,0,0,0.08);
    }

    .navbar-custom {
        max-width: 1200px;
        margin: auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* LOGO GAMBAR */
    .logo-img {
        max-height: 48px;
        width: auto;
        display: block;
    }

    /* Tombol login & daftar mengikuti style referensi */
    .btn-outline-custom {
        padding: 8px 18px;
        border-radius: 10px;
        border: 2px solid #ffffffe1;
        background: transparent;
        color: #ffffffc5;
        text-decoration: none;
        font-weight: 600;
        margin-left: 10px;
    }

    .btn-white-custom {
        padding: 8px 18px;
        border-radius: 10px;
        background: #fffffff5;
        color: #000;
        text-decoration: none;
        font-weight: 600;
        margin-left: 10px;
    }

    /* Untuk user login */
    .nav-hello {
        color: #fff;
        margin-right: 10px;
        font-weight: 600;
    }
  </style>
</head>

<body>

<header>
  <div class="navbar-custom">

      <!-- LOGO (gunakan gambar bila tersedia) -->
      <a href="<?= $baseUrl ?>index.php" class="logo">
    <img src="/NgekosAja2.id/assets/uploads/logo.png" alt="NgekosAja2.id" style="max-height: 48px">
</a>



      <div class="nav-links d-flex align-items-center">

      <?php if (!empty($_SESSION['user_id'])): ?>
          <span class="nav-hello">Halo, <b><?= htmlspecialchars($_SESSION['fullname']) ?></b></span>
          <a href="/NgekosAja2.id/logout.php" class="btn-outline-custom">Logout</a>

      <?php else: ?>
          <a href="/NgekosAja2.id/login.php" class="btn-outline-custom">Masuk</a>
          <a href="/NgekosAja2.id/register.php" class="btn-white-custom">Daftar</a>

      <?php endif; ?>

      </div>

  </div>
</header>