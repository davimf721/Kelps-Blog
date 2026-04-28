<?php
/** @var array $currentUser ['id', 'username', 'is_admin'] */
$uid      = $currentUser['id'] ?? null;
$uname    = $currentUser['username'] ?? null;
$isAdmin  = $currentUser['is_admin'] ?? false;
$uri      = $_SERVER['REQUEST_URI'] ?? '/';
?>
<header class="site-header">
    <div class="header-inner">
        <a href="/" class="site-logo">
            <img src="/images/logo.png" alt="Kelps Blog" onerror="this.style.display='none'">
            <span class="site-title">Kelps Blog</span>
        </a>

        <nav class="main-nav" role="navigation" aria-label="Menu principal">
            <a href="/" class="nav-link <?= $uri === '/' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Início</span>
            </a>

            <?php if ($uid): ?>
                <a href="/posts/create" class="nav-link <?= str_starts_with($uri, '/posts/create') ? 'active' : '' ?>">
                    <i class="fas fa-plus"></i> <span>Novo post</span>
                </a>

                <a href="/profile/<?= $uid ?>" class="nav-link <?= str_starts_with($uri, '/profile') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> <span><?= htmlspecialchars($uname, ENT_QUOTES) ?></span>
                </a>

                <a href="/notifications" class="nav-link notif-link" id="notif-link">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge" id="notif-badge" style="display:none"></span>
                </a>

                <?php if ($isAdmin): ?>
                    <a href="/admin" class="nav-link nav-admin">
                        <i class="fas fa-shield-alt"></i> <span>Admin</span>
                    </a>
                <?php endif; ?>

                <form method="POST" action="/logout" style="display:inline">
                    <?= $csrf ?? '' ?>
                    <button type="submit" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> <span>Sair</span>
                    </button>
                </form>
            <?php else: ?>
                <a href="/login"    class="nav-link">Entrar</a>
                <a href="/register" class="btn-register">Cadastrar</a>
            <?php endif; ?>
        </nav>

        <button class="nav-toggle" id="nav-toggle" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<script>
document.getElementById('nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.main-nav')?.classList.toggle('open');
});

// Polling de notificações
<?php if ($uid): ?>
(function pollNotifications() {
    fetch('/api/notifications/count', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => {
            const badge = document.getElementById('notif-badge');
            if (badge) {
                badge.textContent = d.count;
                badge.style.display = d.count > 0 ? 'inline-block' : 'none';
            }
        })
        .catch(() => {});
    setTimeout(pollNotifications, 30000);
})();
<?php endif; ?>
</script>
