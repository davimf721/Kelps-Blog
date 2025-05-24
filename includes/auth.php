<?php
// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para verificar se o usuário está logado
function is_logged_in() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Verificar o cookie de "lembrar-me"
    if (isset($_COOKIE['remember_token'])) {
        require_once 'db_connect.php'; // Conectar ao banco de dados
        
        // Para usar PostgreSQL:
        $sql = "SELECT id, username FROM users WHERE remember_token = $1 AND token_expires > NOW()";
        $result = pg_query_params($dbconn, $sql, array($_COOKIE['remember_token']));
        $user = $result ? pg_fetch_assoc($result) : null;
        
        if ($user) {
            // Cookie válido, fazer login automático
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Opcional: Renovar o token a cada login
            $new_token = bin2hex(random_bytes(32));
            $sql = "UPDATE users SET remember_token = $1, token_expires = NOW() + INTERVAL '30 days' WHERE id = $2";
            pg_query_params($dbconn, $sql, array($new_token, $user['id']));
            
            // Atualizar o cookie
            setcookie(
                'remember_token',
                $new_token,
                [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
            
            return true;
        }
    }
    
    return false;
}

// Função para obter informações do usuário atual
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    require_once 'db_connect.php';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Função para verificar se o usuário atual é um admin
function is_admin() {
    $user = get_logged_in_user();
    return $user && $user['is_admin'] == 1;
}