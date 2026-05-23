<?php
// admin/pages/dashboard.php
$stats_games    = $conn->query("SELECT COUNT(*) as c FROM games WHERE is_active=1")->fetch_assoc()['c'];
$stats_trx      = $conn->query("SELECT COUNT(*) as c FROM topup_transactions")->fetch_assoc()['c'];
$stats_revenue  = $conn->query("SELECT COALESCE(SUM(final_price),0) as c FROM topup_transactions WHERE status='success'")->fetch_assoc()['c'];
$stats_users    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$stats_pending  = $conn->query("SELECT COUNT(*) as c FROM topup_transactions WHERE status='pending'")->fetch_assoc()['c'];

// Transaksi terbaru
$recent = $conn->query("SELECT t.trx_id, t.game_account_id, t.final_price, t.status, t.created_at, g.game_name, dp.package_name
    FROM topup_transactions t
    LEFT JOIN games g ON t.game_id=g.game_id
    LEFT JOIN diamond_packages dp ON t.package_id=dp.package_id
    ORDER BY t.created_at DESC LIMIT 10");
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon red">🎮</div>
    <div>
      <div class="stat-label">Total Game</div>
      <div class="stat-value"><?= $stats_games ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">💰</div>
    <div>
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value" style="font-size:18px;"><?= formatRupiah($stats_revenue) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">📋</div>
    <div>
      <div class="stat-label">Total Transaksi</div>
      <div class="stat-value"><?= $stats_trx ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">⏳</div>
    <div>
      <div class="stat-label">Pending</div>
      <div class="stat-value"><?= $stats_pending ?></div>
    </div>
  </div>
</div>

<!-- QUICK ACTIONS -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
  <a href="?page=games" class="btn btn-primary">➕ Tambah Game</a>
  <a href="?page=packages" class="btn btn-success">💎 Atur Harga Diamond</a>
  <a href="?page=vouchers" class="btn btn-warning">🎫 Buat Voucher</a>
  <a href="/vannmarket/" target="_blank" class="btn btn-secondary">🌐 Lihat Website</a>
</div>

<!-- TRANSAKSI TERBARU -->
<div class="section-card">
  <div class="section-header">
    <h2>📋 Transaksi Terbaru</h2>
    <a href="?page=transactions" class="btn btn-sm btn-secondary">Lihat Semua</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#ID</th>
          <th>Game</th>
          <th>Paket</th>
          <th>User ID</th>
          <th>Total</th>
          <th>Status</th>
          <th>Tanggal</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recent->num_rows === 0): ?>
        <tr><td colspan="7" style="text-align:center;color:#888;padding:32px;">Belum ada transaksi.</td></tr>
        <?php else: ?>
        <?php while ($r = $recent->fetch_assoc()): ?>
        <?php
          $sc = ['pending'=>'badge-pending','success'=>'badge-success2','failed'=>'badge-inactive'];
          $sl = ['pending'=>'Pending','success'=>'Sukses','failed'=>'Gagal','refunded'=>'Refund'];
        ?>
        <tr>
          <td style="font-weight:600;">#<?= $r['trx_id'] ?></td>
          <td><?= esc($r['game_name'] ?? '-') ?></td>
          <td><?= esc($r['package_name'] ?? '-') ?></td>
          <td><?= esc($r['game_account_id']) ?></td>
          <td style="font-weight:600;"><?= formatRupiah($r['final_price']) ?></td>
          <td><span class="badge-status <?= $sc[$r['status']] ?? 'badge-inactive' ?>"><?= $sl[$r['status']] ?? $r['status'] ?></span></td>
          <td style="color:#888;font-size:13px;"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
