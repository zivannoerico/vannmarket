<?php
// admin/index.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'dashboard');
$allowed = ['dashboard','games','packages','payments','vouchers','transactions','users','leaderboard'];
if (!in_array($page, $allowed)) $page = 'dashboard';

$page_file = __DIR__ . "/pages/{$page}.php";
if (!file_exists($page_file)) $page = 'dashboard';

$page_titles = [
  'dashboard'    => 'Dashboard',
  'games'        => 'Kelola Game',
  'packages'     => 'Harga Diamond',
  'payments'     => 'Metode Pembayaran',
  'vouchers'     => 'Voucher',
  'transactions' => 'Transaksi',
  'users'        => 'Kelola Users',
  'leaderboard'  => 'Leaderboard',
];
$pageTitle = $page_titles[$page] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($pageTitle) ?> | Admin VANN Market</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/vannmarket/admin/assets/admin.css">
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-wrap">
  <div class="topbar">
    <h1><?= esc($pageTitle) ?></h1>
    <div class="topbar-user">
      <span>👤 <strong><?= esc($_SESSION['admin_user'] ?? 'Admin') ?></strong></span>
      <a href="/vannmarket/admin/logout.php" class="btn btn-sm btn-secondary">Logout</a>
    </div>
  </div>

  <div class="content">
    <?php include __DIR__ . "/pages/{$page}.php"; ?>
  </div>
</div>

<!-- Auto cek user baru setiap 30 detik -->
<script>
setInterval(function() {
    fetch('/vannmarket/public/api/check_new_users.php')
        .then(r => r.json())
        .then(data => {
            const badge = document.querySelector('a[href*="page=users"] .notif-badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const userLink = document.querySelector('a[href*="page=users"]');
                    if (userLink) {
                        const b = document.createElement('span');
                        b.className = 'notif-badge';
                        b.textContent = data.count;
                        userLink.appendChild(b);
                    }
                }
            } else {
                if (badge) badge.remove();
            }
        })
        .catch(() => {});
}, 5000);
</script>

</body>
</html>