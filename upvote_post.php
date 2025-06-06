<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';
require_once 'includes/notification_helper.php'; // Adicionar esta linha

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para dar upvote']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['post_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$post_id = (int)$input['post_id'];
$action = $input['action']; // 'add' ou 'remove'
$user_id = $_SESSION['user_id'];

try {
    // Verificar se o post existe e buscar informações do autor
    $post_check = pg_query_params($dbconn, 
        "SELECT id, title, user_id FROM posts WHERE id = $1", 
        [$post_id]
    );
    
    if (!$post_check || pg_num_rows($post_check) == 0) {
        echo json_encode(['success' => false, 'message' => 'Post não encontrado']);
        exit;
    }
    
    $post_info = pg_fetch_assoc($post_check);

    // Verificar se o usuário já deu upvote
    $upvote_check = pg_query_params($dbconn, 
        "SELECT id FROM post_upvotes WHERE post_id = $1 AND user_id = $2", 
        [$post_id, $user_id]
    );
    
    $has_upvoted = $upvote_check && pg_num_rows($upvote_check) > 0;

    if ($action === 'add' && !$has_upvoted) {
        // Adicionar upvote
        $insert_result = pg_query_params($dbconn,
            "INSERT INTO post_upvotes (post_id, user_id) VALUES ($1, $2)",
            [$post_id, $user_id]
        );
        
        if (!$insert_result) {
            throw new Exception('Erro ao adicionar upvote');
        }
        
        // *** NOVA FUNCIONALIDADE: Notificar autor sobre upvote ***
        notifyUserAboutPostUpvote($dbconn, $post_info['user_id'], $user_id, $post_id, $post_info['title']);
        
    } elseif ($action === 'remove' && $has_upvoted) {
        // Remover upvote
        $delete_result = pg_query_params($dbconn,
            "DELETE FROM post_upvotes WHERE post_id = $1 AND user_id = $2",
            [$post_id, $user_id]
        );
        
        if (!$delete_result) {
            throw new Exception('Erro ao remover upvote');
        }
    }
    
    // Contar upvotes atualizados
    $count_result = pg_query_params($dbconn,
        "SELECT COUNT(*) as count FROM post_upvotes WHERE post_id = $1",
        [$post_id]
    );
    
    $count_row = pg_fetch_assoc($count_result);
    $upvotes_count = (int)$count_row['count'];
    
    // Atualizar contador na tabela posts
    pg_query_params($dbconn,
        "UPDATE posts SET upvotes_count = $1 WHERE id = $2",
        [$upvotes_count, $post_id]
    );
    
    echo json_encode([
        'success' => true,
        'upvotes_count' => $upvotes_count,
        'action_performed' => $action
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>