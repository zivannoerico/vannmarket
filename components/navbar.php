<?php // components/navbar.php ?>
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
      <a href="/vannmarket/" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['REQUEST_URI']) === '/vannmarket/') ? 'active' : '' ?>">
        <i class="fas fa-gift"></i> Topup
      </a>
      <a href="/vannmarket/public/transactions.php" class="nav-link">
        <i class="fas fa-receipt"></i> Cek Transaksi
      </a>
      <a href="#" class="nav-link">
        <i class="fas fa-trophy"></i> Leaderboard
      </a>
      <div class="nav-right">
        <a href="/vannmarket/public/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Masuk</a>
        <a href="/vannmarket/public/register.php" class="btn-register"><i class="fas fa-user-plus"></i> Daftar</a>
      </div>
    </div>
  </div>
</nav>

<script>
// Live search
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
let searchTimeout;

searchInput?.addEventListener('input', function() {
  clearTimeout(searchTimeout);
  const q = this.value.trim();
  if (q.length < 2) { searchResults.style.display = 'none'; return; }
  searchTimeout = setTimeout(() => {
    fetch(`/vannmarket/public/api/search.php?q=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.length) { searchResults.style.display = 'none'; return; }
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
    searchResults && (searchResults.style.display = 'none');
  }
});
</script>
