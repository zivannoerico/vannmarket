<?php
// admin/pages/leaderboard.php — Admin atur leaderboard manual

$msg = ''; $msg_type = '';

// Tambah ke leaderboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $game_account_id = trim($_POST['game_account_id'] ?? '');
    $display_name    = trim($_POST['display_name'] ?? '');
    $total_topup     = floatval($_POST['total_topup'] ?? 0);
    $total_trx       = intval($_POST['total_trx'] ?? 0);
    $device_type     = $_POST['device_type'] ?? 'mobile';
    $fav_game_id     = intval($_POST['fav_game_id'] ?? 0) ?: null;
    $is_pinned       = isset($_POST['is_pinned']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO leaderboard_manual (game_account_id, display_name, total_topup, total_trx, device_type, fav_game_id, is_pinned, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
    $stmt->bind_param("ssdisii", $game_account_id, $display_name, $total_topup, $total_trx, $device_type, $fav_game_id, $is_pinned);
    if ($stmt->execute()) {
        $msg = 'Berhasil ditambahkan ke leaderboard.'; $msg_type = 'success';
        header("Location: ?page=leaderboard&msg=".urlencode($msg)); exit;
    } else {
        $msg = 'Gagal: '.$conn->error; $msg_type = 'error';
    }
}

// Hapus dari leaderboard
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM leaderboard_manual WHERE lb_id=$id");
    header("Location: ?page=leaderboard&msg=".urlencode('Data dihapus.')); exit;
}

// Toggle pin
if (isset($_GET['pin'])) {
    $id = intval($_GET['pin']);
    $conn->query("UPDATE leaderboard_manual SET is_pinned = NOT is_pinned WHERE lb_id=$id");
    header("Location: ?page=leaderboard"); exit;
}

if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

$games_list = $conn->query("SELECT game_id, game_name FROM games WHERE is_active=1 ORDER BY game_name");
$lb_rows    = $conn->query("SELECT lb.*, g.game_name, g.image_path FROM leaderboard_manual lb LEFT JOIN games g ON lb.fav_game_id=g.game_id ORDER BY lb.is_pinned DESC, lb.total_topup DESC");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FORM TAMBAH -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header"><h2>➕ Tambah ke Leaderboard</h2></div>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="field">
        <label>Nama Tampil *</label>
        <input type="text" name="display_name" placeholder="Nama yang ditampilkan" required>
      </div>
      <div class="field">
        <label>User ID Game</label>
        <input type="text" name="game_account_id" placeholder="ID akun game">
      </div>
      <div class="field">
        <label>Total Top Up (Rp)</label>
        <input type="number" name="total_topup" placeholder="Contoh: 500000" min="0">
      </div>
      <div class="field">
        <label>Jumlah Transaksi</label>
        <input type="number" name="total_trx" placeholder="Contoh: 10" min="0">
      </div>
      <div class="field">
        <label>Perangkat</label>
        <select name="device_type">
          <option value="mobile">📱 Mobile</option>
          <option value="pc">💻 PC / Desktop</option>
        </select>
      </div>
      <div class="field">
        <label>Game Favorit</label>
        <select name="fav_game_id">
          <option value="">-- Pilih Game --</option>
          <?php while ($gm = $games_list->fetch_assoc()): ?>
          <option value="<?= $gm['game_id'] ?>"><?= esc($gm['game_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div style="padding:0 22px 22px;display:flex;align-items:center;gap:16px;">
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
        <input type="checkbox" name="is_pinned" value="1"> 📌 Pin di teratas
      </label>
      <button type="submit" class="btn btn-primary">➕ Tambah ke Leaderboard</button>
    </div>
  </form>
</div>

<!-- TABEL LEADERBOARD -->
<div class="section-card">
  <div class="section-header">
    <h2>🏆 Data Leaderboard (<?= $lb_rows ? $lb_rows->num_rows : 0 ?>)</h2>
    <a href="/vannmarket/public/leaderboard.php" target="_blank" class="btn btn-sm btn-secondary">🌐 Lihat di Website</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Nama</th><th>Total Top Up</th><th>Transaksi</th><th>Perangkat</th><th>Game Favorit</th><th>Pin</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (!$lb_rows || $lb_rows->num_rows === 0): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="icon">🏆</div><p>Belum ada data leaderboard.</p></div></td></tr>
        <?php else: $rank=1; while ($r = $lb_rows->fetch_assoc()): ?>
        <tr>
          <td><strong>#<?= $rank++ ?></strong></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:32px;height:32px;background:#e60000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;">
                <?= strtoupper(substr($r['display_name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600;"><?= esc($r['display_name']) ?></div>
                <div style="font-size:11px;color:#666;"><?= esc($r['game_account_id'] ?: '—') ?></div>
              </div>
            </div>
          </td>
          <td style="font-weight:700;color:#e60000;"><?= formatRupiah($r['total_topup']) ?></td>
          <td style="color:#aaa;"><?= $r['total_trx'] ?>x</td>
          <td><?= $r['device_type'] === 'pc' ? '💻 PC' : '📱 Mobile' ?></td>
          <td>
            <?php if ($r['game_name']): ?>
            <div style="display:flex;align-items:center;gap:6px;">
              <img src="/vannmarket/<?= esc($r['image_path'] ?? '') ?>" style="width:24px;height:24px;border-radius:4px;object-fit:cover;" onerror="this.style.display='none'">
              <?= esc($r['game_name']) ?>
            </div>
            <?php else: ?><span style="color:#444;">—</span><?php endif; ?>
          </td>
          <td>
            <a href="?page=leaderboard&pin=<?= $r['lb_id'] ?>" class="btn btn-sm <?= $r['is_pinned'] ? 'btn-warning' : 'btn-secondary' ?>">
              <?= $r['is_pinned'] ? '📌 Pinned' : '📍 Pin' ?>
            </a>
          </td>
          <td>
            <a href="?page=leaderboard&delete=<?= $r['lb_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus dari leaderboard?')">Hapus</a>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>