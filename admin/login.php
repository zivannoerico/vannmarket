<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if (isAdminLoggedIn()) redirect('/vannmarket/admin/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_user'] = $admin['username'];
        redirect('/vannmarket/admin/');
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin | VANN Market</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { background:#0a0a0a; color:#f0f0f0; font-family:'Poppins',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .login-box { background:#1a1a1a; border-radius:16px; padding:44px; width:100%; max-width:420px; box-shadow:0 24px 64px rgba(0,0,0,.6); }
    .login-logo { text-align:center; margin-bottom:30px; }
    .login-logo h1 { font-size:24px; font-weight:700; color:#e60000; }
    .login-logo p { font-size:13px; color:#888; margin-top:4px; }
    .form-group { margin-bottom:18px; }
    label { display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:#ccc; }
    input { width:100%; padding:13px 16px; background:#222; border:1px solid #333; border-radius:8px; color:#fff; font-size:14px; font-family:inherit; outline:none; transition:border-color .2s; }
    input:focus { border-color:#e60000; }
    .btn-login { width:100%; padding:14px; background:#e60000; color:#fff; border:none; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; margin-top:8px; transition:background .2s; }
    .btn-login:hover { background:#c00; }
    .error { background:#2e0f0f; border:1px solid #661a1a; color:#e57373; padding:12px 16px; border-radius:8px; font-size:13px; margin-bottom:20px; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-logo">
      <h1>🎮 VANN Market</h1>
      <p>Admin Dashboard</p>
    </div>
    <?php if ($error): ?>
    <div class="error">⚠️ <?= esc($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username admin" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-login">Masuk ke Dashboard</button>
    </form>
  </div>
</body>
</html>
