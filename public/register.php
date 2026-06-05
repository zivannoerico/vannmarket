<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if (isUserLoggedIn()) redirect('/vannmarket/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$username || !$password) {
        $error = 'Username dan password wajib diisi.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $chk = $conn->prepare("SELECT user_id FROM users WHERE username=?");
        $chk->bind_param("s", $username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Username sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username,email,phone_number,password,status,created_at) VALUES (?,?,?,?,'active',NOW())");
            $stmt->bind_param("ssss", $username, $email, $phone, $hash);
            if ($stmt->execute()) {
                // Auto login setelah daftar
                $new_id = $conn->insert_id;
                $_SESSION['user_id']    = $new_id;
                $_SESSION['user_name']  = $username;
                $_SESSION['user_email'] = $email;
                redirect('/vannmarket/');
            } else {
                $error = 'Gagal mendaftar: ' . $conn->error;
            }
        }
    }
}

$pageTitle = 'Daftar';
?>
<!DOCTYPE html>
<html lang="id">
<head><?php include __DIR__ . '/../components/header.php'; ?></head>
<body>
<?php include __DIR__ . '/../components/navbar.php'; ?>

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
  <div style="background:#1e1e1e;border-radius:16px;padding:40px;width:100%;max-width:440px;border:1px solid #2a2a2a;">
    <h2 style="margin-bottom:8px;text-align:center;">Buat Akun Baru</h2>
    <p style="text-align:center;color:#888;font-size:13px;margin-bottom:24px;">Daftar gratis dan mulai top up!</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field-group">
        <label>Username *</label>
        <input type="text" name="username" placeholder="Pilih username unik" required autofocus>
      </div>
      <div class="field-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="email@contoh.com">
      </div>
      <div class="field-group">
        <label>No. HP</label>
        <input type="text" name="phone_number" placeholder="08xxxxxxxxxx">
      </div>
      <div class="field-group">
        <label>Password *</label>
        <input type="password" name="password" placeholder="Minimal 6 karakter" required>
      </div>
      <div class="field-group">
        <label>Konfirmasi Password *</label>
        <input type="password" name="confirm_password" placeholder="Ulangi password" required>
      </div>
      <button type="submit" class="btn-order" style="margin-top:16px;width:100%;">Daftar Sekarang</button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:14px;color:#888;">
      Sudah punya akun? <a href="/vannmarket/public/login.php" style="color:#e60000;">Masuk di sini</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>