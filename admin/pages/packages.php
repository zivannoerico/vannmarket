<?php
// admin/pages/packages.php — Kelola Harga Diamond per Game

$msg = ''; $msg_type = '';

// ---- HAPUS ----
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM diamond_packages WHERE package_id=?");
    $stmt->bind_param("i", $id);
    
    // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
    if ($stmt->execute()) {
        $msg = 'Paket berhasil dihapus.';
        $msg_type = 'success';
    } else {
        $msg = 'Gagal menghapus.';
        $msg_type = 'error';
    }
}

// ---- TOGGLE AKTIF ----
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE diamond_packages SET is_active = NOT is_active WHERE package_id=$id");
    $back = '?page=packages' . (isset($_GET['game_id']) ? '&game_id='.(int)$_GET['game_id'] : '');
    header("Location: $back"); exit;
}

// ---- TAMBAH / EDIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $pkg_id      = intval($_POST['package_id'] ?? 0);
    $game_id     = intval($_POST['game_id'] ?? 0);
    $pkg_name    = trim($_POST['package_name'] ?? '');
    $amount      = intval($_POST['amount'] ?? 0);
    $price       = floatval($_POST['price'] ?? 0);
    $bonus       = intval($_POST['bonus_amount'] ?? 0);
    $category    = $_POST['category'] ?? 'topup';
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$game_id || !$pkg_name || $price <= 0) {
        $msg = 'Game, nama paket, dan harga wajib diisi.'; $msg_type = 'error';
    } else {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO diamond_packages (game_id,package_name,amount,price,bonus_amount,category,is_active,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->bind_param("isidiisi", $game_id, $pkg_name, $amount, $price, $bonus, $category, $is_active);
            
            // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
            if ($stmt->execute()) {
                $msg = 'Paket berhasil ditambahkan.';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal: ' . $conn->error;
                $msg_type = 'error';
            }
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE diamond_packages SET game_id=?,package_name=?,amount=?,price=?,bonus_amount=?,category=?,is_active=? WHERE package_id=?");
            $stmt->bind_param("isidiisi", $game_id, $pkg_name, $amount, $price, $bonus, $category, $is_active, $pkg_id);
            
            // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
            if ($stmt->execute()) {
                $msg = 'Paket berhasil diperbarui.';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal: ' . $conn->error;
                $msg_type = 'error';
            }
        }
        if ($msg_type === 'success') {
            header("Location: ?page=packages&game_id={$game_id}&msg=".urlencode($msg)); exit;
        }
    }
}
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

// ---- FILTER GAME ----
$filter_game_id = intval($_GET['game_id'] ?? 0);

// ---- EDIT LOAD ----
$edit = null;
if (isset($_GET['edit'])) {
    $eid  = intval($_GET['edit']);
    $edit = $conn->query("SELECT * FROM diamond_packages WHERE package_id=$eid")->fetch_assoc();
    if ($edit && !$filter_game_id) $filter_game_id = $edit['game_id'];
}

// ---- LOAD SEMUA GAME ----
$games_list = $conn->query("SELECT game_id, game_name FROM games WHERE is_active=1 ORDER BY game_name");
$games_map  = [];
while ($gm = $games_list->fetch_assoc()) $games_map[$gm['game_id']] = $gm['game_name'];

// ---- LIST PACKAGES ----
$where_pkg = $filter_game_id ? "WHERE dp.game_id=$filter_game_id" : '';
$packages  = $conn->query("SELECT dp.*, g.game_name FROM diamond_packages dp LEFT JOIN games g ON dp.game_id=g.game_id $where_pkg ORDER BY dp.game_id, dp.category, dp.price ASC");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FILTER GAME -->
<div style="margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
  <span style="font-size:14px;color:#888;">Filter Game:</span>
  <a href="?page=packages" class="btn btn-sm <?= !$filter_game_id ? 'btn-primary' : 'btn-secondary' ?>">Semua</a>
  <?php foreach ($games_map as $gid => $gname): ?>
  <a href="?page=packages&game_id=<?= $gid ?>" class="btn btn-sm <?= $filter_game_id === $gid ? 'btn-primary' : 'btn-secondary' ?>">
    <?= esc($gname) ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- FORM -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2><?= $edit ? '✏️ Edit Paket Diamond' : '➕ Tambah Paket Diamond' ?></h2>
    <?php if ($edit): ?>
    <a href="?page=packages<?= $filter_game_id ? '&game_id='.$filter_game_id : '' ?>" class="btn btn-sm btn-secondary">✕ Batal</a>
    <?php endif; ?>
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="package_id" value="<?= $edit['package_id'] ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label>Game *</label>
        <select name="game_id" required>
          <option value="">-- Pilih Game --</option>
          <?php foreach ($games_map as $gid => $gname): ?>
          <option value="<?= $gid ?>" <?= (($edit['game_id'] ?? $filter_game_id) === $gid) ? 'selected' : '' ?>><?= esc($gname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Nama Paket *</label>
        <input type="text" name="package_name" value="<?= esc($edit['package_name'] ?? '') ?>" placeholder="Contoh: 86 Diamonds" required>
      </div>

      <div class="field">
        <label>Jumlah Diamond</label>
        <input type="number" name="amount" value="<?= $edit['amount'] ?? 0 ?>" min="0" placeholder="0 = tidak berlaku">
        <span class="hint">Isi 0 jika membership/voucher</span>
      </div>

      <div class="field">
  <label>Harga (Rp) *</label>
  <input type="number" name="price" value="<?= $edit['price'] ?? '' ?>" step="any" min="1" placeholder="Contoh: 19000" required>
</div>

      <div class="field">
        <label>Bonus Diamond</label>
        <input type="number" name="bonus_amount" value="<?= $edit['bonus_amount'] ?? 0 ?>" min="0" placeholder="0 jika tidak ada bonus">
      </div>

      <div class="field">
        <label>Kategori</label>
        <select name="category">
          <option value="topup" <?= (($edit['category'] ?? 'topup') === 'topup') ? 'selected' : '' ?>>Top Up</option>
          <option value="membership" <?= (($edit['category'] ?? '') === 'membership') ? 'selected' : '' ?>>Membership</option>
        </select>
      </div>
    </div>

    <div style="display:flex;gap:20px;padding:0 22px 22px;align-items:center;">
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
        <input type="checkbox" name="is_active" <?= (($edit['is_active'] ?? 1) ? 'checked' : '') ?>> Aktif
      </label>
      <button type="submit" class="btn btn-primary" style="margin-left:auto;">
        <?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah Paket' ?>
      </button>
    </div>
  </form>
</div>

<!-- TABEL PACKAGES -->
<div class="section-card">
  <div class="section-header">
    <h2>💎 Daftar Paket Diamond <?= $filter_game_id ? '— '.esc($games_map[$filter_game_id] ?? '') : '(Semua Game)' ?> (<?= $packages->num_rows ?>)</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Game</th><th>Nama Paket</th><th>Jumlah</th>
          <th>Bonus</th><th>Harga</th><th>Kategori</th><th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($packages->num_rows === 0): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="icon">💎</div><p>Belum ada paket.</p></div></td></tr>
        <?php else: ?>
        <?php while ($p = $packages->fetch_assoc()): ?>
        <tr>
          <td>#<?= $p['package_id'] ?></td>
          <td style="font-size:13px;color:#aaa;"><?= esc($p['game_name']) ?></td>
          <td style="font-weight:600;"><?= esc($p['package_name']) ?></td>
          <td><?= $p['amount'] > 0 ? number_format($p['amount']) : '—' ?></td>
          <td><?= $p['bonus_amount'] > 0 ? '+'.number_format($p['bonus_amount']) : '—' ?></td>
          <td style="font-weight:600;color:#e60000;"><?= formatRupiah($p['price']) ?></td>
          <td><?= esc($p['category']) ?></td>
          <td>
            <a href="?page=packages&toggle=<?= $p['package_id'] ?><?= $filter_game_id ? '&game_id='.$filter_game_id : '' ?>" style="text-decoration:none;">
              <span class="badge-status <?= $p['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                <?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </a>
          </td>
          <td>
            <div class="cell-actions">
              <a href="?page=packages&edit=<?= $p['package_id'] ?><?= $filter_game_id ? '&game_id='.$filter_game_id : '' ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="?page=packages&delete=<?= $p['package_id'] ?><?= $filter_game_id ? '&game_id='.$filter_game_id : '' ?>"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus paket ini?')">Hapus</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>