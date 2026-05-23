<?php
// public/api/order.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$game_id           = intval($_POST['game_id'] ?? 0);
$package_id        = intval($_POST['package_id'] ?? 0);
$payment_method_id = intval($_POST['payment_method_id'] ?? 0);
$game_account_id   = trim($_POST['game_account_id'] ?? '');
$voucher_code      = trim($_POST['voucher_code'] ?? '');

if (!$game_id || !$package_id || !$payment_method_id || !$game_account_id) {
    echo json_encode(['success'=>false,'message'=>'Data tidak lengkap.']); exit;
}

// Ambil harga paket
$pkg = $conn->prepare("SELECT * FROM diamond_packages WHERE package_id=? AND game_id=? AND is_active=1");
$pkg->bind_param("ii", $package_id, $game_id);
$pkg->execute();
$package = $pkg->get_result()->fetch_assoc();
if (!$package) { echo json_encode(['success'=>false,'message'=>'Paket tidak ditemukan.']); exit; }

$base_price = floatval($package['price']);
$discount   = 0;
$voucher_id = null;

// Voucher
if ($voucher_code) {
    $vs = $conn->prepare("SELECT * FROM vouchers WHERE voucher_code=? AND (game_id=? OR game_id IS NULL)");
    $vs->bind_param("si", $voucher_code, $game_id);
    $vs->execute();
    $v = $vs->get_result()->fetch_assoc();
    if ($v) {
        $today = date('Y-m-d');
        if ((!$v['valid_from'] || $today >= $v['valid_from']) && (!$v['valid_until'] || $today <= $v['valid_until'])) {
            $discount   = $base_price * floatval($v['discount_pct']) / 100;
            $voucher_id = $v['voucher_id'];
        }
    }
}

$final_price = $base_price - $discount;

// Simpan transaksi
$stmt = $conn->prepare("INSERT INTO topup_transactions (game_id, package_id, game_account_id, payment_method_id, voucher_id, base_price, discount_amount, final_price, status, created_at) VALUES (?,?,?,?,?,?,?,?,'pending',NOW())");
$stmt->bind_param("iisiiddd", $game_id, $package_id, $game_account_id, $payment_method_id, $voucher_id, $base_price, $discount, $final_price);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.','trx_id'=>$conn->insert_id]);
} else {
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan pesanan: '.$conn->error]);
}
