<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Leaderboard Top Up';

$rows = [];
$lb = $conn->query("SELECT lb.*, g.game_name, g.image_path FROM leaderboard_manual lb LEFT JOIN games g ON lb.fav_game_id=g.game_id ORDER BY lb.is_pinned DESC, lb.total_topup DESC LIMIT 10");
if ($lb) while ($r = $lb->fetch_assoc()) $rows[] = $r;

$stats = $conn->query("SELECT COUNT(DISTINCT game_account_id) AS total_users, SUM(final_price) AS total_volume, COUNT(trx_id) AS total_trx FROM topup_transactions WHERE status='success'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include __DIR__ . '/../components/header.php'; ?>
<style>
.lb-hero { background:linear-gradient(135deg,#1a0000 0%,#0f0f0f 50%,#001a0a 100%); padding:48px 0 40px; text-align:center; border-bottom:1px solid #222; position:relative; overflow:hidden; }
.lb-hero::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 50% -20%,rgba(230,0,0,.15) 0%,transparent 60%); }
.lb-hero h1 { font-size:32px; font-weight:800; position:relative; }
.lb-hero h1 span { color:var(--red); }
.lb-hero p { color:#888; font-size:14px; margin-top:6px; position:relative; }
.lb-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin:32px 0; }
.lb-stat-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:20px; text-align:center; }
.lb-stat-card .stat-val { font-size:26px; font-weight:800; color:var(--red); }
.lb-stat-card .stat-lbl { font-size:12px; color:#666; margin-top:4px; }
.podium { display:flex; align-items:flex-end; justify-content:center; gap:16px; margin:40px 0 48px; }
.podium-item { display:flex; flex-direction:column; align-items:center; flex:1; max-width:200px; }
.podium-card { width:100%; background:var(--bg-card); border:1px solid var(--border); border-radius:14px 14px 0 0; padding:20px 16px 24px; text-align:center; position:relative; transition:transform .2s; }
.podium-card:hover { transform:translateY(-4px); }
.podium-item:nth-child(2) .podium-card { background:linear-gradient(160deg,#2a1a00,#1a1200); border-color:#b8860b; box-shadow:0 0 24px rgba(255,215,0,.15); }
.podium-base { width:100%; border-radius:0 0 10px 10px; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:900; color:#fff; padding:12px; }
.podium-item:nth-child(1) .podium-base { background:#888; height:60px; }
.podium-item:nth-child(2) .podium-base { background:linear-gradient(135deg,#c8a415,#f5d020); height:80px; color:#000; }
.podium-item:nth-child(3) .podium-base { background:linear-gradient(135deg,#8B4513,#cd7f32); height:45px; }
.podium-rank { position:absolute; top:-14px; left:50%; transform:translateX(-50%); width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; border:2px solid #111; }
.rank-gold { background:#f5d020; color:#000; }
.rank-silver { background:#bbb; color:#000; }
.rank-bronze { background:#cd7f32; color:#fff; }
.podium-name { font-size:14px; font-weight:700; margin-bottom:4px; }
.podium-total { font-size:13px; color:var(--red); font-weight:700; }
.podium-trx { font-size:11px; color:#666; margin-top:2px; }
.podium-device { font-size:11px; background:#2a2a2a; padding:3px 8px; border-radius:20px; margin-top:6px; display:inline-block; color:#aaa; }
.lb-table-wrap { background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; margin-bottom:60px; }
.lb-table-head { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.lb-table-head h2 { font-size:16px; font-weight:700; }
table.lb-table { width:100%; border-collapse:collapse; }
.lb-table thead th { padding:12px 20px; font-size:12px; color:#555; font-weight:600; text-transform:uppercase; letter-spacing:.5px; text-align:left; border-bottom:1px solid #222; background:#161616; }
.lb-table tbody tr { border-bottom:1px solid #1e1e1e; transition:background .15s; }
.lb-table tbody tr:hover { background:#1e1e1e; }
.lb-table tbody tr:last-child { border-bottom:none; }
.lb-table td { padding:14px 20px; font-size:14px; vertical-align:middle; }
.rank-badge { width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; }
.rb-1 { background:linear-gradient(135deg,#c8a415,#f5d020); color:#000; }
.rb-2 { background:linear-gradient(135deg,#888,#ccc); color:#000; }
.rb-3 { background:linear-gradient(135deg,#8B4513,#cd7f32); color:#fff; }
.rb-other { background:#2a2a2a; color:#666; }
.user-cell { display:flex; align-items:center; gap:12px; }
.user-name { font-weight:600; }
.device-badge { display:inline-flex; align-items:center; gap:5px; background:#1e1e1e; border:1px solid #2a2a2a; padding:4px 10px; border-radius:20px; font-size:12px; color:#aaa; }
.device-mobile { border-color:#2a3a2a; color:#4caf88; background:rgba(76,175,136,.08); }
.device-pc { border-color:#1a2a3a; color:#64b5f6; background:rgba(100,181,246,.08); }
.lb-empty { text-align:center; padding:60px 20px; color:#555; }
</style>
</head>
<body>
<?php include __DIR__ . '/../components/navbar.php'; ?>

<div class="lb-hero">
  <div class="container">
    <h1>🏆 <span>Leaderboard</span> Top Up</h1>
    <p>Para sultan yang paling banyak top up di VANN Market</p>
  </div>
</div>

<div class="container">
  <div class="lb-stats">
    <div class="lb-stat-card">
      <div class="stat-val"><?= number_format($stats['total_users'] ?? 0) ?></div>
      <div class="stat-lbl">👥 Total Spender</div>
    </div>
    <div class="lb-stat-card">
      <div class="stat-val">Rp <?= number_format(($stats['total_volume'] ?? 0)/1000000, 1) ?>jt</div>
      <div class="stat-lbl">💰 Total Volume</div>
    </div>
    <div class="lb-stat-card">
      <div class="stat-val"><?= number_format($stats['total_trx'] ?? 0) ?></div>
      <div class="stat-lbl">🧾 Total Transaksi</div>
    </div>
  </div>

  <?php if (empty($rows)): ?>
  <div class="lb-empty">
    <div style="font-size:48px;">🏆</div>
    <p style="margin-top:12px;">Belum ada data leaderboard.<br>Admin belum mengatur leaderboard.</p>
  </div>
  <?php else: ?>

  <?php
  $top3_order = [];
  if (isset($rows[1])) $top3_order[] = ['data'=>$rows[1],'rank_cls'=>'rank-silver','crown'=>'🥈','base'=>'2'];
  if (isset($rows[0])) $top3_order[] = ['data'=>$rows[0],'rank_cls'=>'rank-gold',  'crown'=>'👑','base'=>'1'];
  if (isset($rows[2])) $top3_order[] = ['data'=>$rows[2],'rank_cls'=>'rank-bronze','crown'=>'🥉','base'=>'3'];
  ?>
  <div class="podium">
    <?php foreach ($top3_order as $p):
      $r = $p['data'];
      $device_icon  = $r['device_type']==='pc' ? '💻' : '📱';
      $device_label = $r['device_type']==='pc' ? 'PC/Desktop' : 'Mobile';
      $initial = strtoupper(substr($r['display_name'],0,1));
    ?>
    <div class="podium-item">
      <div class="podium-card">
        <div class="podium-rank <?= $p['rank_cls'] ?>"><?= $p['base'] ?></div>
        <div style="font-size:28px;margin-bottom:6px;"><?= $p['crown'] ?></div>
        <?php if ($r['image_path']): ?>
        <img src="/vannmarket/<?= esc($r['image_path']) ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid #333;margin:0 auto 10px;display:block;" onerror="this.style.display='none'">
        <?php else: ?>
        <div style="width:60px;height:60px;border-radius:50%;background:#e60000;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;margin:0 auto 10px;"><?= $initial ?></div>
        <?php endif; ?>
        <div class="podium-name"><?= esc($r['display_name']) ?></div>
        <div class="podium-total"><?= formatRupiah($r['total_topup']) ?></div>
        <div class="podium-trx"><?= $r['total_trx'] ?> transaksi</div>
        <div class="podium-device"><?= $device_icon ?> <?= $device_label ?></div>
      </div>
      <div class="podium-base"><?= $p['base'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="lb-table-wrap">
    <div class="lb-table-head">
      <h2>📊 Semua Peringkat</h2>
      <span style="font-size:12px;color:#555;">🔄 Diatur oleh admin</span>
    </div>
    <table class="lb-table">
      <thead>
        <tr><th>#</th><th>Pengguna</th><th>Total Top Up</th><th>Transaksi</th><th>Game Favorit</th><th>Perangkat</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $r):
          $rank = $i+1;
          $rb = $rank<=3 ? "rb-{$rank}" : 'rb-other';
          $device_cls = $r['device_type']==='pc' ? 'device-pc' : 'device-mobile';
          $device_icon = $r['device_type']==='pc' ? '💻' : '📱';
          $device_label = $r['device_type']==='pc' ? 'PC / Desktop' : 'Mobile';
        ?>
        <tr>
          <td><span class="rank-badge <?= $rb ?>"><?= $rank ?></span></td>
          <td>
            <div class="user-cell">
              <div style="width:38px;height:38px;border-radius:50%;background:#e60000;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0;">
                <?= strtoupper(substr($r['display_name'],0,1)) ?>
              </div>
              <div>
                <div class="user-name"><?= esc($r['display_name']) ?></div>
                <?php if ($r['is_pinned']): ?><div style="font-size:11px;color:#f5c518;">📌 Pinned</div><?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-weight:700;color:var(--red);font-size:15px;"><?= formatRupiah($r['total_topup']) ?></td>
          <td style="color:#888;"><?= $r['total_trx'] ?>x transaksi</td>
          <td>
            <?php if ($r['game_name']): ?>
            <div style="display:flex;align-items:center;gap:8px;">
              <?php if ($r['image_path']): ?><img src="/vannmarket/<?= esc($r['image_path']) ?>" style="width:28px;height:28px;border-radius:6px;object-fit:cover;" onerror="this.style.display='none'"><?php endif; ?>
              <span style="font-size:13px;color:#ccc;"><?= esc($r['game_name']) ?></span>
            </div>
            <?php else: ?><span style="color:#444;">—</span><?php endif; ?>
          </td>
          <td><span class="device-badge <?= $device_cls ?>"><?= $device_icon ?> <?= $device_label ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>