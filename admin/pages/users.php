<?php
// admin/pages/users.php — Kelola Users

$msg = ''; $msg_type = '';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $id);
    
    // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
    if ($stmt->execute()) {
        $msg = 'User dihapus.';
        $msg_type = 'success';
    } else {
        $msg = 'Gagal menghapus.';
        $msg_type = 'error';
    }
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE users SET status = IF(status='active','inactive','active') WHERE user_id=$id");
    header("Location: ?page=users"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $uid          = intval($_POST['user_id'] ?? 0);
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone_number'] ?? '');
    $status      = $_POST['status'] ?? 'active';
    $password_raw= $_POST['password'] ?? '';

    if (!$username) { 
        $msg = 'Username wajib diisi.'; 
        $msg_type = 'error'; 
    } else {
        if ($action === 'add') {
            if (!$password_raw) { 
                $msg = 'Password wajib diisi.'; 
                $msg_type = 'error'; 
            } else {
                $hash = password_hash($password_raw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username,email,password,phone_number,status,created_at) VALUES (?,?,?,?,?,NOW())");
                $stmt->bind_param("sssss", $username, $email, $hash, $phone, $status);
                
                // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
                if ($stmt->execute()) {
                    $msg = 'User berhasil ditambahkan.';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal: ' . $conn->error;
                    $msg_type = 'error';
                }
            }
        } elseif ($action === 'edit') {
            if ($password_raw) {
                $hash = password_hash($password_raw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?,email=?,password=?,phone_number=?,status=? WHERE user_id=?");
                $stmt->bind_param("sssssi", $username, $email, $hash, $phone, $status, $uid);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?,email=?,phone_number=?,status=? WHERE user_id=?");
                $stmt->bind_param("ssssi", $username, $email, $phone, $status, $uid);
            }
            
            // PERBAIKAN: Mengubah ternary berkoma menjadi if-else biasa
            if ($stmt->execute()) {
                $msg = 'User berhasil diperbarui.';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal.';
                $msg_type = 'error';
            }
        }
        if ($msg_type === 'success') { header("Location: ?page=users&msg=".urlencode($msg)); exit; }
    }
}
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = 'success'; }

$edit = null;
if (isset($_GET['edit'])) {
    $eid  = intval($_GET['edit']);
    $edit = $conn->query("SELECT * FROM users WHERE user_id=$eid")->fetch_assoc();
}

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE username LIKE '%". $conn->real_escape_string($search) ."%' OR phone_number LIKE '%". $conn->real_escape_string($search) ."%'" : '';
$users  = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= esc($msg) ?></div>
<?php endif; ?>

<!-- FORM -->
<div class="section-card" style="margin-bottom:24px;">
  <div class="section-header">
    <h2><?= $edit ? '✏️ Edit User' : '➕ Tambah User Baru' ?></h2>
    <?php if ($edit): ?><a href="?page=users" class="btn btn-sm btn-secondary">✕ Batal</a><?php endif; ?>
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
    <?php if ($edit): ?><input type="hidden" name="user_id" value="<?= $edit['user_id'] ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label>Username *</label>
        <input type="text" name="username" value="<?= esc($edit['username'] ?? '') ?>" placeholder="Username" required>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= esc($edit['email'] ?? '') ?>" placeholder="email@contoh.com">
      </div>
      <div class="field">
        <label>No. HP</label>
        <input type="text" name="phone_number" value="<?= esc($edit['phone_number'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
      <div class="field">
        <label>Password <?= $edit ? '(kosongkan jika tidak diganti)' : '*' ?></label>
        <input type="password" name="password" placeholder="Password" <?= !$edit ? 'required' : '' ?>>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= (($edit['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktif</option>
          <option value="inactive" <?= (($edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
    </div>

    <div style="padding:0 22px 22px;">
      <button type="submit" class="btn btn-primary">
        <?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah User' ?>
      </button>
    </div>
  </form>
</div>

<!-- TABEL -->
<div class="section-card">
  <div class="section-header">
    <h2>👥 Daftar Users (<?= $users->num_rows ?>)</h2>
    <form method="GET" class="search-bar">
      <input type="hidden" name="page" value="users">
      <input type="text" name="q" value="<?= esc($search) ?>" placeholder="Cari username / no HP...">
      <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Username</th><th>Email</th><th>No HP</th><th>Status</th><th>Terdaftar</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if ($users->num_rows === 0): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="icon">👥</div><p>Belum ada user.</p></div></td></tr>
        <?php else: ?>
        <?php while ($u = $users->fetch_assoc()): ?>
        <tr>
          <td>#<?= $u['user_id'] ?></td>
          <td style="font-weight:600;"><?= esc($u['username']) ?></td>
          <td style="color:#888;"><?= esc($u['email'] ?? '—') ?></td>
          <td><?= esc($u['phone_number'] ?? '—') ?></td>
          <td>
            <a href="?page=users&toggle=<?= $u['user_id'] ?>" style="text-decoration:none;">
              <span class="badge-status <?= $u['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                <?= $u['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </a>
          </td>
          <td style="font-size:13px;color:#888;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="cell-actions">
              <a href="?page=users&edit=<?= $u['user_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="?page=users&delete=<?= $u['user_id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus user <?= esc($u['username']) ?>?')">Hapus</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>