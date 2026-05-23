<?php // admin/includes/sidebar.php ?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">🎮</span>
    <span class="brand-name">VANN Admin</span>
  </div>
  <nav class="sidebar-nav">
    <?php
    $current = basename($_SERVER['PHP_SELF']);
    $page    = $_GET['page'] ?? 'dashboard';

    $menu = [
      ['icon'=>'📊','label'=>'Dashboard',    'page'=>'dashboard'],
      ['icon'=>'🎮','label'=>'Kelola Game',   'page'=>'games'],
      ['icon'=>'💎','label'=>'Harga Diamond', 'page'=>'packages'],
      ['icon'=>'💳','label'=>'Metode Bayar',  'page'=>'payments'],
      ['icon'=>'🎫','label'=>'Voucher',        'page'=>'vouchers'],
      ['icon'=>'📋','label'=>'Transaksi',      'page'=>'transactions'],
      ['icon'=>'👥','label'=>'Users',          'page'=>'users'],
    ];
    foreach ($menu as $m) {
      $active = ($page === $m['page']) ? 'active' : '';
      echo "<a href='/vannmarket/admin/?page={$m['page']}' class='nav-item {$active}'>
        <span class='nav-icon'>{$m['icon']}</span>
        <span>{$m['label']}</span>
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
