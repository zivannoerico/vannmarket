<?php
// index.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Beranda';

$featured    = $conn->query("SELECT * FROM games WHERE is_featured=1 AND is_active=1 LIMIT 4");
$popular     = $conn->query("SELECT * FROM games WHERE is_popular=1 AND is_active=1 LIMIT 8");
$all_topup   = $conn->query("SELECT * FROM games WHERE category='topup' AND is_active=1");
$all_voucher = $conn->query("SELECT * FROM games WHERE category='voucher' AND is_active=1");

// Voucher aktif
$today = date('Y-m-d');
$active_vouchers = $conn->query("SELECT v.*, g.game_name FROM vouchers v
    LEFT JOIN games g ON v.game_id = g.game_id
    WHERE (v.valid_until IS NULL OR v.valid_until >= '$today')
    AND (v.valid_from IS NULL OR v.valid_from <= '$today')
    ORDER BY v.voucher_id DESC LIMIT 6");
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

<!-- VOUCHER AKTIF -->
<?php if ($active_vouchers && $active_vouchers->num_rows > 0): ?>
<section class="section" style="background:linear-gradient(135deg,#1a0000,#0f0f0f);padding:40px 0;">
  <div class="container">
    <div class="section-header">
      <h2>🎫 VOUCHER AKTIF</h2>
      <p>Gunakan kode voucher ini saat top up untuk mendapatkan diskon!</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-top:20px;">
      <?php while ($v = $active_vouchers->fetch_assoc()): ?>
      <div style="background:#1e1e1e;border:1px dashed #e60000;border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;width:4px;height:100%;background:#e60000;"></div>
        <div style="text-align:center;padding-left:8px;">
          <div style="font-size:26px;font-weight:900;color:#e60000;letter-spacing:2px;"><?= $v['discount_pct'] ?>%</div>
          <div style="font-size:10px;color:#888;">DISKON</div>
        </div>
        <div style="flex:1;">
          <div style="font-size:16px;font-weight:800;color:#fff;letter-spacing:1px;"><?= esc($v['voucher_code']) ?></div>
          <div style="font-size:12px;color:#888;margin-top:3px;">
            <?= $v['game_name'] ? 'Untuk: '.esc($v['game_name']) : 'Semua Game' ?>
          </div>
          <?php if ($v['valid_until']): ?>
          <div style="font-size:11px;color:#555;margin-top:3px;">Berlaku s/d <?= date('d M Y', strtotime($v['valid_until'])) ?></div>
          <?php endif; ?>
        </div>
        <button onclick="copyVoucher('<?= esc($v['voucher_code']) ?>', this)"
          style="background:#e60000;border:none;border-radius:8px;color:#fff;padding:8px 12px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
          Salin
        </button>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>
<?php endif; ?>

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
<section class="section" style="background:#111;padding:40px 0;">
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
        <?php $all_topup->data_seek(0); while ($g = $all_topup->fetch_assoc()): ?>
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
document.querySelectorAll('.tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display='none');
    btn.classList.add('active');
    document.getElementById('tab-'+btn.dataset.tab).style.display='block';
  });
});

function copyVoucher(code, btn) {
  navigator.clipboard.writeText(code).then(() => {
    const ori = btn.textContent;
    btn.textContent = '✓ Disalin!';
    btn.style.background = '#22c55e';
    setTimeout(() => { btn.textContent = ori; btn.style.background = '#e60000'; }, 2000);
  });
}
</script>
</body>
</html>