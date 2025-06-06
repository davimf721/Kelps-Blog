<?php
// Em vez de usar require_once, que causa erro fatal se o arquivo não existe,
// Vamos incluir diretamente as funcionalidades necessárias

// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar contagem de notificações não lidas se o usuário estiver logado
if (isset($_SESSION['user_id'])) {
    // Conectar ao banco de dados se ainda não estiver conectado
    if (!isset($dbconn) || !$dbconn) {
        if (file_exists(__DIR__ . '/db_connect.php')) {
            require_once __DIR__ . '/db_connect.php';
            
            // Atualizar contador de notificações
            $user_id = $_SESSION['user_id'];
            $check_notifications = pg_query($dbconn, "SELECT unread_notifications FROM users WHERE id = $user_id");
            
            if ($check_notifications && pg_num_rows($check_notifications) > 0) {
                $_SESSION['unread_notifications'] = pg_fetch_result($check_notifications, 0, 0);
            }
        }
    }
}

// Incluir função de autenticação
require_once __DIR__ . '/auth.php';

// Verificar se o usuário é admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    if (isset($dbconn)) {
        $admin_check = pg_query($dbconn, "SELECT is_admin FROM users WHERE id = $user_id");
        if ($admin_check && pg_num_rows($admin_check) > 0) {
            $is_admin = pg_fetch_result($admin_check, 0, 0) == 't';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Kelps Blog'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive-fixes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/file.jpg">
</head>
<body class="<?php echo isset($current_page) ? 'page-' . $current_page : ''; ?>">
    <header>
        <div class="site-logo">
            <img src="images/file.jpg" alt="Kelps Blog" onerror="this.style.display='none'">
        </div>
        
        <h1 class="site-title">Kelps Blog</h1>
        
        <!-- Botão do menu hamburger para mobile -->
        <button class="mobile-menu-toggle" aria-label="Abrir menu" aria-expanded="false">
            <i class="fas fa-bars hamburger"></i>
        </button>
        
        <nav role="navigation" aria-label="Menu principal">
            <ul>
                <li><a href="index.php" <?php echo (isset($current_page) && $current_page === 'home') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i> Home
                </a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="create_post.php" <?php echo (isset($current_page) && $current_page === 'create') ? 'class="active"' : ''; ?>>
                        <i class="fas fa-plus"></i> Criar Post
                    </a></li>
                    
                    <!-- Link para Notificações -->
                    <li><a href="notifications.php" <?php echo (isset($current_page) && $current_page === 'notifications') ? 'class="active"' : ''; ?> class="notifications-link">
                        <i class="fas fa-bell"></i> 
                        <span class="notifications-text">Notificações</span>
                        <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                            <span class="notification-badge"><?php echo $_SESSION['unread_notifications']; ?></span>
                        <?php endif; ?>
                    </a></li>
                    
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li><a href="admin/" class="admin-link">
                            <i class="fas fa-shield-alt"></i> Admin
                        </a></li>
                    <?php endif; ?>
                    
                    <li><a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>" <?php echo (isset($current_page) && $current_page === 'profile') ? 'class="active"' : ''; ?>>
                        <i class="fas fa-user"></i> 
                        <span class="username-text">Perfil (<?php echo htmlspecialchars($_SESSION['username']); ?>)</span>
                    </a></li>
                    
                    <li><a href="logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a></li>
                <?php else: ?>
                    <li><a href="login.php" <?php echo (isset($current_page) && $current_page === 'login') ? 'class="active"' : ''; ?>>
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a></li>
                    <li><a href="register.php" <?php echo (isset($current_page) && $current_page === 'register') ? 'class="active"' : ''; ?>>
                        <i class="fas fa-user-plus"></i> Registrar
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Mensagens de sucesso/erro -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

    <script>
        // Script do menu hamburger
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const body = document.body;
            const nav = document.querySelector('nav');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    const isOpen = body.classList.contains('mobile-nav-open');
                    
                    if (isOpen) {
                        // Fechar menu
                        body.classList.remove('mobile-nav-open');
                        menuToggle.setAttribute('aria-expanded', 'false');
                        menuToggle.setAttribute('aria-label', 'Abrir menu');
                        menuToggle.querySelector('.hamburger').className = 'fas fa-bars hamburger';
                        body.style.overflow = '';
                    } else {
                        // Abrir menu
                        body.classList.add('mobile-nav-open');
                        menuToggle.setAttribute('aria-expanded', 'true');
                        menuToggle.setAttribute('aria-label', 'Fechar menu');
                        menuToggle.querySelector('.hamburger').className = 'fas fa-times hamburger';
                        body.style.overflow = 'hidden';
                    }
                });
                
                // Fechar menu ao clicar nos links (mobile)
                if (nav) {
                    nav.addEventListener('click', function(e) {
                        if (e.target.tagName === 'A' && window.innerWidth <= 768) {
                            body.classList.remove('mobile-nav-open');
                            menuToggle.setAttribute('aria-expanded', 'false');
                            menuToggle.setAttribute('aria-label', 'Abrir menu');
                            menuToggle.querySelector('.hamburger').className = 'fas fa-bars hamburger';
                            body.style.overflow = '';
                        }
                    });
                }
                
                // Fechar menu ao redimensionar janela
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768 && body.classList.contains('mobile-nav-open')) {
                        body.classList.remove('mobile-nav-open');
                        menuToggle.setAttribute('aria-expanded', 'false');
                        menuToggle.setAttribute('aria-label', 'Abrir menu');
                        menuToggle.querySelector('.hamburger').className = 'fas fa-bars hamburger';
                        body.style.overflow = '';
                    }
                });
                
                // Fechar menu com tecla ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && body.classList.contains('mobile-nav-open')) {
                        body.classList.remove('mobile-nav-open');
                        menuToggle.setAttribute('aria-expanded', 'false');
                        menuToggle.setAttribute('aria-label', 'Abrir menu');
                        menuToggle.querySelector('.hamburger').className = 'fas fa-bars hamburger';
                        body.style.overflow = '';
                        menuToggle.focus();
                    }
                });
            }
        });
    </script>