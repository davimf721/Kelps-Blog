<?php
// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/db_connect.php';
    
    $user_id = $_SESSION['user_id'];
    $check_notifications = pg_query($dbconn, "SELECT unread_notifications FROM users WHERE id = $user_id");
    
    if ($check_notifications && pg_num_rows($check_notifications) > 0) {
        $_SESSION['unread_notifications'] = pg_fetch_result($check_notifications, 0, 0);
    }
}

// Função para verificar se o usuário está logado
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Função para verificar se o usuário é administrador
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

// Função para verificar se o usuário está banido
function is_banned() {
    global $dbconn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $query = pg_query($dbconn, "SELECT is_banned FROM users WHERE id = $user_id");
    
    if ($query && pg_num_rows($query) > 0) {
        $user = pg_fetch_assoc($query);
        return ($user['is_banned'] == 't');
    }
    
    return false;
}

// Função para verificar se o usuário pode acessar a página atual
function check_user_access($redirect_if_banned = true) {
    global $dbconn;
    
    // Se não estiver logado, permitir acesso às páginas públicas
    if (!is_logged_in()) {
        return true;
    }
    
    // Verificar se o usuário está banido
    if (is_banned()) {
        if ($redirect_if_banned) {
            // Permitir acesso apenas às páginas de logout e banned
            $current_page = basename($_SERVER['PHP_SELF']);
            $allowed_pages = ['logout.php', 'banned.php'];
            
            if (!in_array($current_page, $allowed_pages)) {
                header("Location: banned.php");
                exit();
            }
        }
        return false;
    }
    
    return true;
}

// Função para fazer logout completo
function logout_user() {
    global $dbconn;
    
    // Limpar token de "lembrar-me" do banco se existir
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        pg_query($dbconn, "UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = $user_id");
    }
    
    // Limpar cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Destruir sessão
    session_destroy();
}

// Verificar token de "lembrar-me" se não estiver logado
function check_remember_token() {
    global $dbconn;
    
    if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        $query = pg_query_params($dbconn, 
            "SELECT id, username, is_admin, is_banned FROM users 
             WHERE remember_token = $1 AND token_expires > NOW() AND is_active = TRUE",
            [$token]
        );
        
        if ($query && pg_num_rows($query) > 0) {
            $user = pg_fetch_assoc($query);
            
            // Verificar se não está banido
            if ($user['is_banned'] != 't') {
                // Fazer login automático
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = ($user['is_admin'] == 't');
                
                return true;
            } else {
                // Se estiver banido, remover token
                pg_query_params($dbconn, 
                    "UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = $1",
                    [$user['id']]
                );
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } else {
            // Token inválido, remover cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    return false;
}

// Verificar automaticamente o token ao incluir este arquivo
check_remember_token();

// Verificar acesso do usuário (chamada automática)
check_user_access();
?>
