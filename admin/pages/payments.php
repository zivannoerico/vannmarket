<?php
// admin/pages/payments.php — Kelola Metode Pembayaran

$msg = ''; $msg_type = '';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM payment_methods WHERE method_id=?");
    $stmt->bind_param("i", $id);
    
    // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
    if ($stmt->execute()) {
        $msg = 'Metode pembayaran dihapus.';
        $msg_type = 'success';
    } else {
        $msg = 'Gagal menghapus.';
        $msg_type = 'error';
    }
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE payment_methods SET is_active = NOT is_active WHERE method_id=$id");
    header("Location: ?page=payments"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $mid         = intval($_POST['method_id'] ?? 0);
    $method_name = trim($_POST['method_name'] ?? '');
    $method_type = $_POST['method_type'] ?? 'e-wallet';
    $fee_pct     = floatval($_POST['fee_pct'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$method_name) { 
        $msg = 'Nama metode wajib diisi.'; 
        $msg_type = 'error'; 
    } else {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO payment_methods (method_name,method_type,fee_pct,is_active,created_at) VALUES (?,?,?,?,NOW())");
            $stmt->bind_param("ssdi", $method_name, $method_type, $fee_pct, $is_active);
            
            // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
            if ($stmt->execute()) {
                $msg = 'Metode berhasil ditambahkan.';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal.';
                $msg_type = 'error';
            }
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE payment_methods SET method_name=?,method_type=?,fee_pct=?,is_active=? WHERE method_id=?");
            $stmt->bind_param("ssdii", $method_name, $method_type, $fee_pct, $is_active, $mid);
            
            // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
            if ($stmt->execute()) {
                $msg = 'Metode berhasil diperbarui.';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal.';
                $msg_type = 'error';
            }
        }
        if ($msg_type === 'success') { header("Location: ?page=payments&msg=".urlencode($msg)); exit; }
    }
}
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

$edit = null;
if (isset($_GET['edit'])) {
    $eid  = intval($_GET['edit']);
    $edit = $conn->query("SELECT * FROM payment_methods WHERE method_id=$eid")->fetch_assoc();
}

$methods = $conn->query("SELECT * FROM payment_methods ORDER BY method_type, method_name");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FORM -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2><?= $edit ? '✏️ Edit Metode Pembayaran' : '➕ Tambah Metode Pembayaran' ?></h2>
    <?php if ($edit): ?><a href="?page=payments" class="btn btn-sm btn-secondary">✕ Batal</a><?php endif; ?>
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="method_id" value="<?= $edit['method_id'] ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label>Nama Metode *</label>
        <input type="text" name="method_name" value="<?= esc($edit['method_name'] ?? '') ?>" placeholder="Contoh: GoPay" required>
      </div>
      <div class="field">
        <label>Tipe</label>
        <select name="method_type">
          <?php foreach (['e-wallet'=>'E-Wallet','bank'=>'Transfer Bank','minimarket'=>'Minimarket','crypto'=>'Crypto'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= (($edit['method_type'] ?? 'e-wallet') === $v) ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Biaya Admin (%)</label>
        <input type="number" name="fee_pct" value="<?= $edit['fee_pct'] ?? 0 ?>" step="0.01" min="0" placeholder="0 = gratis">
      </div>
    </div>

    <div style="display:flex;gap:20px;padding:0 22px 22px;align-items:center;">
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
        <input type="checkbox" name="is_active" <?= (($edit['is_active'] ?? 1) ? 'checked' : '') ?>> Aktif
      </label>
      <button type="submit" class="btn btn-primary" style="margin-left:auto;">
        <?= $edit ? '💾 Simpan' : '➕ Tambah' ?>
      </button>
    </div>
  </form>
</div>

<!-- TABEL -->
<div class="section-card">
  <div class="section-header"><h2>💳 Daftar Metode Pembayaran</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Nama</th><th>Tipe</th><th>Fee</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if ($methods->num_rows === 0): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="icon">💳</div><p>Belum ada metode pembayaran.</p></div></td></tr>
        <?php else: ?>
        <?php while ($m = $methods->fetch_assoc()): ?>
        <tr>
          <td>#<?= $m['method_id'] ?></td>
          <td style="font-weight:600;"><?= esc($m['method_name']) ?></td>
          <td><?= esc($m['method_type']) ?></td>
          <td><?= $m['fee_pct'] > 0 ? $m['fee_pct'].'%' : 'Gratis' ?></td>
          <td>
            <a href="?page=payments&toggle=<?= $m['method_id'] ?>" style="text-decoration:none;">
              <span class="badge-status <?= $m['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                <?= $m['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </a>
          </td>
          <td>
            <div class="cell-actions">
              <a href="?page=payments&edit=<?= $m['method_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="?page=payments&delete=<?= $m['method_id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus metode ini?')">Hapus</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>