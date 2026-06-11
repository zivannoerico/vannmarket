<?php
// admin/pages/leaderboard.php
$msg = ''; $msg_type = '';

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
        header("Location: ?page=leaderboard&msg=Berhasil+ditambahkan."); exit;
    } else {
        $msg = 'Gagal: '.$conn->error; $msg_type = 'error';
    }
}

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM leaderboard_manual WHERE lb_id=".intval($_GET['delete']));
    header("Location: ?page=leaderboard&msg=Data+dihapus."); exit;
}

if (isset($_GET['pin'])) {
    $conn->query("UPDATE leaderboard_manual SET is_pinned = NOT is_pinned WHERE lb_id=".intval($_GET['pin']));
    header("Location: ?page=leaderboard"); exit;
}

if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

$games_list = $conn->query("SELECT game_id, game_name FROM games WHERE is_active=1 ORDER BY game_name");
$lb_rows    = $conn->query("SELECT lb.*, g.game_name, g.image_path FROM leaderboard_manual lb LEFT JOIN games g ON lb.fav_game_id=g.game_id ORDER BY lb.is_pinned DESC, lb.total_topup DESC");

// Rekap top up dari transaksi — untuk membantu admin
$rekap = $conn->query("
    SELECT
        t.game_account_id,
        COALESCE(u.username, '') AS username,
        COUNT(t.trx_id) AS total_trx,
        SUM(t.final_price) AS total_topup,
        MAX(t.created_at) AS last_topup,
        g.game_name AS fav_game
    FROM topup_transactions t
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN games g ON t.game_id = g.game_id
    WHERE t.status = 'success'
    GROUP BY t.game_account_id
    ORDER BY total_topup DESC
    LIMIT 20
");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- REKAP TOP UP -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2>📊 Rekap Top Up User <span style="font-size:13px;color:#888;font-weight:400;">(klik tombol + untuk tambah ke leaderboard)</span></h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>User ID Game</th><th>Nama Akun</th><th>Game</th><th>Total Top Up</th><th>Transaksi</th><th>Terakhir</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (!$rekap || $rekap->num_rows === 0): ?>
        <tr><td colspan="7" style="text-align:center;color:#555;padding:24px;">Belum ada transaksi sukses.</td></tr>
        <?php else: while ($r = $rekap->fetch_assoc()): ?>
        <tr>
          <td style="font-family:monospace;font-size:13px;"><?= esc($r['game_account_id']) ?></td>
          <td>
            <?php if ($r['username']): ?>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:26px;height:26px;background:#e60000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;">
                <?= strtoupper(substr($r['username'],0,1)) ?>
              </div>
              <span style="font-weight:600;"><?= esc($r['username']) ?></span>
            </div>
            <?php else: ?>
            <span style="color:#555;">Guest</span>
            <?php endif; ?>
          </td>
          <td style="font-size:13px;color:#aaa;"><?= esc($r['fav_game'] ?? '—') ?></td>
          <td style="font-weight:700;color:#e60000;"><?= formatRupiah($r['total_topup']) ?></td>
          <td style="color:#aaa;"><?= $r['total_trx'] ?>x</td>
          <td style="font-size:12px;color:#666;"><?= date('d M Y', strtotime($r['last_topup'])) ?></td>
          <td>
            <button class="btn btn-sm btn-success" onclick="fillForm('<?= esc(addslashes($r['game_account_id'])) ?>','<?= esc(addslashes($r['username'] ?: $r['game_account_id'])) ?>','<?= $r['total_topup'] ?>','<?= $r['total_trx'] ?>')">
              ➕ Tambah
            </button>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- FORM TAMBAH -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2>➕ Tambah ke Leaderboard</h2>
  </div>
  <form method="POST" id="lbForm">
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="field">
        <label>Nama Tampil *</label>
        <input type="text" name="display_name" id="f_display_name" placeholder="Nama yang ditampilkan di leaderboard" required>
      </div>
      <div class="field">
        <label>User ID Game</label>
        <input type="text" name="game_account_id" id="f_account_id" placeholder="ID akun game">
      </div>
      <div class="field">
        <label>Total Top Up (Rp)</label>
        <input type="number" name="total_topup" id="f_total_topup" placeholder="Contoh: 500000" min="0">
      </div>
      <div class="field">
        <label>Jumlah Transaksi</label>
        <input type="number" name="total_trx" id="f_total_trx" placeholder="Contoh: 10" min="0">
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
          <td><?= $r['device_type']==='pc' ? '💻 PC' : '📱 Mobile' ?></td>
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
            <a href="?page=leaderboard&delete=<?= $r['lb_id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Hapus dari leaderboard?')">Hapus</a>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function fillForm(account_id, display_name, total_topup, total_trx) {
    document.getElementById('f_account_id').value = account_id;
    document.getElementById('f_display_name').value = display_name;
    document.getElementById('f_total_topup').value = total_topup;
    document.getElementById('f_total_trx').value = total_trx;
    document.getElementById('lbForm').scrollIntoView({behavior:'smooth'});
}
</script>