<?php
// config/db.php — Koneksi Database Laragon
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');         // Laragon default kosong
define('DB_NAME', 'vansstore');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
}
