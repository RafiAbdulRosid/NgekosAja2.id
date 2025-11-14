<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NgekosAja.id - Temukan Kos Impianmu 🌿</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito+Sans:wght@400;500&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #00BFA6;
      --secondary: #FF8A65;
      --accent: #FFD54F;
      --light: #FAFAFA;
      --dark: #263238;
      --highlight: #A5D6A7;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Nunito Sans', sans-serif;
      background-color: var(--light);
      color: var(--dark);
      overflow-x: hidden;
    }

    header {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 60px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    header h1 {
      font-family: 'Poppins', sans-serif;
      font-size: 26px;
      font-weight: 700;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin-left: 25px;
      font-weight: 600;
      transition: 0.3s;
    }

    nav a:hover {
      color: var(--accent);
    }

    .hero {
      background: linear-gradient(to bottom right, #A5D6A7, #00BFA6);
      color: white;
      text-align: center;
      padding: 90px 20px 100px;
      border-radius: 0 0 80px 80px;
    }

    .hero h2 {
      font-family: 'Poppins', sans-serif;
      font-size: 40px;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .hero p {
      font-size: 18px;
      max-width: 600px;
      margin: 0 auto 30px;
    }

    .search-bar {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }

    .search-bar input {
      padding: 14px 20px;
      width: 350px;
      border: none;
      border-radius: 30px;
      font-size: 16px;
      outline: none;
    }

    .search-bar button {
      background-color: var(--secondary);
      border: none;
      color: white;
      font-weight: 700;
      padding: 14px 25px;
      border-radius: 30px;
      cursor: pointer;
      transition: 0.3s;
    }

    .search-bar button:hover {
      background-color: var(--accent);
      color: var(--dark);
    }

    .kos-section {
      padding: 70px 60px;
      text-align: center;
    }

    .kos-section h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 30px;
      margin-bottom: 40px;
    }

    .kos-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 25px;
    }

    .kos-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      overflow: hidden;
      transition: 0.3s ease;
    }

    .kos-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .kos-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .kos-card .info {
      padding: 18px;
      text-align: left;
    }

    footer {
      background-color: var(--dark);
      color: white;
      text-align: center;
      padding: 25px;
      margin-top: 80px;
    }

    @media (max-width: 768px) {
      .search-bar input {
        width: 220px;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>NgekosAja.id</h1>
  <nav>
    <a href="#">Beranda</a>
    <a href="login.php">Login</a>
    <a href="register.php">Daftar</a>
  </nav>
</header>

<section class="hero">
  <h2>Temukan Kos Impianmu 🌴</h2>
  <p>Pilihan kos terbaik untuk mahasiswa dan perantau.</p>

  <div class="search-bar">
      <input id="searchInput" type="text" placeholder="Cari lokasi atau nama kos...">
      <button id="searchBtn">Cari Kos</button>
  </div>
</section>

<section class="kos-section">
  <h3>Kos Rekomendasi Untukmu</h3>

  <div class="kos-list">

    <div class="kos-card" data-name="Kos Putri Melati" data-lokasi="Tembalang Semarang">
      <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c" alt="">
      <div class="info">
        <h4>Kos Putri Melati</h4>
        <p>Rp 800.000 / bulan</p>
        <p>📍 Tembalang, Semarang</p>
      </div>
    </div>

    <div class="kos-card" data-name="Kos Putra Harmoni" data-lokasi="Banyumanik Semarang">
      <img src="https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde" alt="">
      <div class="info">
        <h4>Kos Putra Harmoni</h4>
        <p>Rp 1.000.000 / bulan</p>
        <p>📍 Banyumanik, Semarang</p>
      </div>
    </div>

    <div class="kos-card" data-name="Kos Amanah" data-lokasi="Pedurungan Semarang">
      <img src="https://images.unsplash.com/photo-1598928506311-c55ded91a20d" alt="">
      <div class="info">
        <h4>Kos Amanah</h4>
        <p>Rp 950.000 / bulan</p>
        <p>📍 Pedurungan, Semarang</p>
      </div>
    </div>

  </div>
</section>

<footer>
  &copy; 2025 NgekosAja.id | Temukan Kos Impianmu 🌿
</footer>

<!-- ========== JAVASCRIPT SEARCH ========== -->
<script>
document.getElementById("searchBtn").addEventListener("click", function () {
    const query = document.getElementById("searchInput").value.toLowerCase().trim();
    const cards = document.querySelectorAll(".kos-card");

    cards.forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const lokasi = card.dataset.lokasi.toLowerCase();

        card.style.display =
          (name.includes(query) || lokasi.includes(query))
          ? "block"
          : "none";
    });
});
</script>

<script>
document.getElementById("searchInput").addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
        document.getElementById("searchBtn").click();
    }
});
</script>
</body>
</html>