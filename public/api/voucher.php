<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$code    = strtoupper(trim($_GET['code'] ?? ''));
$game_id = intval($_GET['game_id'] ?? 0);

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'Kode voucher kosong.']); exit;
}

$stmt = $conn->prepare("SELECT * FROM vouchers WHERE voucher_code=? AND (game_id=? OR game_id IS NULL) AND is_active=1");
$stmt->bind_param("si", $code, $game_id);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();

if (!$v) {
    echo json_encode(['valid' => false, 'message' => 'Kode voucher tidak ditemukan atau tidak aktif.']); exit;
}

$today = date('Y-m-d');

if ($v['valid_from'] && $today < $v['valid_from']) {
    echo json_encode(['valid' => false, 'message' => 'Voucher belum aktif.']); exit;
}
if ($v['valid_until'] && $today > $v['valid_until']) {
    echo json_encode(['valid' => false, 'message' => 'Voucher sudah kedaluwarsa.']); exit;
}
if ($v['max_usage'] !== null && $v['used_count'] >= $v['max_usage']) {
    echo json_encode(['valid' => false, 'message' => 'Kuota voucher sudah habis.']); exit;
}

echo json_encode([
    'valid'        => true,
    'voucher_id'   => $v['voucher_id'],
    'discount_pct' => floatval($v['discount_pct']),
    'message'      => "Voucher valid! Diskon {$v['discount_pct']}%"
]);