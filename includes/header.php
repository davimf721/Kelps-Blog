<?php
// filepath: /home/davimf7221/projetos/Kelps-Blog/includes/header.php
// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        if (file_exists(__DIR__ . '/db_connect.php')) {
            require_once __DIR__ . '/db_connect.php';
        }
    }
    
    // Verificar notificações apenas se temos conexão com banco
    if (isset($dbconn) && $dbconn) {
        $user_id = (int)$_SESSION['user_id']; // Sanitizar input
        $notification_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE";
        $notification_result = pg_query($dbconn, $notification_query);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="description" content="Kelps Blog - Compartilhe suas ideias e conhecimentos">
    <meta name="robots" content="index, follow">
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
        
        <!-- Botão do menu hambúrguer para mobile -->
        <button class="mobile-menu-toggle" aria-label="Abrir menu" aria-expanded="false">
            <span class="hamburger">
                <i class="fas fa-bars"></i>
            </span>
        </button>

        <nav role="navigation" aria-label="Menu principal">
            <ul>
                <li>
                    <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>index.php" 
                       class="<?php echo $current_page === 'home' ? 'active' : ''; ?>" 
                       aria-label="Página inicial">
                        <i class="fas fa-home" aria-hidden="true"></i>
                        <span>Home</span>
                    </a>
                </li>
                
                <?php if (is_logged_in()): ?>
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>create_post.php" 
                           class="<?php echo $current_page === 'create_post' ? 'active' : ''; ?>" 
                           aria-label="Criar novo post">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span>Criar Post</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>notifications.php" 
                           class="<?php echo $current_page === 'notifications' ? 'active' : ''; ?> notifications-link" 
                           aria-label="Notificações">
                            <i class="fas fa-bell" aria-hidden="true"></i>
                            <span>Notificações</span>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="notification-badge" aria-label="<?php echo $unread_notifications; ?> notificações não lidas">
                                    <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li>
                            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '' : '../admin/'; ?>dashboard.php" 
                               class="<?php echo $current_page === 'admin' ? 'active' : ''; ?>" 
                               aria-label="Painel administrativo">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <span>Admin</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>profile.php" 
                           class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>" 
                           aria-label="Meu perfil">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span class="username-text">Perfil (<?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuário'); ?>)</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>logout.php" 
                           aria-label="Sair da conta">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>login.php" 
                           class="<?php echo $current_page === 'login' ? 'active' : ''; ?>" 
                           aria-label="Fazer login">
                            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                            <span>Login</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>register.php" 
                           class="<?php echo $current_page === 'register' ? 'active' : ''; ?>" 
                           aria-label="Criar conta">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            <span>Registrar</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Conteúdo da página será inserido aqui -->

<script>
// Script CORRIGIDO para menu hambúrguer responsivo
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const body = document.body;
    const nav = document.querySelector('nav');
    
    // Verificar se os elementos existem
    if (!mobileMenuToggle || !nav) {
        console.log('Elementos do menu não encontrados');
        return;
    }
    
    console.log('Menu hambúrguer encontrado, inicializando...');
    
    // Função para abrir/fechar menu
    function toggleMenu() {
        const isOpen = body.classList.contains('mobile-nav-open');
        
        console.log('Toggle menu - Estado atual:', isOpen ? 'aberto' : 'fechado');
        
        if (isOpen) {
            // Fechar menu
            body.classList.remove('mobile-nav-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mobileMenuToggle.setAttribute('aria-label', 'Abrir menu');
            
            // Restaurar scroll
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            
            console.log('Menu fechado');
        } else {
            // Abrir menu
            body.classList.add('mobile-nav-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'true');
            mobileMenuToggle.setAttribute('aria-label', 'Fechar menu');
            
            // Prevenir scroll
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            
            console.log('Menu aberto');
        }
    }
    
    // Event listener para o botão hambúrguer
    mobileMenuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Botão hambúrguer clicado');
        toggleMenu();
    });
    
    // Event listener para touch events (mobile)
    mobileMenuToggle.addEventListener('touchend', function(e) {
        e.preventDefault();
        console.log('Touch no botão hambúrguer');
        toggleMenu();
    });
    
    // Fechar menu ao clicar em links
    const navLinks = document.querySelectorAll('nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768 && body.classList.contains('mobile-nav-open')) {
                console.log('Link clicado, fechando menu');
                setTimeout(() => {
                    body.classList.remove('mobile-nav-open');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    mobileMenuToggle.setAttribute('aria-label', 'Abrir menu');
                    document.body.style.overflow = '';
                    document.documentElement.style.overflow = '';
                }, 150);
            }
        });
    });
    
    // Fechar menu com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && body.classList.contains('mobile-nav-open')) {
            console.log('ESC pressionado, fechando menu');
            body.classList.remove('mobile-nav-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mobileMenuToggle.setAttribute('aria-label', 'Abrir menu');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            mobileMenuToggle.focus();
        }
    });
    
    // Fechar menu ao clicar fora
    nav.addEventListener('click', function(e) {
        if (e.target === nav && body.classList.contains('mobile-nav-open')) {
            console.log('Clique fora do menu, fechando');
            body.classList.remove('mobile-nav-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mobileMenuToggle.setAttribute('aria-label', 'Abrir menu');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
    });
    
    // Ajustar menu em mudanças de tamanho da tela
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Desktop: garantir que o menu esteja visível e limpar estados mobile
            body.classList.remove('mobile-nav-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mobileMenuToggle.setAttribute('aria-label', 'Abrir menu');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            console.log('Redimensionado para desktop, menu resetado');
        }
    });
    
    console.log('Menu hambúrguer inicializado com sucesso');
});
</script>