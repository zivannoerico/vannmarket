<?php
// admin/includes/sidebar.php
// Hitung user baru (belum dibaca) dalam 24 jam terakhir
$new_users_count = 0;
if (isset($conn)) {
    $nu = $conn->query("SELECT COUNT(*) as c FROM users WHERE created_at >= NOW() - INTERVAL 24 HOUR");
    $new_users_count = $nu ? intval($nu->fetch_assoc()['c']) : 0;
}
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">🎮</span>
    <span class="brand-name">VANN Admin</span>
  </div>
  <nav class="sidebar-nav">
    <?php
    $page = $_GET['page'] ?? 'dashboard';
    $menu = [
      ['icon'=>'📊', 'label'=>'Dashboard',    'page'=>'dashboard', 'badge'=>0],
      ['icon'=>'🎮', 'label'=>'Kelola Game',   'page'=>'games',     'badge'=>0],
      ['icon'=>'💎', 'label'=>'Harga Diamond', 'page'=>'packages',  'badge'=>0],
      ['icon'=>'💳', 'label'=>'Metode Bayar',  'page'=>'payments',  'badge'=>0],
      ['icon'=>'🎫', 'label'=>'Voucher',        'page'=>'vouchers',  'badge'=>0],
      ['icon'=>'📋', 'label'=>'Transaksi',      'page'=>'transactions','badge'=>0],
      ['icon'=>'👥', 'label'=>'Users',          'page'=>'users',     'badge'=>$new_users_count],
      ['icon'=>'🏆', 'label'=>'Leaderboard',    'page'=>'leaderboard','badge'=>0],
    ];
    foreach ($menu as $m) {
      $active = ($page === $m['page']) ? 'active' : '';
      $badge  = $m['badge'] > 0 ? "<span class='notif-badge'>{$m['badge']}</span>" : '';
      echo "<a href='/vannmarket/admin/?page={$m['page']}' class='nav-item {$active}'>
        <span class='nav-icon'>{$m['icon']}</span>
        <span>{$m['label']}</span>
        {$badge}
      </a>";
    }
    ?>
  </nav>
  <div class="sidebar-footer">
    <a href="/vannmarket/admin/logout.php" class="nav-item logout">
      <span class="nav-icon">🚪</span>
      <span>Keluar</span>
    </a>
  </div>
</aside>