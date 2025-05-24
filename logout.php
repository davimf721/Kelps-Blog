<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php'; // Este arquivo deve definir $dbconn

// Limpar o token no banco de dados
if (isset($_SESSION['user_id']) && isset($dbconn)) {
    
    $sql = "UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = $1";
    $result = pg_query_params($dbconn, $sql, array($_SESSION['user_id']));

    if (!$result) {
        // Opcional: Adicionar log de erro se a consulta falhar
        error_log("Erro ao limpar remember_token no logout para user_id: " . $_SESSION['user_id'] . " - " . pg_last_error($dbconn));
    }
} elseif (isset($_SESSION['user_id']) && !isset($dbconn)) {
    // Opcional: Adicionar log de erro se $dbconn não estiver definido
    error_log("Erro no logout: \$dbconn não está definido. user_id: " . $_SESSION['user_id']);
}

// Destruir o cookie
setcookie('remember_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => true, // Requer HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Destruir a sessão
session_unset();
session_destroy();

// Redirecionar para a página inicial
header("Location: index.php");
exit;
?>