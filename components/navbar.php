<?php
// components/navbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedUser = getLoggedUser();
?>
<style>
.user-menu { position:relative; }
.user-trigger {
  display:flex; align-items:center; gap:8px; cursor:pointer;
  background:#1e1e1e; border:1px solid #2a2a2a; border-radius:8px;
  padding:7px 14px; transition:.2s; user-select:none;
}
.user-trigger:hover { border-color:#444; background:#252525; }
.user-avatar-sm {
  width:28px; height:28px; background:var(--red); border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:12px; font-weight:700; color:#fff; flex-shrink:0;
}
.user-trigger-name { font-size:13px; font-weight:600; color:#f0f0f0; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.user-trigger-arrow { font-size:10px; color:#666; transition:.2s; }
.user-menu.open .user-trigger-arrow { transform:rotate(180deg); }
.user-dropdown {
  position:absolute; top:calc(100% + 8px); right:0; min-width:180px;
  background:#1e1e1e; border:1px solid #2a2a2a; border-radius:10px;
  padding:6px; z-index:999; display:none;
  box-shadow:0 8px 24px rgba(0,0,0,.5);
}
.user-menu.open .user-dropdown { display:block; }
.user-dropdown-header { padding:10px 12px 8px; border-bottom:1px solid #2a2a2a; margin-bottom:4px; }
.user-dropdown-header .uname { font-size:14px; font-weight:700; }
.user-dropdown-header .uemail { font-size:11px; color:#666; margin-top:2px; }
.user-dropdown a {
  display:flex; align-items:center; gap:8px; padding:9px 12px;
  font-size:13px; color:#ccc; border-radius:6px; text-decoration:none; transition:.15s;
}
.user-dropdown a:hover { background:#2a2a2a; color:#fff; }
.user-dropdown .logout-link { color:#e57373; margin-top:2px; border-top:1px solid #2a2a2a; padding-top:8px; }
.user-dropdown .logout-link:hover { background:rgba(230,0,0,.1); color:#e60000; }
</style>

<nav class="main-nav">
  <div class="nav-top container">
    <a href="/vannmarket/" class="logo">
      <img src="/vannmarket/assets/image/logo vannmarket.png" alt="VANN MARKET">
    </a>
    <div class="search-container">
      <input type="text" id="searchInput" placeholder="Cari Game atau Voucher..." autocomplete="off">
      <div id="searchResults" class="search-dropdown"></div>
    </div>
    <div class="language">
      <img src="/vannmarket/assets/image/bendera_indonesia-removebg-preview.png" alt="ID">
      <span>ID / IDR</span>
    </div>
  </div>
  <div class="nav-bottom">
    <div class="container nav-links">
      <a href="/vannmarket/" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php' || $_SERVER['REQUEST_URI'] === '/vannmarket/') ? 'active' : '' ?>">
        <i class="fas fa-gift"></i> Topup
      </a>
      <a href="/vannmarket/public/transactions.php" class="nav-link">
        <i class="fas fa-receipt"></i> Cek Transaksi
      </a>
      <a href="/vannmarket/public/leaderboard.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'leaderboard.php') ? 'active' : '' ?>">
        <i class="fas fa-trophy"></i> Leaderboard
      </a>
      <div class="nav-right">
        <?php if ($loggedUser): ?>
        <div class="user-menu" id="userMenu">
          <div class="user-trigger" onclick="toggleUserMenu()">
            <div class="user-avatar-sm"><?= strtoupper(substr($loggedUser['username'], 0, 1)) ?></div>
            <span class="user-trigger-name"><?= esc($loggedUser['username']) ?></span>
            <span class="user-trigger-arrow">▼</span>
          </div>
          <div class="user-dropdown">
            <div class="user-dropdown-header">
              <div class="uname"><?= esc($loggedUser['username']) ?></div>
              <div class="uemail"><?= esc($_SESSION['user_email'] ?? '') ?></div>
            </div>
            <a href="/vannmarket/public/transactions.php"><i class="fas fa-receipt"></i> Riwayat Transaksi</a>
            <a href="/vannmarket/public/api/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Keluar</a>
          </div>
        </div>
        <?php else: ?>
        <a href="/vannmarket/public/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Masuk</a>
        <a href="/vannmarket/public/register.php" class="btn-register"><i class="fas fa-user-plus"></i> Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<script>
function toggleUserMenu() {
  document.getElementById('userMenu')?.classList.toggle('open');
}
document.addEventListener('click', e => {
  const menu = document.getElementById('userMenu');
  if (menu && !menu.contains(e.target)) menu.classList.remove('open');
});

const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
let searchTimeout;
searchInput?.addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  if (q.length < 2) { searchResults.style.display='none'; return; }
  searchTimeout = setTimeout(() => {
    fetch(`/vannmarket/public/api/search.php?q=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.length) { searchResults.style.display='none'; return; }
        searchResults.innerHTML = data.map(g =>
          `<a href="/vannmarket/public/game.php?id=${g.game_id}" class="search-item">
            <img src="/vannmarket/${g.image_path}" onerror="this.src='/vannmarket/assets/image/placeholder.png'" alt="">
            <span>${g.game_name}</span>
            <small>${g.publisher}</small>
          </a>`
        ).join('');
        searchResults.style.display = 'block';
      });
  }, 300);
});
document.addEventListener('click', e => {
  if (!searchInput?.contains(e.target) && !searchResults?.contains(e.target)) {
    searchResults && (searchResults.style.display='none');
  }
});
</script>