<?php
// public/api/search.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo '[]'; exit; }

$q_like = "%$q%";
$stmt = $conn->prepare("SELECT game_id, game_name, publisher, image_path FROM games WHERE (game_name LIKE ? OR publisher LIKE ?) AND is_active=1 LIMIT 8");
$stmt->bind_param("ss", $q_like, $q_like);
$stmt->execute();
$res = $stmt->get_result();

$results = [];
while ($row = $res->fetch_assoc()) $results[] = $row;
echo json_encode($results);
