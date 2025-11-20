<?php
// login.php
session_start();
require_once 'db.php'; // Memastikan variabel $pdo tersedia

$error = '';

// Cek apakah user sudah login, jika ya, arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'pemilik') {
        header('Location: dashboard_owner.php');
        exit;
    } else {
        header('Location: dashboard_user.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? ''); // email atau username
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $error = "Lengkapi email/username dan password.";
    } else {
        // Ambil user berdasarkan email atau username
        $sql = "SELECT id, username, fullname, email, password_hash, role FROM users WHERE email = ? OR username = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Tutup koneksi PDO setelah query selesai
        $pdo = null;

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login sukses: set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname']; // PENTING: Untuk menampilkan nama di header/dashboard
            $_SESSION['role'] = $user['role'];

            // redirect sesuai role
            if ($user['role'] === 'pemilik') {
                header('Location: dashboard_owner.php');
                exit;
            } else {
                // Asumsi role selain 'pemilik' diarahkan ke dashboard_user.php
                header('Location: dashboard_user.php');
                exit;
            }
        } else {
            $error = "Email/username atau password salah.";
        }
    }
}

// Jika ada error di koneksi db.php dan belum ditangani
if (isset($pdo) && $pdo === null) {
    // Jika koneksi gagal, meskipun seharusnya di-die() di db.php
    // Tapi ini sebagai pengamanan tambahan
    $error = "Terjadi masalah koneksi database.";
}

include 'includes/header.php'; ?>

<style>
body {
    background: #F6F1EE;
    font-family: "Poppins", sans-serif;
}

.container-login {
    max-width: 750px;
    margin: 40px auto;
    text-align: center;
}

/* Judul */
.login-title {
    font-size: 42px;
    font-weight: 600;
    color: #223A59;
    margin-bottom: 35px;
}

/* Card biru */
.card-login {
    background: #74B7DB;
    padding: 50px 60px;
    border-radius: 30px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    text-align: left;
}

/* Label */
.form-label {
    font-weight: 500;
    color: #223A59;
    margin-bottom: 5px;
}

/* Input style sama dengan register */
.card-login .form-control {
    background: rgba(0, 50, 90, 0.35);
    border: none;
    height: 48px;
    color: white;
    border-radius: 12px;
    font-size: 16px;
    padding-left: 15px;
}
.card-login .form-control::placeholder {
    color: rgba(255,255,255,0.7);
}

/* Tombol */
.btn-primary {
    background: white !important;
    color: #223A59 !important;
    border: none;
    border-radius: 14px;
    font-size: 18px;
    padding: 12px 0;
    width: 220px;
    display: block;
    margin: 25px auto 0;
    transition: 0.2s;
}
.btn-primary:hover {
    opacity: 0.85;
}

/* Alerts */
.alert {
    border-radius: 15px;
    text-align: left;
}

/* Link Daftar */
.text-center a {
    color: #223A59;
    font-weight: 600;
}
</style>

<div class="container-login">
    <h1 class="login-title">MASUK</h1>

    <div class="card-login">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-2">
                <label class="form-label">Email atau Username</label>
                <input class="form-control" name="identity"
                       value="<?= htmlspecialchars($_POST['identity'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" name="password" type="password" required>
            </div>

            <button class="btn btn-primary w-100" type="submit">Masuk</button>
        </form>

        <p class="mt-3 text-center">
            Belum punya akun? <a href="register.php">Daftar</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>