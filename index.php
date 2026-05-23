<?php
// index.php — Halaman Utama VANN Market
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$pageTitle = 'Beranda';

// Ambil game populer dari DB
$featured = $conn->query("SELECT * FROM games WHERE is_featured=1 AND is_active=1 LIMIT 4");
$popular  = $conn->query("SELECT * FROM games WHERE is_popular=1 AND is_active=1 LIMIT 8");
$all_topup = $conn->query("SELECT * FROM games WHERE category='topup' AND is_active=1");
$all_voucher = $conn->query("SELECT * FROM games WHERE category='voucher' AND is_active=1");
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include __DIR__ . '/components/header.php'; ?></head>
<body>
<?php include __DIR__ . '/components/navbar.php'; ?>

<!-- BANNER -->
<div class="banner container">
  <img src="/vannmarket/assets/image/bannervann.png" alt="VANN MARKET Promo">
</div>

<!-- REKOMENDASI -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>✨ REKOMENDASI</h2>
      <p>Game pilihan terbaik dari kami untuk kamu</p>
    </div>
    <div class="game-cards">
      <?php while ($g = $featured->fetch_assoc()): ?>
      <a href="/vannmarket/public/game.php?id=<?= $g['game_id'] ?>" class="game-card">
        <img src="/vannmarket/<?= esc($g['image_path']) ?>" alt="<?= esc($g['game_name']) ?>" onerror="this.src='/vannmarket/assets/image/placeholder.png'">
        <div class="game-card-info">
          <h3><?= esc($g['game_name']) ?></h3>
          <p><?= esc($g['publisher']) ?></p>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- POPULER -->
<section class="section" style="background:#111; padding:40px 0;">
  <div class="container">
    <div class="section-header">
      <h2>🔥 POPULER SEKARANG!</h2>
      <p>Game yang paling banyak dibeli hari ini</p>
    </div>
    <div class="game-cards">
      <?php while ($g = $popular->fetch_assoc()): ?>
      <a href="/vannmarket/public/game.php?id=<?= $g['game_id'] ?>" class="game-card">
        <img src="/vannmarket/<?= esc($g['image_path']) ?>" alt="<?= esc($g['game_name']) ?>" onerror="this.src='/vannmarket/assets/image/placeholder.png'">
        <div class="game-card-info">
          <h3><?= esc($g['game_name']) ?></h3>
          <p><?= esc($g['publisher']) ?></p>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- SEMUA GAME DENGAN TAB -->
<section class="section">
  <div class="container">
    <div class="tabs">
      <button class="tab active" data-tab="topup">Top Up</button>
      <button class="tab" data-tab="voucher">Voucher</button>
      <button class="tab" data-tab="via_login">Via Login</button>
      <button class="tab" data-tab="live_app">Live App</button>
    </div>

    <div id="tab-topup" class="tab-panel">
      <div class="game-grid">
        <?php
        $all_topup->data_seek(0);
        while ($g = $all_topup->fetch_assoc()):
        ?>
        <a href="/vannmarket/public/game.php?id=<?= $g['game_id'] ?>" class="game-item">
          <img src="/vannmarket/<?= esc($g['image_path']) ?>" alt="<?= esc($g['game_name']) ?>" onerror="this.src='/vannmarket/assets/image/placeholder.png'">
          <div class="game-item-name"><?= esc($g['game_name']) ?></div>
        </a>
        <?php endwhile; ?>
      </div>
    </div>

    <div id="tab-voucher" class="tab-panel" style="display:none;">
      <div class="game-grid">
        <?php while ($g = $all_voucher->fetch_assoc()): ?>
        <a href="/vannmarket/public/game.php?id=<?= $g['game_id'] ?>" class="game-item">
          <img src="/vannmarket/<?= esc($g['image_path']) ?>" alt="<?= esc($g['game_name']) ?>" onerror="this.src='/vannmarket/assets/image/placeholder.png'">
          <div class="game-item-name"><?= esc($g['game_name']) ?></div>
        </a>
        <?php endwhile; ?>
      </div>
    </div>

    <div id="tab-via_login" class="tab-panel" style="display:none;">
      <p style="color:#aaa;text-align:center;padding:40px 0;">Belum ada game Via Login tersedia</p>
    </div>
    <div id="tab-live_app" class="tab-panel" style="display:none;">
      <p style="color:#aaa;text-align:center;padding:40px 0;">Belum ada game Live App tersedia</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>

<script>
// Tab switching
document.querySelectorAll('.tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).style.display = 'block';
  });
});
</script>
</body>
</html>
