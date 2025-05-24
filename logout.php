<?php
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

session_start(); // Reiniciar a sessão apenas para a mensagem
$_SESSION['logout_message'] = "Você foi desconectado com sucesso.";

header("Location: index.php");
exit();
?>