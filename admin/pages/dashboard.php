<?php
// admin/pages/dashboard.php
$stats_games   = $conn->query("SELECT COUNT(*) as c FROM games WHERE is_active=1")->fetch_assoc()['c'];
$stats_trx     = $conn->query("SELECT COUNT(*) as c FROM topup_transactions")->fetch_assoc()['c'];
$stats_revenue = $conn->query("SELECT COALESCE(SUM(final_price),0) as c FROM topup_transactions WHERE status='success'")->fetch_assoc()['c'];
$stats_users   = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$stats_pending = $conn->query("SELECT COUNT(*) as c FROM topup_transactions WHERE status='pending'")->fetch_assoc()['c'];

// User baru 24 jam terakhir
$new_users = $conn->query("SELECT user_id, username, email, phone_number, created_at FROM users WHERE created_at >= NOW() - INTERVAL 24 HOUR ORDER BY created_at DESC");

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
    <div class="stat-icon yellow">👥</div>
    <div>
      <div class="stat-label">Total Users</div>
      <div class="stat-value"><?= $stats_users ?></div>
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

<!-- NOTIFIKASI USER BARU -->
<?php if ($new_users && $new_users->num_rows > 0): ?>
<div class="section-card" style="margin-bottom:24px;border-left:3px solid #e60000;">
  <div class="section-header">
    <h2>🔔 User Baru <span style="background:#e60000;color:#fff;font-size:12px;padding:2px 10px;border-radius:10px;margin-left:8px;"><?= $new_users->num_rows ?> baru</span></h2>
    <span style="font-size:12px;color:#888;">Dalam 24 jam terakhir</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Username</th><th>Email</th><th>No. HP</th><th>Daftar</th></tr>
      </thead>
      <tbody>
        <?php while ($u = $new_users->fetch_assoc()): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:32px;height:32px;background:#e60000;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                <?= strtoupper(substr($u['username'],0,1)) ?>
              </div>
              <strong><?= esc($u['username']) ?></strong>
            </div>
          </td>
          <td style="color:#aaa;"><?= esc($u['email'] ?: '—') ?></td>
          <td style="color:#aaa;"><?= esc($u['phone_number'] ?: '—') ?></td>
          <td style="font-size:13px;color:#888;"><?= date('d M Y H:i', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- TRANSAKSI TERBARU -->
<div class="section-card">
  <div class="section-header">
    <h2>📋 Transaksi Terbaru</h2>
    <a href="?page=transactions" class="btn btn-sm btn-secondary">Lihat Semua</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#ID</th><th>Game</th><th>Paket</th><th>User ID</th><th>Total</th><th>Status</th><th>Tanggal</th></tr>
      </thead>
      <tbody>
        <?php if ($recent->num_rows === 0): ?>
        <tr><td colspan="7" style="text-align:center;color:#888;padding:32px;">Belum ada transaksi.</td></tr>
        <?php else: ?>
        <?php while ($r = $recent->fetch_assoc()):
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
    <script>
setInterval(function() {
    fetch('/vannmarket/public/api/check_new_users.php')
        .then(r => r.json())
        .then(data => {
            if (data.count > 0) {
                // Update badge di sidebar
                const badge = document.querySelector('.nav-item[href*="users"] .notif-badge');
                if (badge) badge.textContent = data.count;
                
                // Update section user baru
                location.reload();
            }
        })
        .catch(() => {});
}, 5000);
</script>
  </div>
</div>