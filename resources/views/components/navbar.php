<?php
/** @var array $currentUser ['id', 'username', 'is_admin'] */
$uid     = $currentUser['id'] ?? null;
$uname   = $currentUser['username'] ?? null;
$isAdmin = $currentUser['is_admin'] ?? false;
$uri     = $_SERVER['REQUEST_URI'] ?? '/';
?>
<header class="site-header" id="site-header">
    <div class="header-inner">

        <a href="/" class="site-logo">
            <img src="/images/logo.png" alt="Kelps Blog" onerror="this.style.display='none'">
            <span class="site-title">Kelps Blog</span>
        </a>

        <nav class="main-nav" id="main-nav" role="navigation" aria-label="Menu principal">
            <a href="/" class="nav-link <?= $uri === '/' ? 'active' : '' ?>">
                <i class="fas fa-home"></i><span>Início</span>
            </a>

            <?php if ($uid): ?>
                <a href="/posts/create" class="nav-link <?= str_starts_with($uri, '/posts/create') ? 'active' : '' ?>">
                    <i class="fas fa-plus"></i><span>Novo post</span>
                </a>
                <a href="/profile/<?= $uid ?>" class="nav-link <?= str_starts_with($uri, '/profile') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i><span><?= htmlspecialchars($uname, ENT_QUOTES) ?></span>
                </a>
                <a href="/notifications" class="nav-link notif-link" id="notif-link" title="Notificações">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge" id="notif-badge" style="display:none"></span>
                </a>
                <?php if ($isAdmin): ?>
                    <a href="/admin" class="nav-link nav-admin">
                        <i class="fas fa-shield-alt"></i><span>Admin</span>
                    </a>
                <?php endif; ?>
                <form method="POST" action="/logout" class="nav-logout-form">
                    <?= $csrf ?? '' ?>
                    <button type="submit" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i><span>Sair</span>
                    </button>
                </form>
            <?php else: ?>
                <a href="/login"    class="nav-link">Entrar</a>
                <a href="/register" class="btn-register">Cadastrar</a>
            <?php endif; ?>
        </nav>

        <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="main-nav">
            <i class="fas fa-bars" id="nav-toggle-icon"></i>
        </button>

    </div>
</header>

<script>
(function () {
    var toggle  = document.getElementById('nav-toggle');
    var nav     = document.getElementById('main-nav');
    var icon    = document.getElementById('nav-toggle-icon');
    if (!toggle || !nav) return;

    function openMenu() {
        nav.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        icon.classList.replace('fa-bars', 'fa-times');
    }

    function closeMenu() {
        nav.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        icon.classList.replace('fa-times', 'fa-bars');
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        nav.classList.contains('open') ? closeMenu() : openMenu();
    });

    // Fechar ao clicar fora
    document.addEventListener('click', function (e) {
        if (nav.classList.contains('open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
            closeMenu();
        }
    });

    // Fechar ao clicar em link do menu mobile
    nav.querySelectorAll('a, button[type="submit"]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeMenu();
        });
    });

<?php if ($uid): ?>
    // Polling de notificações
    (function pollNotifications() {
        fetch('/api/notifications/count', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var badge = document.getElementById('notif-badge');
                if (badge) {
                    badge.textContent = d.count;
                    badge.style.display = d.count > 0 ? 'inline-flex' : 'none';
                }
            })
            .catch(function () {});
        setTimeout(pollNotifications, 30000);
    })();
<?php endif; ?>
}());
</script>
