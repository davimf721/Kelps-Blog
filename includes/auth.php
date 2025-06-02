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
    // Verificar primeiro se há sessão ativa
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    
    // Se não há sessão, verificar cookie
    if (isset($_COOKIE['remember_token'])) {
        // Estabelecer conexão com o banco de dados
        try {
            // Usar as variáveis de ambiente diretamente aqui para garantir conexão
            $host = getenv('DB_HOST');
            $port = getenv('DB_PORT');
            $dbname = getenv('DB_NAME');
            $user = getenv('DB_USER');
            $password = getenv('DB_PASSWORD');
            
            if (!$host || !$dbname || !$user) {
                // Variáveis de ambiente não definidas, não podemos conectar
                return false;
            }
            
            $conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
            $local_dbconn = pg_connect($conn_string);
            
            if (!$local_dbconn) {
                return false;
            }
            
            $sql = "SELECT id, username, is_admin FROM users WHERE remember_token = $1 AND token_expires > NOW()";
            $result = pg_query_params($local_dbconn, $sql, array($_COOKIE['remember_token']));
            
            if ($result && pg_num_rows($result) > 0) {
                $user = pg_fetch_assoc($result);
                
                // Cookie válido, fazer login automático
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                
                return true;
            }
        } catch (Exception $e) {
            // Falha na conexão ou consulta, ignorar e retornar false
            return false;
        }
    }
    
    return false;
}

// Função para obter informações do usuário atual
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $dbconn;
    
    // Usar as variáveis de ambiente diretamente aqui para garantir conexão
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    
    if (!$host || !$dbname || !$user) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'is_admin' => isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false
        ];
    }
    
    try {
        $conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
        $local_dbconn = pg_connect($conn_string);
        
        if (!$local_dbconn) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'is_admin' => isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false
            ];
        }
        
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT id, username, email, is_admin FROM users WHERE id = $1";
        $result = pg_query_params($local_dbconn, $sql, array($user_id));
        
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
    } catch (Exception $e) {
        // Em caso de erro, retornar dados da sessão
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'is_admin' => isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false
    ];
}

// Função para verificar se o usuário atual é um admin
function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    
    if (isset($_SESSION['is_admin'])) {
        return (bool)$_SESSION['is_admin'];
    }
    
    global $dbconn;
    
    try {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT is_admin FROM users WHERE id = $user_id";
        $result = pg_query($dbconn, $sql);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $_SESSION['is_admin'] = (bool)$row['is_admin'];
            return $_SESSION['is_admin'];
        }
    } catch (Exception $e) {
        // Em caso de erro, retornar false
    }
    
    return false;
}

// Adicionar login quando o usuário acessa o site
function update_login_info($user_id) {
    global $dbconn;

    try {
        $sql = "SELECT is_admin FROM users WHERE id = $user_id";
        $result = pg_query($dbconn, $sql);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $_SESSION['is_admin'] = (bool)$row['is_admin'];
        }
    } catch (Exception $e) {
        // Ignorar erro
    }
}

// Adiciona um alias para isLoggedIn para compatibilidade
function isLoggedIn() {
    return is_logged_in();
}
