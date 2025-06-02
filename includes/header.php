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
    <title><?php echo $page_title ?? 'Kelps Blog'; ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="/images/file.jpg" type="image/jpg">
    <style>
        /* Estilo para o badge de notificações */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 50%;
        }
        
        #notifications-link {
            position: relative;
        }
        
        .admin-link {
            background-color: #ff9800;
            color: white !important;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .admin-link:hover {
            background-color: #e68a00;
        }
    </style>
</head>
<body>
    <header>
        <div class="site-logo">
            <!-- Logo aqui se tiver -->
        </div>
        <h1 class="site-title">Kelps Blog</h1>
        <nav>
            <ul>
                <li><a href="/index.php" <?php echo $current_page == 'home' ? 'class="active"' : ''; ?>>Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/create_post.php" <?php echo $current_page == 'create_post' ? 'class="active"' : ''; ?>>Criar Post</a></li>
                    <li>
                        <a href="/notifications.php" id="notifications-link" <?php echo $current_page == 'notifications' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-bell"></i>
                            <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                                <span class="notification-badge"><?php echo $_SESSION['unread_notifications']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                        <li><a href="/admin/index.php" class="admin-link <?php echo strpos($current_page, 'admin') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-shield-alt"></i> Admin
                        </a></li>
                    <?php endif; ?>
                    <li><a href="/profile.php" <?php echo $current_page == 'profile' ? 'class="active"' : ''; ?>>Perfil (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                    <li><a href="/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/register.php" <?php echo $current_page == 'register' ? 'class="active"' : ''; ?>>Register</a></li>
                    <li><a href="/login.php" <?php echo $current_page == 'login' ? 'class="active"' : ''; ?>>Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <p><?php echo $_SESSION['error']; ?></p>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <p><?php echo $_SESSION['success']; ?></p>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>