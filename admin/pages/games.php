<?php
// admin/pages/games.php — Kelola Game (CRUD + Upload Gambar)

$msg = ''; $msg_type = '';

// ---- HAPUS ----
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM games WHERE game_id=?");
    $stmt->bind_param("i", $id);
    // PERBAIKAN BARIS 11: Menggunakan if-else biasa
    if ($stmt->execute()) {
        $msg = 'Game berhasil dihapus.';
        $msg_type = 'success';
    } else {
        $msg = 'Gagal menghapus.';
        $msg_type = 'error';
    }
}

// ---- TOGGLE AKTIF ----
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE games SET is_active = NOT is_active WHERE game_id=$id");
    header("Location: ?page=games"); exit;
}

// ---- TAMBAH / EDIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $game_id_edit = intval($_POST['game_id'] ?? 0);
    $game_name   = trim($_POST['game_name'] ?? '');
    $publisher   = trim($_POST['publisher'] ?? '');
    $category    = $_POST['category'] ?? 'topup';
    $description = trim($_POST['description'] ?? '');
    $is_popular  = isset($_POST['is_popular']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$game_name) { $msg='Nama game wajib diisi.'; $msg_type='error'; goto END; }

    // Upload gambar
    $image_path = $_POST['old_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed_ext)) { $msg='Format gambar tidak didukung (jpg/png/webp).'; $msg_type='error'; goto END; }
        $fname = 'game_' . time() . '_' . rand(100,999) . '.' . $ext;
        $dest  = __DIR__ . '/../../uploads/games/' . $fname;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $image_path = 'uploads/games/' . $fname;
        }
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO games (game_name,publisher,category,image_path,description,is_popular,is_featured,is_active,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param("ssssssii", $game_name, $publisher, $category, $image_path, $description, $is_popular, $is_featured, $is_active);
        // PERBAIKAN BARIS 46: Menggunakan if-else biasa
        if ($stmt->execute()) {
            $msg = 'Game berhasil ditambahkan.';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal menyimpan: ' . $conn->error;
            $msg_type = 'error';
        }
    } elseif ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE games SET game_name=?,publisher=?,category=?,image_path=?,description=?,is_popular=?,is_featured=?,is_active=? WHERE game_id=?");
        $stmt->bind_param("ssssssiii", $game_name, $publisher, $category, $image_path, $description, $is_popular, $is_featured, $is_active, $game_id_edit);
        // PERBAIKAN BARIS 49: Menggunakan if-else biasa
        if ($stmt->execute()) {
            $msg = 'Game berhasil diperbarui.';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal update: ' . $conn->error;
            $msg_type = 'error';
        }
    }
    if ($msg_type === 'success') { header("Location: ?page=games&msg=".urlencode($msg)); exit; }
}
END:
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

// ---- EDIT LOAD ----
$edit = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $edit = $conn->query("SELECT * FROM games WHERE game_id=$eid")->fetch_assoc();
}

// ---- LIST ----
$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE game_name LIKE '%".  $conn->real_escape_string($search) ."%'" : '';
$games  = $conn->query("SELECT * FROM games $where ORDER BY game_id DESC");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FORM TAMBAH / EDIT -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2><?= $edit ? '✏️ Edit Game' : '➕ Tambah Game Baru' ?></h2>
    <?php if ($edit): ?>
    <a href="?page=games" class="btn btn-sm btn-secondary">✕ Batal Edit</a>
    <?php endif; ?>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="game_id" value="<?= $edit['game_id'] ?>"><input type="hidden" name="old_image" value="<?= esc($edit['image_path']) ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label>Nama Game *</label>
        <input type="text" name="game_name" value="<?= esc($edit['game_name'] ?? '') ?>" placeholder="Contoh: Mobile Legends" required>
      </div>
      <div class="field">
        <label>Publisher</label>
        <input type="text" name="publisher" value="<?= esc($edit['publisher'] ?? '') ?>" placeholder="Contoh: Moonton">
      </div>
      <div class="field">
        <label>Kategori</label>
        <select name="category">
          <?php foreach (['topup'=>'Top Up','via_login'=>'Via Login','voucher'=>'Voucher','live_app'=>'Live App'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= (($edit['category'] ?? 'topup') === $v) ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Gambar Game (JPG/PNG/WebP)</label>
        <input type="file" name="image" accept="image/*">
        <?php if (!empty($edit['image_path'])): ?>
        <img src="/vannmarket/<?= esc($edit['image_path']) ?>" class="img-preview" style="margin-top:8px;width:60px;height:60px;" onerror="this.style.display='none'">
        <?php endif; ?>
      </div>
    </div>

    <div class="form-grid" style="padding-top:0;">
      <div class="field" style="grid-column:1/-1;">
        <label>Deskripsi</label>
        <textarea name="description" placeholder="Deskripsi singkat game..."><?= esc($edit['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;gap:20px;padding:0 22px 22px;flex-wrap:wrap;align-items:center;">
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
        <input type="checkbox" name="is_popular" <?= (($edit['is_popular'] ?? 0) ? 'checked' : '') ?>> Populer
      </label>
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
        <input type="checkbox" name="is_featured" <?= (($edit['is_featured'] ?? 0) ? 'checked' : '') ?>> Rekomendasi
      </label>
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
        <input type="checkbox" name="is_active" <?= (($edit['is_active'] ?? 1) ? 'checked' : '') ?>> Aktif
      </label>
      <button type="submit" class="btn btn-primary" style="margin-left:auto;">
        <?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah Game' ?>
      </button>
    </div>
  </form>
</div>

<!-- TABEL GAME -->
<div class="section-card">
  <div class="section-header">
    <h2>🎮 Daftar Game (<?= $games->num_rows ?>)</h2>
    <form method="GET" class="search-bar">
      <input type="hidden" name="page" value="games">
      <input type="text" name="q" value="<?= esc($search) ?>" placeholder="Cari nama game...">
      <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Gambar</th><th>Nama Game</th><th>Publisher</th>
          <th>Kategori</th><th>Populer</th><th>Featured</th><th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($games->num_rows === 0): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="icon">🎮</div><p>Belum ada game.</p></div></td></tr>
        <?php else: ?>
        <?php while ($g = $games->fetch_assoc()): ?>
        <tr>
          <td>#<?= $g['game_id'] ?></td>
          <td><img src="/vannmarket/<?= esc($g['image_path']) ?>" class="img-preview" onerror="this.src='/vannmarket/assets/image/placeholder.png'" alt=""></td>
          <td style="font-weight:600;"><?= esc($g['game_name']) ?></td>
          <td><?= esc($g['publisher']) ?></td>
          <td><?= esc($g['category']) ?></td>
          <td><?= $g['is_popular'] ? '✅' : '—' ?></td>
          <td><?= $g['is_featured'] ? '✅' : '—' ?></td>
          <td>
            <a href="?page=games&toggle=<?= $g['game_id'] ?>" title="Klik untuk toggle" style="text-decoration:none;">
              <span class="badge-status <?= $g['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                <?= $g['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </a>
          </td>
          <td>
            <div class="cell-actions">
              <a href="?page=games&edit=<?= $g['game_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="?page=packages&game_id=<?= $g['game_id'] ?>" class="btn btn-sm btn-success">💎 Paket</a>
              <a href="?page=games&delete=<?= $g['game_id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus game <?= esc($g['game_name']) ?>? Semua paket terkait juga akan dihapus.')">Hapus</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
