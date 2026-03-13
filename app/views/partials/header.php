<?php
// filepath: /home/davimf7221/projetos/Kelps-Blog/includes/header.php
// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir sistema de tradução
require_once dirname(dirname(__DIR__)) . '/config/translations.php';

// Incluir funcionalidades de autenticação se não foram carregadas
if (!function_exists('is_logged_in')) {
    // Tentar incluir auth.php se existir
    if (file_exists(__DIR__ . '/auth.php')) {
        require_once __DIR__ . '/auth.php';
    } else {
        // Definir função básica se auth.php não existir
        function is_logged_in() {
            return isset($_SESSION['user_id']);
        }
    }
}

// Verificar notificações não lidas
$unread_notifications = 0;
if (is_logged_in()) {
    // Incluir conexão com banco se não foi incluída
    if (!isset($dbconn)) {
        $dbPath = __DIR__ . '/../../helpers/db.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }
    }
    
    // Verificar notificações apenas se temos conexão com banco
    if (isset($dbconn) && $dbconn) {
        $user_id = (int)$_SESSION['user_id'];
        $notification_result = pg_query_params($dbconn, 
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = $1 AND is_read = FALSE",
            [$user_id]
        );
        if ($notification_result) {
            $notification_data = pg_fetch_assoc($notification_result);
            $unread_notifications = (int)$notification_data['count'];
        }
    }
}

// Definir página atual se não foi definida
if (!isset($current_page)) {
    $current_page = '';
}

// Definir título da página se não foi definido
if (!isset($page_title)) {
    $page_title = 'Kelps Blog';
}

// Determinar o caminho correto para CSS baseado na estrutura de diretórios
$css_path = 'css/style.css';
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $css_path = '../css/style.css';
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $css_path; ?>">
    <?php 
    // Adicionar CSS de posts para páginas que usam cards
    $posts_css = str_replace('style.css', 'posts.css', $css_path);
    $landing_css = str_replace('style.css', 'landing.css', $css_path);
    $profile_css = str_replace('style.css', 'profile.css', $css_path);
    ?>
    <link rel="stylesheet" href="<?php echo $posts_css; ?>">
    <link rel="stylesheet" href="<?php echo $landing_css; ?>">
    <link rel="stylesheet" href="<?php echo $profile_css; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="description" content="Kelps Blog - Compartilhe suas ideias e conhecimentos">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/png" href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>images/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>images/logo.png">
</head>
<body class="<?php echo isset($current_page) ? 'page-' . $current_page : ''; ?>">
    <header>
        <div class="site-logo">
            <img src="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>images/logo.png" 
                 alt="Kelps Blog Logo" 
                 onerror="this.style.display='none'">
        </div>
        
        <h1 class="site-title">
            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>index.php" 
               style="color: inherit; text-decoration: none;">
                Kelps Blog
            </a>
        </h1>
        
        <nav role="navigation" aria-label="Menu principal">
            <ul>
                <li>
                    <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>index.php" 
                       class="<?php echo $current_page === 'home' ? 'active' : ''; ?>" 
                       aria-label="<?php echo __('home'); ?>">
                        <i class="fas fa-home" aria-hidden="true"></i>
                        <span><?php echo __('home'); ?></span>
                    </a>
                </li>
                
                <?php if (is_logged_in()): ?>
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>create_post.php" 
                           class="<?php echo $current_page === 'create_post' ? 'active' : ''; ?>" 
                           aria-label="<?php echo __('create_post'); ?>">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span><?php echo __('create_post'); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Botão do menu hambúrguer -->
        <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="<?php echo __('open_menu'); ?>" aria-expanded="false">
            <i class="fas fa-bars" id="menu-icon"></i>
        </button>
        
        <!-- Menu Sanduíche Lateral (Drawer) -->
        <div class="sidebar-drawer" id="sidebar-drawer" aria-hidden="true">
            <div class="drawer-header">
                <button class="drawer-close" id="drawer-close-btn" aria-label="<?php echo __('close_menu'); ?>">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <ul class="drawer-menu">
                <!-- Home - Sempre visível -->
                <li>
                    <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>index.php" 
                       class="drawer-link">
                        <i class="fas fa-home"></i>
                        <span><?php echo __('home'); ?></span>
                    </a>
                </li>
                
                <?php if (is_logged_in()): ?>
                    <!-- Criar Post -->
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>create_post.php" 
                           class="drawer-link">
                            <i class="fas fa-plus"></i>
                            <span><?php echo __('create_post'); ?></span>
                        </a>
                    </li>
                    
                    <!-- Notificações -->
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>notifications.php" 
                           class="drawer-link">
                            <i class="fas fa-bell"></i>
                            <span><?php echo __('notifications'); ?></span>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="drawer-badge"><?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li>
                            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '' : '../admin/'; ?>dashboard.php" 
                               class="drawer-link">
                                <i class="fas fa-shield-alt"></i>
                                <span><?php echo __('admin'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>profile.php" 
                           class="drawer-link">
                            <i class="fas fa-user"></i>
                            <span><?php echo __('profile'); ?> (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>logout.php" 
                           class="drawer-link drawer-logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span><?php echo __('logout'); ?></span>
                        </a>
                    </li>
                    
                    <li class="drawer-divider"></li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>login.php" 
                           class="drawer-link">
                            <i class="fas fa-sign-in-alt"></i>
                            <span><?php echo __('login'); ?></span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>register.php" 
                           class="drawer-link">
                            <i class="fas fa-user-plus"></i>
                            <span><?php echo __('register'); ?></span>
                        </a>
                    </li>
                    
                    <li class="drawer-divider"></li>
                <?php endif; ?>
                
                <!-- Seletor de Idioma -->
                <li class="language-selector">
                    <span class="language-label"><i class="fas fa-globe"></i> <?php echo __('language'); ?></span>
                    <div class="language-buttons">
                        <button class="lang-btn <?php echo LanguageManager::isPortuguese() ? 'active' : ''; ?>" 
                                data-lang="pt" 
                                aria-label="<?php echo __('portuguese'); ?>">
                            🇧🇷 PT
                        </button>
                        <button class="lang-btn <?php echo LanguageManager::isEnglish() ? 'active' : ''; ?>" 
                                data-lang="en" 
                                aria-label="<?php echo __('english'); ?>">
                            🇺🇸 EN
                        </button>
                    </div>
                </li>
            </ul>
        </div>
        
        <!-- Overlay para fechar menu ao clicar fora -->
        <div class="drawer-overlay" id="drawer-overlay"></div>
    </header>

    <!-- Container de pop-ups de notificação -->
    <?php if (is_logged_in()): ?>
    <div id="notif-toast-container" aria-live="polite" aria-atomic="false"></div>
    <?php endif; ?>

    <script src="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>js/drawer.js"></script>

    <main>

<script>
// Sinaliza ao drawer.js externo que os listeners já estão configurados aqui
window._drawerInlineInit = true;

// Sistema de Menu Sanduíche + Seletor de Idioma
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebarDrawer = document.getElementById('sidebar-drawer');
    const drawerOverlay = document.getElementById('drawer-overlay');
    const drawerCloseBtn = document.getElementById('drawer-close-btn');
    const langButtons = document.querySelectorAll('.lang-btn');
    
    // Funções de abertura/fechamento do drawer
    function openDrawer() {
        sidebarDrawer.classList.add('drawer-open');
        sidebarDrawer.setAttribute('aria-hidden', 'false');
        drawerOverlay.classList.add('overlay-visible');
        menuToggle.setAttribute('aria-expanded', 'true');
        menuToggle.setAttribute('aria-label', '<?php echo addslashes(__('close_menu')); ?>');
        menuToggle.closest('header').classList.add('menu-is-open');
        document.getElementById('menu-icon').classList.replace('fa-bars', 'fa-times');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDrawer() {
        sidebarDrawer.classList.remove('drawer-open');
        sidebarDrawer.setAttribute('aria-hidden', 'true');
        drawerOverlay.classList.remove('overlay-visible');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', '<?php echo addslashes(__('open_menu')); ?>');
        menuToggle.closest('header').classList.remove('menu-is-open');
        document.getElementById('menu-icon').classList.replace('fa-times', 'fa-bars');
        document.body.style.overflow = '';
    }
    
    // Event listeners para abrir/fechar
    menuToggle?.addEventListener('click', function() {
        sidebarDrawer.classList.contains('drawer-open') ? closeDrawer() : openDrawer();
    });
    drawerCloseBtn?.addEventListener('click', closeDrawer);
    drawerOverlay?.addEventListener('click', closeDrawer);
    
    // Fechar ao clicar em um link
    const drawerLinks = document.querySelectorAll('.drawer-link, .drawer-divider');
    drawerLinks.forEach(link => {
        if (link.tagName === 'A') {
            link.addEventListener('click', closeDrawer);
        }
    });
    
    // Fechar com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarDrawer.classList.contains('drawer-open')) {
            closeDrawer();
            menuToggle.focus();
        }
    });
    
    // Seletor de Idioma
    langButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lang = this.getAttribute('data-lang');
            
            // Atualizar cookie via AJAX
            fetch('<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>app/api/set-language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ language: lang })
            })
            .then(() => {
                // Recarregar página após mudar idioma
                location.reload();
            })
            .catch(err => console.error('Erro ao mudar idioma:', err));
        });
    });
});
</script>

<?php if (is_logged_in()): ?>
<script>
// ================================================================
// SISTEMA DE POP-UP DE NOTIFICAÇÕES (polling a cada 30s)
// ================================================================
(function() {
    const BASE_PATH = '<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>';
    const POLL_INTERVAL = 30000; // 30 segundos
    const TOAST_DURATION = 6000; // 6 segundos visível

    // Ícones por tipo
    const ICONS = {
        new_post : '<i class="fas fa-file-alt"></i>',
        comment  : '<i class="fas fa-comment"></i>',
        follow   : '<i class="fas fa-user-plus"></i>',
        upvote   : '<i class="fas fa-arrow-up"></i>',
        default  : '<i class="fas fa-bell"></i>',
    };

    const TITLES = {
        new_post : 'Novo Post',
        comment  : 'Comentário',
        follow   : 'Novo Seguidor',
        upvote   : 'Upvote',
        default  : 'Notificação',
    };

    // Timestamp da última checagem (ISO string)
    let lastChecked = new Date().toISOString();

    // Elemento container dos toasts
    const container = document.getElementById('notif-toast-container');

    // Badge de contagem não lida no drawer (se existir)
    function updateBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }

        // Badge no drawer também
        const drawerBadge = document.querySelector('.drawer-badge');
        if (drawerBadge) {
            drawerBadge.textContent = count > 99 ? '99+' : count;
            drawerBadge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }

    function showToast(notif) {
        if (!container) return;

        const type  = notif.type || 'default';
        const icon  = ICONS[type]  || ICONS.default;
        const title = TITLES[type] || TITLES.default;

        const toast = document.createElement('div');
        toast.className = `notif-toast notif-toast--${type}`;
        toast.setAttribute('role', 'alert');

        // Link para a referência, se houver
        const href = notif.reference_id
            ? `${BASE_PATH}post.php?id=${notif.reference_id}`
            : `${BASE_PATH}notifications.php`;

        toast.innerHTML = `
            <span class="notif-toast__icon">${icon}</span>
            <div class="notif-toast__body">
                <div class="notif-toast__title">${title}</div>
                <div class="notif-toast__text">${escapeHtmlToast(notif.content)}</div>
            </div>
            <button class="notif-toast__close" aria-label="Fechar">&times;</button>
            <div class="notif-toast__progress"></div>
        `;

        // Clicar no corpo navega para a notificação
        toast.addEventListener('click', function(e) {
            if (e.target.closest('.notif-toast__close')) return;
            window.location.href = href;
        });

        toast.querySelector('.notif-toast__close').addEventListener('click', function(e) {
            e.stopPropagation();
            dismissToast(toast);
        });

        container.appendChild(toast);

        // Animar entrada
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('visible'));
        });

        // Auto-remover após TOAST_DURATION
        const timer = setTimeout(() => dismissToast(toast), TOAST_DURATION);
        toast._timer = timer;
    }

    function dismissToast(toast) {
        clearTimeout(toast._timer);
        toast.classList.remove('visible');
        toast.style.transition = 'transform 0.3s ease, opacity 0.25s ease';
        toast.style.transform = 'translateX(120%)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 320);
    }

    function escapeHtmlToast(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function poll() {
        const url = `${BASE_PATH}app/api/poll_notifications.php?since=${encodeURIComponent(lastChecked)}`;
        const now = new Date().toISOString();

        fetch(url, { credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || !data.success) return;

                // Atualizar badge
                updateBadge(data.unread_count);

                // Mostrar toast para cada nova notificação (máx 3 por polling)
                const toShow = data.notifications.slice(0, 3);
                toShow.reverse().forEach(notif => showToast(notif));

                // Avançar timestamp
                lastChecked = now;
            })
            .catch(() => {}); // falha silenciosa
    }

    // Iniciar polling após 5s (deixar a página carregar)
    setTimeout(function startPolling() {
        poll();
        setInterval(poll, POLL_INTERVAL);
    }, 5000);
})();
</script>
<?php endif; ?>