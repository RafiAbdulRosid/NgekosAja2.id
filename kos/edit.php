<?php
// kos/edit.php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);
require_once __DIR__ . '/../db.php';

// cek login & role
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pemilik') {
    header('Location: ../login.php');
    exit;
}
$owner_id = (int)$_SESSION['user_id'];
$baseUrl = '/NgekosAja.id/';

// ambil id kos
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../dashboard_owner.php'); exit; }

// pastikan kos milik owner
$stmt = $pdo->prepare("SELECT * FROM kos WHERE id = ? AND owner_id = ? LIMIT 1");
$stmt->execute([$id, $owner_id]);
$kos = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$kos) {
    $_SESSION['flash_error'] = "Kos tidak ditemukan atau bukan milik Anda.";
    header('Location: ../dashboard_owner.php'); exit;
}

// ambil kamar dan images
$kamars = $pdo->prepare("SELECT * FROM kamar WHERE kos_id = ? ORDER BY id ASC");
$kamars->execute([$id]);
$kamars = $kamars->fetchAll(PDO::FETCH_ASSOC);

$images = $pdo->prepare("SELECT * FROM kos_images WHERE kos_id = ? ORDER BY id ASC");
$images->execute([$id]);
$images = $images->fetchAll(PDO::FETCH_ASSOC);

// proses POST update
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $type = in_array($_POST['type'] ?? 'campur',['putra','putri','campur']) ? $_POST['type'] : 'campur';
    $description = trim($_POST['description'] ?? '');

    if ($name === '') $errors[] = "Nama kos wajib diisi.";
    if ($price <= 0) $errors[] = "Harga minimal > 0.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE kos SET name = ?, address = ?, city = ?, price = ?, type = ?, description = ? WHERE id = ?");
            $upd->execute([$name,$address,$city,$price,$type,$description,$id]);

            // update/insert kamar sederhana:
            // jika ada kamar_id disubmit -> update; ada row baru dengan name kosong akan dilewati
            $room_ids = $_POST['room_id'] ?? [];
            $room_names = $_POST['room_name'] ?? [];
            $room_prices = $_POST['room_price'] ?? [];
            $room_status = $_POST['room_status'] ?? [];

            // iterate existing/submitted rows
            for ($i=0;$i<count($room_names);$i++) {
                $rid = isset($room_ids[$i]) ? (int)$room_ids[$i] : 0;
                $rname = trim($room_names[$i]);
                $rprice = (int)($room_prices[$i] ?? 0);
                $rstatus = in_array($room_status[$i] ?? 'kosong', ['kosong','terisi','dibooking']) ? $room_status[$i] : 'kosong';

                if ($rid > 0) {
                    // update existing
                    $pdo->prepare("UPDATE kamar SET name=?, price=?, status=? WHERE id=? AND kos_id=?")
                        ->execute([$rname,$rprice,$rstatus,$rid,$id]);
                } else {
                    // insert new if valid
                    if ($rname !== '' && $rprice > 0) {
                        $pdo->prepare("INSERT INTO kamar (kos_id, name, price, status) VALUES (?, ?, ?, ?)")
                            ->execute([$id,$rname,$rprice,$rstatus]);
                    }
                }
            }

            // proses hapus kamar jika ada request delete_kamar[] (button remove membuat array)
            if (!empty($_POST['delete_kamar']) && is_array($_POST['delete_kamar'])) {
                $toDel = array_map('intval', $_POST['delete_kamar']);
                $in = implode(',', array_fill(0, count($toDel), '?'));
                $stmtDel = $pdo->prepare("DELETE FROM kamar WHERE id IN ($in) AND kos_id = ?");
                $stmtDel->execute(array_merge($toDel, [$id]));
            }

            // proses upload gambar baru (sama aturan seperti add.php)
            if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $uploadDirBase = __DIR__ . '/../assets/uploads';
                if (!is_dir($uploadDirBase)) mkdir($uploadDirBase, 0755, true);
                $targetDir = $uploadDirBase . '/kos_' . $id;
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                $allowedTypes = ['image/jpeg','image/png','image/webp'];
                $maxSize = 4 * 1024 * 1024;
                $insImg = $pdo->prepare("INSERT INTO kos_images (kos_id, filename) VALUES (?, ?)");
                for ($i=0;$i<count($_FILES['images']['name']);$i++) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $tmp = $_FILES['images']['tmp_name'][$i];
                    $size = filesize($tmp);
                    $type = @mime_content_type($tmp);
                    if (!in_array($type, $allowedTypes) || $size > $maxSize) continue;
                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
                    $newName = 'img_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                    $dest = $targetDir . '/' . $newName;
                    if (move_uploaded_file($tmp, $dest)) {
                        $rel = 'assets/uploads/kos_' . $id . '/' . $newName;
                        $insImg->execute([$id, $rel]);
                    }
                }
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Perubahan tersimpan.";
            header('Location: ../dashboard_owner.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}

// proses hapus gambar (aksi via GET mis. ?del_img=ID) - sederhana
if (isset($_GET['del_img'])) {
    $imgId = (int)$_GET['del_img'];
    // pastikan image milik kos ini
    $s = $pdo->prepare("SELECT filename FROM kos_images WHERE id = ? AND kos_id = ? LIMIT 1");
    $s->execute([$imgId,$id]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $path = __DIR__ . '/../' . ltrim($row['filename'],'/');
        if (is_file($path)) @unlink($path);
        $pdo->prepare("DELETE FROM kos_images WHERE id = ?")->execute([$imgId]);
        $_SESSION['flash_success'] = "Gambar dihapus.";
        header('Location: edit.php?id=' . $id);
        exit;
    }
}

// include header jika ada
if (file_exists(__DIR__ . '/../includes/header.php')) include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4" style="max-width:1000px">
  <h3>Edit Kos: <?= htmlspecialchars($kos['name']) ?></h3>

  <?php if (!empty($errors)): ?>
    <div style="color:#b00020;padding:10px;border:1px solid #f5c6cb;background:#fff0f1;margin-bottom:12px">
      <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px">
      <div>
        <label>Nama Kos</label>
        <input class="form-control" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $kos['name']) ?>" required>

        <label style="margin-top:8px">Alamat</label>
        <textarea class="form-control" name="address"><?= htmlspecialchars($_POST['address'] ?? $kos['address']) ?></textarea>

        <div style="display:flex;gap:8px;margin-top:8px">
          <div style="flex:1">
            <label>Kota</label>
            <input class="form-control" name="city" value="<?= htmlspecialchars($_POST['city'] ?? $kos['city']) ?>">
          </div>
          <div style="width:140px">
            <label>Harga</label>
            <input class="form-control" name="price" type="number" value="<?= htmlspecialchars($_POST['price'] ?? $kos['price']) ?>">
          </div>
        </div>

        <label style="margin-top:8px">Tipe</label>
        <select class="form-control" name="type">
          <option value="campur" <?= ($kos['type']=='campur')?'selected':'' ?>>Campur</option>
          <option value="putra" <?= ($kos['type']=='putra')?'selected':'' ?>>Putra</option>
          <option value="putri" <?= ($kos['type']=='putri')?'selected':'' ?>>Putri</option>
        </select>

        <label style="margin-top:8px">Deskripsi</label>
        <textarea class="form-control" name="description"><?= htmlspecialchars($_POST['description'] ?? $kos['description']) ?></textarea>

        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
          <a class="btn btn-outline-secondary" href="../dashboard_owner.php" style="margin-left:8px">Batal</a>
        </div>
      </div>

      <aside>
        <div class="card p-2" style="margin-bottom:12px">
          <strong>Gambar saat ini</strong>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
            <?php foreach($images as $img): 
              $imgUrl = $baseUrl . ltrim($img['filename'],'/');
            ?>
              <div style="position:relative">
                <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:120px;height:80px;object-fit:cover;border-radius:6px">
                <a href="edit.php?id=<?= $id ?>&del_img=<?= $img['id'] ?>" onclick="return confirm('Hapus gambar ini?')" style="position:absolute;right:6px;top:6px;background:#fff;padding:3px;border-radius:50%;text-decoration:none;color:#c00">×</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card p-2" style="margin-bottom:12px">
          <label>Upload gambar baru (multiple)</label>
          <input type="file" name="images[]" multiple accept="image/*" class="form-control">
        </div>

        <div class="card p-2">
          <strong>Daftar Kamar</strong>
          <div id="roomsBox" style="margin-top:8px">
            <?php foreach($kamars as $km): ?>
              <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                <input type="hidden" name="room_id[]" value="<?= $km['id'] ?>">
                <input class="form-control" name="room_name[]" value="<?= htmlspecialchars($km['name']) ?>" style="flex:1">
                <input class="form-control" name="room_price[]" value="<?= htmlspecialchars($km['price']) ?>" style="width:90px">
                <select name="room_status[]" class="form-control" style="width:110px">
                  <option value="kosong" <?= $km['status']=='kosong'?'selected':'' ?>>Kosong</option>
                  <option value="terisi" <?= $km['status']=='terisi'?'selected':'' ?>>Terisi</option>
                </select>
                <label style="margin-left:6px"><input type="checkbox" name="delete_kamar[]" value="<?= $km['id'] ?>"> Hapus</label>
              </div>
            <?php endforeach; ?>
            <!-- template for adding new kamar -->
            <div id="newRoomTemplate" style="display:none">
              <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                <input type="hidden" name="room_id[]" value="0">
                <input class="form-control" name="room_name[]" value="" style="flex:1" placeholder="Nama kamar">
                <input class="form-control" name="room_price[]" value="" style="width:90px" placeholder="harga">
                <select name="room_status[]" class="form-control" style="width:110px"><option value="kosong">Kosong</option><option value="terisi">Terisi</option></select>
                <button type="button" class="btn btn-outline-danger removeNew">Hapus</button>
              </div>
            </div>
            <div style="margin-top:8px">
              <button type="button" id="addRoomBtn" class="btn btn-outline-primary">+ Tambah Kamar Baru</button>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </form>
</div>

<?php if (file_exists(__DIR__ . '/../includes/footer.php')) include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.getElementById('addRoomBtn').addEventListener('click', function(){
  var tpl = document.getElementById('newRoomTemplate').children[0].cloneNode(true);
  tpl.querySelector('.removeNew')?.addEventListener('click', function(){ tpl.remove(); });
  document.getElementById('roomsBox').appendChild(tpl);
});
</script>
