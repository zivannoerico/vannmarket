<?php
// admin/pages/transactions.php
$msg = ''; $msg_type = '';

// Hapus semua transaksi
if (isset($_GET['delete_all'])) {
    $conn->query("DELETE FROM topup_transactions");
    header("Location: ?page=transactions&msg=Semua+transaksi+berhasil+dihapus."); exit;
}

// Hapus 1 transaksi
if (isset($_GET['delete'])) {
    $tid = intval($_GET['delete']);
    $conn->query("DELETE FROM topup_transactions WHERE trx_id=$tid");
    header("Location: ?page=transactions&msg=Transaksi+dihapus."); exit;
}

// Update status
if (isset($_GET['status']) && isset($_GET['id'])) {
    $tid    = intval($_GET['id']);
    $status = in_array($_GET['status'], ['pending','success','failed','refunded']) ? $_GET['status'] : null;
    if ($status) {
        $stmt = $conn->prepare("UPDATE topup_transactions SET status=? WHERE trx_id=?");
        $stmt->bind_param("si", $status, $tid);
        $stmt->execute();
        header("Location: ?page=transactions&msg=Status+transaksi+diperbarui."); exit;
    }
}
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

// Filter
$filter_status = $_GET['filter_status'] ?? '';
$filter_game   = intval($_GET['filter_game'] ?? 0);
$search        = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($filter_status) $where .= " AND t.status='".$conn->real_escape_string($filter_status)."'";
if ($filter_game)   $where .= " AND t.game_id=$filter_game";
if ($search)        $where .= " AND (t.game_account_id LIKE '%".$conn->real_escape_string($search)."%' OR t.trx_id LIKE '%".$conn->real_escape_string($search)."%' OR u.username LIKE '%".$conn->real_escape_string($search)."%')";

$trxs = $conn->query("SELECT t.*, g.game_name, dp.package_name, pm.method_name, u.username
    FROM topup_transactions t
    LEFT JOIN games g ON t.game_id=g.game_id
    LEFT JOIN diamond_packages dp ON t.package_id=dp.package_id
    LEFT JOIN payment_methods pm ON t.payment_method_id=pm.method_id
    LEFT JOIN users u ON t.user_id=u.user_id
    $where ORDER BY t.created_at DESC LIMIT 200");

$total_revenue = $conn->query("SELECT COALESCE(SUM(final_price),0) as s FROM topup_transactions WHERE status='success'")->fetch_assoc()['s'];
$games_filter  = $conn->query("SELECT game_id, game_name FROM games ORDER BY game_name");

$status_map = ['pending'=>'Pending','success'=>'Sukses','failed'=>'Gagal','refunded'=>'Refund'];
$status_cls = ['pending'=>'badge-pending','success'=>'badge-success2','failed'=>'badge-inactive','refunded'=>'badge-active'];
?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FILTER BAR -->
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
  <form method="GET" style="display:contents;">
    <input type="hidden" name="page" value="transactions">
    <input type="text" name="q" value="<?= esc($search) ?>" placeholder="Cari ID / User ID / Nama..." style="padding:9px 14px;background:#222;border:1px solid #333;border-radius:8px;color:#fff;font-family:inherit;font-size:14px;outline:none;width:220px;">
    <select name="filter_status" style="padding:9px 14px;background:#222;border:1px solid #333;border-radius:8px;color:#fff;font-size:14px;outline:none;">
      <option value="">Semua Status</option>
      <?php foreach ($status_map as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filter_status===$k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select name="filter_game" style="padding:9px 14px;background:#222;border:1px solid #333;border-radius:8px;color:#fff;font-size:14px;outline:none;">
      <option value="0">Semua Game</option>
      <?php while ($gf = $games_filter->fetch_assoc()): ?>
      <option value="<?= $gf['game_id'] ?>" <?= $filter_game===$gf['game_id'] ? 'selected' : '' ?>><?= esc($gf['game_name']) ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="?page=transactions" class="btn btn-secondary btn-sm">Reset</a>
  </form>
  <div style="margin-left:auto;display:flex;align-items:center;gap:16px;">
    <div style="text-align:right;">
      <div style="font-size:12px;color:#888;">Total Revenue (Sukses)</div>
      <div style="font-size:18px;font-weight:700;color:#22c55e;"><?= formatRupiah($total_revenue) ?></div>
    </div>
    <a href="?page=transactions&delete_all=1" class="btn btn-danger btn-sm"
       onclick="return confirm('⚠️ Hapus SEMUA transaksi? Tindakan ini tidak bisa dibatalkan!')">
      🗑️ Hapus Semua
    </a>
  </div>
</div>

<!-- TABEL -->
<div class="section-card">
  <div class="section-header">
    <h2>📋 Transaksi (<?= $trxs->num_rows ?>)</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#ID</th><th>Game</th><th>Paket</th><th>Pembeli</th>
          <th>Bayar</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($trxs->num_rows === 0): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="icon">📋</div><p>Belum ada transaksi.</p></div></td></tr>
        <?php else: ?>
        <?php while ($t = $trxs->fetch_assoc()): ?>
        <tr>
          <td style="font-weight:700;">#<?= $t['trx_id'] ?></td>
          <td><?= esc($t['game_name'] ?? '—') ?></td>
          <td style="font-size:13px;"><?= esc($t['package_name'] ?? '—') ?></td>
          <td>
            <?php if ($t['username']): ?>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:28px;height:28px;background:#e60000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                <?= strtoupper(substr($t['username'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600;font-size:13px;"><?= esc($t['username']) ?></div>
                <div style="font-size:11px;color:#666;"><?= esc($t['game_account_id']) ?></div>
              </div>
            </div>
            <?php else: ?>
            <div style="font-family:monospace;font-size:13px;color:#aaa;"><?= esc($t['game_account_id']) ?></div>
            <div style="font-size:11px;color:#555;">Guest</div>
            <?php endif; ?>
          </td>
          <td><?= esc($t['method_name'] ?? '—') ?></td>
          <td style="font-weight:600;"><?= formatRupiah($t['final_price']) ?></td>
          <td>
            <span class="badge-status <?= $status_cls[$t['status']] ?? 'badge-inactive' ?>">
              <?= $status_map[$t['status']] ?? $t['status'] ?>
            </span>
          </td>
          <td style="font-size:12px;color:#888;"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
          <td>
            <div class="cell-actions" style="gap:4px;">
              <?php if ($t['status'] === 'pending'): ?>
              <a href="?page=transactions&id=<?= $t['trx_id'] ?>&status=success" class="btn btn-sm btn-success"
                 onclick="return confirm('Tandai sukses?')" title="Tandai Sukses">✓</a>
              <a href="?page=transactions&id=<?= $t['trx_id'] ?>&status=failed" class="btn btn-sm btn-danger"
                 onclick="return confirm('Tandai gagal?')" title="Tandai Gagal">✗</a>
              <?php elseif ($t['status'] === 'success'): ?>
              <a href="?page=transactions&id=<?= $t['trx_id'] ?>&status=refunded" class="btn btn-sm btn-warning"
                 onclick="return confirm('Proses refund?')" title="Refund">↩</a>
              <?php endif; ?>
              <a href="?page=transactions&delete=<?= $t['trx_id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus transaksi ini?')" title="Hapus">🗑️</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>