<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$result = $conn->query("SELECT COUNT(*) as c FROM users WHERE created_at >= NOW() - INTERVAL 24 HOUR");
$count = $result ? intval($result->fetch_assoc()['c']) : 0;

echo json_encode(['count' => $count]);