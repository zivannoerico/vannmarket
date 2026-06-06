<?php
// public/game.php — Halaman Detail & Top Up Game
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

$game_id = intval($_GET['id'] ?? 0);
if (!$game_id) redirect('/vannmarket/');

// Ambil data game
$stmt = $conn->prepare("SELECT * FROM games WHERE game_id=? AND is_active=1");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();
if (!$game) redirect('/vannmarket/');

// Ambil paket diamond
$pkgs_stmt = $conn->prepare("SELECT * FROM diamond_packages WHERE game_id=? AND is_active=1 ORDER BY category, price ASC");
$pkgs_stmt->bind_param("i", $game_id);
$pkgs_stmt->execute();
$packages_result = $pkgs_stmt->get_result();

$packages = ['topup' => [], 'membership' => []];
while ($p = $packages_result->fetch_assoc()) {
    $key = ($p['category'] === 'membership') ? 'membership' : 'topup';
    $packages[$key][] = $p;
}

// Ambil metode pembayaran
$payments = $conn->query("SELECT * FROM payment_methods WHERE is_active=1 ORDER BY method_type, method_name");

// Ambil voucher aktif
$today_date = date('Y-m-d');
$voucher_stmt = $conn->prepare("
    SELECT * FROM vouchers
    WHERE is_active = 1
      AND (game_id = ? OR game_id IS NULL)
      AND (valid_from IS NULL OR valid_from <= ?)
      AND (valid_until IS NULL OR valid_until >= ?)
      AND (max_usage IS NULL OR used_count < max_usage)
    ORDER BY discount_pct DESC
");
$voucher_stmt->bind_param("iss", $game_id, $today_date, $today_date);
$voucher_stmt->execute();
$available_vouchers = $voucher_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Top Up ' . $game['game_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include __DIR__ . '/../components/header.php'; ?></head>
<body>
<?php include __DIR__ . '/../components/navbar.php'; ?>

<!-- GAME HERO -->
<section class="game-hero">
  <div class="container">
    <div class="game-hero-inner">
      <div class="game-hero-cover">
        <img src="/vannmarket/<?= esc($game['image_path']) ?>" alt="<?= esc($game['game_name']) ?>" onerror="this.src='/vannmarket/assets/image/placeholder.png'">
      </div>
      <div class="game-hero-info">
        <h1>TOP UP <?= esc(strtoupper($game['game_name'])) ?></h1>
        <p class="publisher"><?= esc($game['publisher']) ?></p>
        <div class="game-badges">
          <span class="badge"><i class="fas fa-bolt"></i> Proses Cepat</span>
          <span class="badge"><i class="fas fa-headset"></i> Layanan 24/7</span>
          <span class="badge"><i class="fas fa-shield-alt"></i> Pembayaran Aman</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TOPUP FORM -->
<div class="container">
  <div class="topup-layout">
    <!-- LEFT COLUMN -->
    <div class="topup-left">

      <!-- STEP 1: PILIH NOMINAL -->
      <div class="step-card">
        <div class="step-head">
          <span class="step-num">1</span>
          <h3>Pilih Nominal</h3>
        </div>
        <div class="step-body">
          <?php if (!empty($packages['topup'])): ?>
          <h4>Top Up</h4>
          <div class="packages-grid" id="pkgGrid">
            <?php foreach ($packages['topup'] as $p): ?>
            <button class="pkg-btn" data-id="<?= $p['package_id'] ?>" data-price="<?= $p['price'] ?>" data-name="<?= esc($p['package_name']) ?>">
              <span class="pkg-amount"><?= esc($p['package_name']) ?></span>
              <span class="pkg-price"><?= formatRupiah($p['price']) ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($packages['membership'])): ?>
          <h4 style="margin-top:18px;">Membership</h4>
          <div class="packages-grid">
            <?php foreach ($packages['membership'] as $p): ?>
            <button class="pkg-btn" data-id="<?= $p['package_id'] ?>" data-price="<?= $p['price'] ?>" data-name="<?= esc($p['package_name']) ?>">
              <span class="pkg-amount"><?= esc($p['package_name']) ?></span>
              <span class="pkg-price"><?= formatRupiah($p['price']) ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- STEP 2: DATA AKUN -->
      <div class="step-card">
        <div class="step-head">
          <span class="step-num">2</span>
          <h3>Masukkan Data Akun</h3>
        </div>
        <div class="step-body">
          <div class="field-group">
            <label>User ID / AID</label>
            <input type="text" id="accountId" placeholder="Masukkan User ID akun game kamu">
            <p class="field-hint">Pastikan ID yang dimasukkan benar sebelum melanjutkan.</p>
          </div>
        </div>
      </div>

      <!-- STEP 3: VOUCHER -->
      <div class="step-card">
        <div class="step-head">
          <span class="step-num">3</span>
          <h3>Kode Voucher (Opsional)</h3>
        </div>
        <div class="step-body">

          <?php if (!empty($available_vouchers)): ?>
          <p style="font-size:13px;color:#aaa;margin-bottom:8px;">🎫 Voucher tersedia:</p>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
            <?php foreach ($available_vouchers as $av): ?>
            <button type="button"
              onclick="pilihVoucher('<?= esc($av['voucher_code']) ?>')"
              style="background:#1e1e1e;border:1.5px dashed #e60000;border-radius:8px;padding:8px 14px;cursor:pointer;text-align:left;">
              <span style="font-weight:700;color:#e60000;letter-spacing:1px;"><?= esc($av['voucher_code']) ?></span>
              <span style="font-size:12px;color:#aaa;margin-left:8px;">Diskon <?= $av['discount_pct'] ?>%</span>
              <?php if ($av['valid_until']): ?>
              <span style="font-size:11px;color:#888;display:block;margin-top:2px;">s/d <?= date('d M Y', strtotime($av['valid_until'])) ?></span>
              <?php endif; ?>
            </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="field-group" style="display:flex;gap:10px;">
            <input type="text" id="voucherCode" placeholder="Masukkan kode voucher" style="flex:1;">
            <button id="applyVoucher" style="padding:12px 18px;background:var(--red);color:#fff;border:none;border-radius:8px;font-weight:600;white-space:nowrap;">Pakai</button>
          </div>
          <p id="voucherMsg" class="field-hint"></p>
        </div>
      </div>

      <!-- STEP 4: PEMBAYARAN -->
      <div class="step-card">
        <div class="step-head">
          <span class="step-num">4</span>
          <h3>Pilih Metode Pembayaran</h3>
        </div>
        <div class="step-body">
          <?php
          $payment_types = ['e-wallet' => 'E-Wallet', 'bank' => 'Transfer Bank', 'minimarket' => 'Minimarket'];
          $payments_data = [];
          while ($pm = $payments->fetch_assoc()) $payments_data[] = $pm;
          foreach ($payment_types as $type => $label):
            $filtered = array_filter($payments_data, fn($p) => $p['method_type'] === $type);
            if (empty($filtered)) continue;
          ?>
          <h4 style="margin-bottom:10px;"><?= $label ?></h4>
          <div class="pay-grid" style="margin-bottom:16px;">
            <?php foreach ($filtered as $pm): ?>
            <button class="pay-btn" data-id="<?= $pm['method_id'] ?>" data-name="<?= esc($pm['method_name']) ?>">
              <?= esc($pm['method_name']) ?>
            </button>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="topup-right">
      <div class="summary-card">
        <h3>Ringkasan Pesanan</h3>
        <div id="summaryContent">
          <p class="summary-empty">Belum ada item dipilih.</p>
        </div>
        <button class="btn-order" id="orderBtn" disabled onclick="submitOrder()">
          🛒 Pesan Sekarang!
        </button>
      </div>
      <div class="rating-card">
        <h3>Ulasan & Rating</h3>
        <span class="rating-number">5.0</span>
        <div class="stars">★★★★★</div>
        <p class="rating-count">Berdasarkan ratusan transaksi</p>
      </div>
      <div class="help-card">
        <h3>Butuh Bantuan?</h3>
        <p style="font-size:13px;color:#aaa;margin-bottom:12px;">Tim kami siap membantu 24/7</p>
        <button class="btn-contact" onclick="window.open('https://wa.me/62xxxxxxxxxx','_blank')">
          💬 Hubungi Admin
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
let selectedPkg = null;
let selectedPay = null;
let voucherDiscount = 0;

// Package selection
document.querySelectorAll('.pkg-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.pkg-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedPkg = {
      id: btn.dataset.id,
      name: btn.dataset.name,
      price: parseFloat(btn.dataset.price)
    };
    updateSummary();
  });
});

// Payment selection
document.querySelectorAll('.pay-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedPay = { id: btn.dataset.id, name: btn.dataset.name };
    updateSummary();
  });
});

// Pilih voucher dari daftar
function pilihVoucher(code) {
  document.getElementById('voucherCode').value = code;
  document.getElementById('applyVoucher').click();
}

// Apply voucher
document.getElementById('applyVoucher').addEventListener('click', async () => {
  const code = document.getElementById('voucherCode').value.trim();
  const msg = document.getElementById('voucherMsg');
  if (!code) { msg.textContent = ''; return; }
  if (!selectedPkg) { msg.style.color='#e57373'; msg.textContent='Pilih nominal terlebih dahulu.'; return; }

  const res = await fetch(`/vannmarket/public/api/voucher.php?code=${encodeURIComponent(code)}&game_id=<?= $game_id ?>`);
  const data = await res.json();
  if (data.valid) {
    voucherDiscount = data.discount_pct;
    msg.style.color = '#4caf88';
    msg.textContent = `✅ Voucher berhasil! Diskon ${data.discount_pct}%`;
  } else {
    voucherDiscount = 0;
    msg.style.color = '#e57373';
    msg.textContent = data.message || 'Voucher tidak valid.';
  }
  updateSummary();
});

// Update summary
function updateSummary() {
  const el = document.getElementById('summaryContent');
  const btn = document.getElementById('orderBtn');
  if (!selectedPkg) {
    el.innerHTML = '<p class="summary-empty">Belum ada item dipilih.</p>';
    btn.disabled = true;
    return;
  }
  const disc = (selectedPkg.price * voucherDiscount / 100);
  const final = selectedPkg.price - disc;
  el.innerHTML = `
    <div class="summary-row"><span>Item</span><span>${selectedPkg.name}</span></div>
    <div class="summary-row"><span>Harga</span><span>${formatRp(selectedPkg.price)}</span></div>
    ${disc > 0 ? `<div class="summary-row"><span>Diskon</span><span style="color:#4caf88;">-${formatRp(disc)}</span></div>` : ''}
    ${selectedPay ? `<div class="summary-row"><span>Pembayaran</span><span>${selectedPay.name}</span></div>` : ''}
    <div class="summary-row total"><span>Total</span><span>${formatRp(final)}</span></div>
  `;
  btn.disabled = !selectedPay;
}

function formatRp(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

async function submitOrder() {
  const accountId = document.getElementById('accountId').value.trim();
  if (!accountId) { alert('Masukkan User ID akun game kamu!'); return; }
  if (!selectedPkg || !selectedPay) return;

  const disc = (selectedPkg.price * voucherDiscount / 100);
  const final = selectedPkg.price - disc;

  const konfirmasi = window.confirm(
    `Konfirmasi Pesanan:\n\nItem: ${selectedPkg.name}\nUser ID: ${accountId}\nPembayaran: ${selectedPay.name}\nTotal: ${formatRp(final)}\n\nLanjutkan?`
  );
  if (!konfirmasi) return;

  const form = new FormData();
  form.append('game_id', '<?= $game_id ?>');
  form.append('package_id', selectedPkg.id);
  form.append('payment_method_id', selectedPay.id);
  form.append('game_account_id', accountId);
  form.append('voucher_code', document.getElementById('voucherCode').value.trim());

  const res = await fetch('/vannmarket/public/api/order.php', { method: 'POST', body: form });
  const data = await res.json();

  if (data.success) {
    alert('✅ ' + data.message + '\n\nNo. Transaksi: ' + data.trx_id);
    window.location = '/vannmarket/public/transactions.php?id=' + data.trx_id;
  } else {
    alert('❌ ' + (data.message || 'Gagal memproses pesanan.'));
  }
}
</script>
</body>
</html>