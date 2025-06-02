<?php
// Iniciar a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário estiver logado, atualizar o contador de notificações na sessão
if (isset($_SESSION['user_id'])) {
    // Verificar se já estamos conectados ao banco de dados
    if (!isset($dbconn) || !$dbconn) {
        // Tentar incluir o arquivo de conexão com o banco
        if (file_exists(__DIR__ . '/db_connect.php')) {
            require_once __DIR__ . '/db_connect.php';
        } else {
            // Se não conseguirmos conectar, apenas continue
            return;
        }
    }
    
    $user_id = $_SESSION['user_id'];
    $check_notifications = pg_query($dbconn, "SELECT unread_notifications FROM users WHERE id = $user_id");
    
    if ($check_notifications && pg_num_rows($check_notifications) > 0) {
        $_SESSION['unread_notifications'] = pg_fetch_result($check_notifications, 0, 0);
    }
}
?>