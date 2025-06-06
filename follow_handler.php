<?php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Definir cabeçalho para retornar JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para realizar esta ação.']);
    exit;
}

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Obter dados da requisição
$follower_id = $_SESSION['user_id'];
$following_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validar dados
if ($following_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    exit;
}

if ($follower_id === $following_id) {
    echo json_encode(['success' => false, 'message' => 'Você não pode seguir a si mesmo.']);
    exit;
}

if ($action !== 'follow' && $action !== 'unfollow') {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit;
}

// Verificar se o usuário a ser seguido existe
$check_user = pg_query_params($dbconn, "SELECT id FROM users WHERE id = $1", [$following_id]);

if (!$check_user || pg_num_rows($check_user) === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    exit;
}

// Verificar se a tabela followers existe
$check_followers_table = pg_query($dbconn, "SELECT to_regclass('public.followers')");
$followers_table_exists = (pg_fetch_result($check_followers_table, 0, 0) !== NULL);

if (!$followers_table_exists) {
    $create_followers_table = "
    CREATE TABLE IF NOT EXISTS followers (
        id SERIAL PRIMARY KEY,
        follower_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        following_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(follower_id, following_id)
    )";
    pg_query($dbconn, $create_followers_table);
    
    // Criar índices para melhor performance
    pg_query($dbconn, "CREATE INDEX IF NOT EXISTS idx_followers_follower_id ON followers(follower_id)");
    pg_query($dbconn, "CREATE INDEX IF NOT EXISTS idx_followers_following_id ON followers(following_id)");
}

try {
    // Iniciar uma transação
    pg_query($dbconn, "BEGIN");
    
    if ($action === 'follow') {
        // Verificar se já está seguindo
        $check_follow = pg_query_params($dbconn, 
            "SELECT id FROM followers WHERE follower_id = $1 AND following_id = $2", 
            [$follower_id, $following_id]
        );
        
        if (pg_num_rows($check_follow) > 0) {
            echo json_encode(['success' => false, 'message' => 'Você já está seguindo este usuário.']);
            pg_query($dbconn, "ROLLBACK");
            exit;
        }
        
        // Inserir novo registro de seguidor
        $insert_follow = pg_query_params($dbconn, 
            "INSERT INTO followers (follower_id, following_id) VALUES ($1, $2)", 
            [$follower_id, $following_id]
        );
        
        if (!$insert_follow) {
            throw new Exception(pg_last_error($dbconn));
        }
        
        // Obter nome do usuário que está seguindo
        $get_username = pg_query_params($dbconn, "SELECT username FROM users WHERE id = $1", [$follower_id]);
        $follower_username = pg_fetch_result($get_username, 0, 0);
        
        // Verificar se a tabela de notificações existe
        $check_notifications_table = pg_query($dbconn, "SELECT to_regclass('public.notifications')");
        $notifications_table_exists = (pg_fetch_result($check_notifications_table, 0, 0) !== NULL);
        
        if ($notifications_table_exists) {
            // Adicionar notificação para o usuário seguido
            $notification_content = "$follower_username começou a seguir você.";
            $add_notification = pg_query_params($dbconn, 
                "INSERT INTO notifications (user_id, sender_id, type, content, reference_id) 
                 VALUES ($1, $2, 'follow', $3, $4)", 
                [$following_id, $follower_id, $notification_content, $follower_id]
            );
            
            if (!$add_notification) {
                throw new Exception(pg_last_error($dbconn));
            }
            
            // Incrementar contador de notificações não lidas
            $update_counter = pg_query_params($dbconn, 
                "UPDATE users SET unread_notifications = COALESCE(unread_notifications, 0) + 1 WHERE id = $1",
                [$following_id]
            );
            
            if (!$update_counter) {
                throw new Exception(pg_last_error($dbconn));
            }
        }
        
        // Confirmar transação
        pg_query($dbconn, "COMMIT");
        
        // Obter contadores atualizados
        $follower_count_query = pg_query_params($dbconn, "SELECT COUNT(*) FROM followers WHERE following_id = $1", [$following_id]);
        $follower_count = pg_fetch_result($follower_count_query, 0, 0);
        
        $following_count_query = pg_query_params($dbconn, "SELECT COUNT(*) FROM followers WHERE follower_id = $1", [$following_id]);
        $following_count = pg_fetch_result($following_count_query, 0, 0);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Você agora está seguindo este usuário!',
            'action' => 'followed',
            'follower_count' => (int)$follower_count,
            'following_count' => (int)$following_count
        ]);
        
    } else { // unfollow
        // Verificar se realmente está seguindo
        $check_follow = pg_query_params($dbconn, 
            "SELECT id FROM followers WHERE follower_id = $1 AND following_id = $2", 
            [$follower_id, $following_id]
        );
        
        if (pg_num_rows($check_follow) === 0) {
            echo json_encode(['success' => false, 'message' => 'Você não está seguindo este usuário.']);
            pg_query($dbconn, "ROLLBACK");
            exit;
        }
        
        // Remover registro de seguidor
        $delete_follow = pg_query_params($dbconn, 
            "DELETE FROM followers WHERE follower_id = $1 AND following_id = $2", 
            [$follower_id, $following_id]
        );
        
        if (!$delete_follow) {
            throw new Exception(pg_last_error($dbconn));
        }
        
        // Confirmar transação
        pg_query($dbconn, "COMMIT");
        
        // Obter contadores atualizados
        $follower_count_query = pg_query_params($dbconn, "SELECT COUNT(*) FROM followers WHERE following_id = $1", [$following_id]);
        $follower_count = pg_fetch_result($follower_count_query, 0, 0);
        
        $following_count_query = pg_query_params($dbconn, "SELECT COUNT(*) FROM followers WHERE follower_id = $1", [$following_id]);
        $following_count = pg_fetch_result($following_count_query, 0, 0);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Você deixou de seguir este usuário.',
            'action' => 'unfollowed',
            'follower_count' => (int)$follower_count,
            'following_count' => (int)$following_count
        ]);
    }
    
} catch (Exception $e) {
    pg_query($dbconn, "ROLLBACK");
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>