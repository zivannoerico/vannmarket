<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if (isUserLoggedIn()) redirect('/vannmarket/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND status='active'");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['user_name']  = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        redirect('/vannmarket/');
    } else {
        $error = 'Username/email atau password salah.';
    }
}

$pageTitle = 'Masuk';
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include __DIR__ . '/../components/header.php'; ?></head>
<body>
<?php include __DIR__ . '/../components/navbar.php'; ?>

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
  <div style="background:#1e1e1e;border-radius:16px;padding:40px;width:100%;max-width:420px;border:1px solid #2a2a2a;">
    <h2 style="margin-bottom:8px;text-align:center;">Masuk ke Akun</h2>
    <p style="text-align:center;color:#888;font-size:13px;margin-bottom:24px;">Selamat datang kembali!</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field-group">
        <label>Username / Email</label>
        <input type="text" name="username" placeholder="Masukkan username atau email" required autofocus>
      </div>
      <div class="field-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-order" style="margin-top:16px;width:100%;">Masuk</button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:14px;color:#888;">
      Belum punya akun? <a href="/vannmarket/public/register.php" style="color:#e60000;">Daftar di sini</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>