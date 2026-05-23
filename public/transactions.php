<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

$pageTitle = 'Cek Transaksi';
$trx_id = intval($_GET['id'] ?? 0);
$trx = null;

if ($trx_id) {
    $stmt = $conn->prepare("SELECT t.*, g.game_name, g.image_path, dp.package_name, pm.method_name
        FROM topup_transactions t
        LEFT JOIN games g ON t.game_id=g.game_id
        LEFT JOIN diamond_packages dp ON t.package_id=dp.package_id
        LEFT JOIN payment_methods pm ON t.payment_method_id=pm.method_id
        WHERE t.trx_id=?");
    $stmt->bind_param("i", $trx_id);
    $stmt->execute();
    $trx = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include __DIR__ . '/../components/header.php'; ?></head>
<body>
<?php include __DIR__ . '/../components/navbar.php'; ?>

<div class="container" style="padding:40px 20px; max-width:700px;">
  <h2 style="margin-bottom:24px;">Cek Transaksi</h2>

  <div style="background:#1e1e1e;border-radius:12px;padding:24px;margin-bottom:24px;">
    <label style="display:block;font-size:14px;margin-bottom:8px;color:#aaa;">Nomor Transaksi</label>
    <form method="GET" style="display:flex;gap:10px;">
      <input type="number" name="id" value="<?= $trx_id ?: '' ?>" placeholder="Masukkan nomor transaksi..."
        style="flex:1;padding:12px 14px;background:#2a2a2a;border:1px solid #333;border-radius:8px;color:#fff;font-size:14px;font-family:inherit;outline:none;">
      <button type="submit" style="padding:12px 22px;background:#e60000;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Cek</button>
    </form>
  </div>

  <?php if ($trx_id && !$trx): ?>
  <div class="alert alert-error">Transaksi #<?= $trx_id ?> tidak ditemukan.</div>
  <?php endif; ?>

  <?php if ($trx): ?>
  <?php
    $status_color = ['pending'=>'#f5a623','success'=>'#4caf50','failed'=>'#e57373','refunded'=>'#64b5f6'];
    $status_label = ['pending'=>'Menunggu Pembayaran','success'=>'Berhasil','failed'=>'Gagal','refunded'=>'Refund'];
    $sc = $status_color[$trx['status']] ?? '#aaa';
    $sl = $status_label[$trx['status']] ?? $trx['status'];
  ?>
  <div style="background:#1e1e1e;border-radius:12px;padding:24px;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #333;">
      <img src="/vannmarket/<?= esc($trx['image_path'] ?? '') ?>" style="width:60px;height:60px;border-radius:10px;object-fit:cover;" onerror="this.src='/vannmarket/assets/image/placeholder.png'">
      <div>
        <h3 style="font-size:17px;"><?= esc($trx['game_name'] ?? '-') ?></h3>
        <p style="font-size:13px;color:#aaa;"><?= esc($trx['package_name'] ?? '-') ?></p>
      </div>
      <div style="margin-left:auto;text-align:right;">
        <span style="background:<?= $sc ?>22;color:<?= $sc ?>;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;"><?= $sl ?></span>
      </div>
    </div>

    <?php
    $rows = [
      ['No. Transaksi', '#' . $trx['trx_id']],
      ['User ID Game', $trx['game_account_id']],
      ['Metode Bayar', $trx['method_name'] ?? '-'],
      ['Harga Dasar', formatRupiah($trx['base_price'])],
    ];
    if ($trx['discount_amount'] > 0)
      $rows[] = ['Diskon', '-' . formatRupiah($trx['discount_amount'])];
    $rows[] = ['Total Bayar', formatRupiah($trx['final_price'])];
    $rows[] = ['Tanggal', date('d M Y H:i', strtotime($trx['created_at']))];
    ?>

    <?php foreach ($rows as [$label, $val]): ?>
    <div style="display:flex;justify-content:space-between;font-size:14px;padding:10px 0;border-bottom:1px solid #2a2a2a;">
      <span style="color:#aaa;"><?= $label ?></span>
      <span style="font-weight:500;"><?= esc($val) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
