<?php
// admin/pages/vouchers.php — Kelola Voucher

$msg = ''; $msg_type = '';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM vouchers WHERE voucher_id=?");
    $stmt->bind_param("i", $id);
    
    // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
    if ($stmt->execute()) {
        $msg = 'Voucher dihapus.';
        $msg_type = 'success';
    } else {
        $msg = 'Gagal.';
        $msg_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $vid          = intval($_POST['voucher_id'] ?? 0);
    $code         = strtoupper(trim($_POST['voucher_code'] ?? ''));
    $game_id      = intval($_POST['game_id'] ?? 0) ?: null;
    $discount_pct = floatval($_POST['discount_pct'] ?? 0);
    $valid_from   = $_POST['valid_from'] ?: null;
    $valid_until  = $_POST['valid_until'] ?: null;

    if (!$code) { 
        $msg = 'Kode voucher wajib diisi.'; 
        $msg_type = 'error'; 
    } else {
        // Cek duplikat
        $chk_id = ($action === 'edit') ? $vid : 0;
        $chk = $conn->prepare("SELECT voucher_id FROM vouchers WHERE voucher_code=? AND voucher_id!=?");
        $chk->bind_param("si", $code, $chk_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $msg = "Kode voucher '$code' sudah digunakan."; $msg_type = 'error';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO vouchers (voucher_code,game_id,discount_pct,valid_from,valid_until,created_at) VALUES (?,?,?,?,?,NOW())");
                $stmt->bind_param("sids s", $code, $game_id, $discount_pct, $valid_from, $valid_until);
                
                // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
                if ($stmt->execute()) {
                    $msg = 'Voucher berhasil dibuat.';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal.';
                    $msg_type = 'error';
                }
            } elseif ($action === 'edit') {
                $stmt = $conn->prepare("UPDATE vouchers SET voucher_code=?,game_id=?,discount_pct=?,valid_from=?,valid_until=? WHERE voucher_id=?");
                $stmt->bind_param("sidssi", $code, $game_id, $discount_pct, $valid_from, $valid_until, $vid);
                
                // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
                if ($stmt->execute()) {
                    $msg = 'Voucher berhasil diperbarui.';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal.';
                    $msg_type = 'error';
                }
            }
            if ($msg_type === 'success') { header("Location: ?page=vouchers&msg=".urlencode($msg)); exit; }
        }
    }
}
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

$edit = null;
if (isset($_GET['edit'])) {
    $eid  = intval($_GET['edit']);
    $edit = $conn->query("SELECT * FROM vouchers WHERE voucher_id=$eid")->fetch_assoc();
}

$games_list = $conn->query("SELECT game_id, game_name FROM games WHERE is_active=1 ORDER BY game_name");
$vouchers   = $conn->query("SELECT v.*, g.game_name FROM vouchers v LEFT JOIN games g ON v.game_id=g.game_id ORDER BY v.voucher_id DESC");
$today = date('Y-m-d');
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FORM -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2><?= $edit ? '✏️ Edit Voucher' : '➕ Buat Voucher Baru' ?></h2>
    <?php if ($edit): ?><a href="?page=vouchers" class="btn btn-sm btn-secondary">✕ Batal</a><?php endif; ?>
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="voucher_id" value="<?= $edit['voucher_id'] ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label>Kode Voucher *</label>
        <input type="text" name="voucher_code" value="<?= esc($edit['voucher_code'] ?? '') ?>"
               placeholder="Contoh: PROMO10" style="text-transform:uppercase;" required
               oninput="this.value=this.value.toUpperCase()">
        <span class="hint">Otomatis jadi huruf besar</span>
      </div>

      <div class="field">
        <label>Berlaku untuk Game</label>
        <select name="game_id">
          <option value="">-- Semua Game --</option>
          <?php
          $games_list->data_seek(0);
          while ($gm = $games_list->fetch_assoc()):
          ?>
          <option value="<?= $gm['game_id'] ?>" <?= (isset($edit['game_id']) && $edit['game_id'] == $gm['game_id']) ? 'selected' : '' ?>>
            <?= esc($gm['game_name']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="field">
        <label>Diskon (%)</label>
        <input type="number" name="discount_pct" value="<?= $edit['discount_pct'] ?? 0 ?>" step="0.1" min="0" max="100" placeholder="Contoh: 10">
      </div>

      <div class="field">
        <label>Berlaku Dari</label>
        <input type="date" name="valid_from" value="<?= esc($edit['valid_from'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Berlaku Sampai</label>
        <input type="date" name="valid_until" value="<?= esc($edit['valid_until'] ?? '') ?>">
      </div>
    </div>

    <div style="padding:0 22px 22px;">
      <button type="submit" class="btn btn-primary">
        <?= $edit ? '💾 Simpan Perubahan' : '➕ Buat Voucher' ?>
      </button>
    </div>
  </form>
</div>

<!-- TABEL -->
<div class="section-card">
  <div class="section-header"><h2>🎫 Daftar Voucher (<?= $vouchers->num_rows ?>)</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Kode</th><th>Game</th><th>Diskon</th><th>Mulai</th><th>Akhir</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if ($vouchers->num_rows === 0): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="icon">🎫</div><p>Belum ada voucher.</p></div></td></tr>
        <?php else: ?>
        <?php while ($v = $vouchers->fetch_assoc()):
          $expired  = $v['valid_until'] && $today > $v['valid_until'];
          $notyet   = $v['valid_from']  && $today < $v['valid_from'];
          $vstatus  = $expired ? 'Kedaluwarsa' : ($notyet ? 'Belum Aktif' : 'Aktif');
          $vstyle   = $expired ? 'badge-inactive' : ($notyet ? 'badge-pending' : 'badge-active');
        ?>
        <tr>
          <td>#<?= $v['voucher_id'] ?></td>
          <td style="font-weight:700;letter-spacing:1px;color:#e60000;"><?= esc($v['voucher_code']) ?></td>
          <td><?= $v['game_name'] ? esc($v['game_name']) : '<span style="color:#888;">Semua Game</span>' ?></td>
          <td style="font-weight:600;"><?= $v['discount_pct'] ?>%</td>
          <td style="font-size:13px;color:#aaa;"><?= $v['valid_from'] ? date('d M Y', strtotime($v['valid_from'])) : '—' ?></td>
          <td style="font-size:13px;color:#aaa;"><?= $v['valid_until'] ? date('d M Y', strtotime($v['valid_until'])) : '—' ?></td>
          <td><span class="badge-status <?= $vstyle ?>"><?= $vstatus ?></span></td>
          <td>
            <div class="cell-actions">
              <a href="?page=vouchers&edit=<?= $v['voucher_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="?page=vouchers&delete=<?= $v['voucher_id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus voucher ini?')">Hapus</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>