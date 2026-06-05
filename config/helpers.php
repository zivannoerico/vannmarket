<?php
// config/helpers.php

function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) redirect('/vannmarket/admin/login.php');
}

function isUserLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getLoggedUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'user_id'  => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'] ?? '',
    ];
}

function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/vannmarket';
}