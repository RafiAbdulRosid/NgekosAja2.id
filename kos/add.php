<?php
// kos/add.php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db.php'; // pastikan benar

// Autocheck login + role
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    header('Location: ../login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ambil input
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $type = in_array($_POST['type'] ?? '', ['putra','putri','campur']) ? $_POST['type'] : 'campur';
    $description = trim($_POST['description'] ?? '');

    // kamar: dikirim sebagai array fields
    $room_names = $_POST['room_name'] ?? [];
    $room_prices = $_POST['room_price'] ?? [];
    $room_status = $_POST['room_status'] ?? [];

    // validasi dasar
    if ($name === '') $errors[] = "Nama kos wajib diisi.";
    if ($price <= 0) $errors[] = "Harga minimal harus lebih dari 0.";
    if (count($room_names) === 0) $errors[] = "Tambahkan minimal 1 kamar.";

    // proses jika valid
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // insert ke tabel kos
            $ins = $pdo->prepare("INSERT INTO kos (owner_id, name, address, city, price, type, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif')");
            $ins->execute([$user_id, $name, $address, $city, $price, $type, $description]);
            $kos_id = (int)$pdo->lastInsertId();

            // insert kamar satu per satu
            $insKamar = $pdo->prepare("INSERT INTO kamar (kos_id, name, price, status) VALUES (?, ?, ?, ?)");
            for ($i=0;$i<count($room_names);$i++) {
                $rname = trim($room_names[$i]);
                $rprice = (int)($room_prices[$i] ?? 0);
                $rstatus = in_array($room_status[$i] ?? '', ['kosong','terisi','dibooking']) ? $room_status[$i] : 'kosong';
                if ($rname === '' || $rprice <= 0) continue; // skip invalid row
                $insKamar->execute([$kos_id, $rname, $rprice, $rstatus]);
            }

            // proses upload gambar (multi)
            if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $uploadDirBase = __DIR__ . '/../assets/uploads';
                if (!is_dir($uploadDirBase)) {
                    mkdir($uploadDirBase, 0755, true);
                }
                $targetDir = $uploadDirBase . '/kos_' . $kos_id;
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                $allowedTypes = ['image/jpeg','image/png','image/webp'];
                $maxSize = 4 * 1024 * 1024; // 4MB per file
                $insImg = $pdo->prepare("INSERT INTO kos_images (kos_id, filename) VALUES (?, ?)");

                for ($i=0;$i<count($_FILES['images']['name']);$i++) {
                    $err = $_FILES['images']['error'][$i];
                    if ($err !== UPLOAD_ERR_OK) continue;
                    $tmp = $_FILES['images']['tmp_name'][$i];
                    $nameOrig = basename($_FILES['images']['name'][$i]);
                    $type = mime_content_type($tmp);
                    $size = filesize($tmp);

                    if (!in_array($type, $allowedTypes)) continue;
                    if ($size > $maxSize) continue;

                    // buat nama file aman
                    $ext = pathinfo($nameOrig, PATHINFO_EXTENSION) ?: 'jpg';
                    $newName = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $dest = $targetDir . '/' . $newName;
                    if (move_uploaded_file($tmp, $dest)) {
                        // simpan relatif path (tanpa root), contoh: assets/uploads/kos_5/img_xxx.jpg
                        $relPath = 'assets/uploads/kos_' . $kos_id . '/' . $newName;
                        $insImg->execute([$kos_id, $relPath]);
                    }
                }
            }

            $pdo->commit();
            $success = "Kos berhasil dibuat.";
            // redirect ke dashboard pemilik atau ke detail kos
            header("Location: ../dashboard_owner.php?msg=" . urlencode($success));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Terjadi kesalahan saat menyimpan: " . $e->getMessage();
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container my-5" style="max-width:900px;">
  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 style="color: #5f647bff;font-family:'Nunito Sans',sans-serif">Tambah Kos Baru</h4>
      <a class="btn btn-outline-secondary" href="../dashboard_owner.php">Kembali</a>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="formAddKos">
      <div class="mb-3">
        <label class="form-label">Nama Kos</label>
        <input class="form-control" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Alamat</label>
        <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Kota</label>
          <input class="form-control" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Harga mulai (per bulan)</label>
          <input class="form-control" name="price" type="number" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Tipe</label>
        <select class="form-select" name="type">
          <option value="campur" <?= (($_POST['type'] ?? '')==='campur')?'selected':'' ?>>Campur</option>
          <option value="putra" <?= (($_POST['type'] ?? '')==='putra')?'selected':'' ?>>Putra</option>
          <option value="putri" <?= (($_POST['type'] ?? '')==='putri')?'selected':'' ?>>Putri</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <hr>

      <h5>Daftar Kamar</h5>
      <p class="text-muted small">Tambahkan kamar yang tersedia. Minimal 1 kamar.</p>

      <div id="roomsContainer">
        <?php
        // jika post balik ada data, tampil ulang
        $prevNames = $_POST['room_name'] ?? ['Kamar 1'];
        $prevPrices = $_POST['room_price'] ?? ['0'];
        $prevStatus = $_POST['room_status'] ?? ['kosong'];
        for ($i=0;$i<count($prevNames);$i++):
        ?>
        <div class="row room-row mb-2" data-index="<?= $i ?>">
          <div class="col-md-5">
            <input class="form-control" name="room_name[]" placeholder="Nama kamar (mis: Kamar 1)" value="<?= htmlspecialchars($prevNames[$i]) ?>">
          </div>
          <div class="col-md-4">
            <input class="form-control" name="room_price[]" type="number" placeholder="Harga" value="<?= htmlspecialchars($prevPrices[$i]) ?>">
          </div>
          <div class="col-md-2">
            <select class="form-select" name="room_status[]">
              <option value="kosong" <?= ($prevStatus[$i] ?? '')==='kosong' ? 'selected':'' ?>>Kosong</option>
              <option value="terisi" <?= ($prevStatus[$i] ?? '')==='terisi' ? 'selected':'' ?>>Terisi</option>
            </select>
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-sm remove-room">×</button>
          </div>
        </div>
        <?php endfor; ?>
      </div>

      <div class="mb-3">
        <button type="button" id="addRoom" class="btn btn-outline-primary">+ Tambah Kamar</button>
      </div>

      <hr>

      <div class="mb-3">
        <label class="form-label">Foto Kos (bisa pilih banyak, max 4MB per file)</label>
        <input type="file" name="images[]" accept="image/*" multiple class="form-control">
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan Kos</button>
        <a href="../dashboard_owner.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  (function(){
    const roomsContainer = document.getElementById('roomsContainer');
    const addBtn = document.getElementById('addRoom');

    addBtn.addEventListener('click', function(){
      const idx = roomsContainer.querySelectorAll('.room-row').length;
      const row = document.createElement('div');
      row.className = 'row room-row mb-2';
      row.innerHTML = `
        <div class="col-md-5"><input class="form-control" name="room_name[]" placeholder="Nama kamar (mis: Kamar 2)"></div>
        <div class="col-md-4"><input class="form-control" name="room_price[]" type="number" placeholder="Harga"></div>
        <div class="col-md-2"><select class="form-select" name="room_status[]"><option value="kosong">Kosong</option><option value="terisi">Terisi</option></select></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-room">×</button></div>
      `;
      roomsContainer.appendChild(row);
    });

    document.addEventListener('click', function(e){
      if (e.target && e.target.classList.contains('remove-room')) {
        const row = e.target.closest('.room-row');
        if (row) row.remove();
      }
    });
  })();
</script>
