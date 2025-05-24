<?php
session_start();
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para dar upvote']);
    exit;
}

// Verificar se o ID do post foi fornecido
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de post inválido']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Verificar se o post existe
$query = "SELECT id FROM posts WHERE id = $post_id";
$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Post não encontrado']);
    exit;
}

// Verificar se o usuário já deu upvote neste post
$check_query = "SELECT id FROM post_upvotes WHERE post_id = $post_id AND user_id = $user_id";
$check_result = pg_query($dbconn, $check_query);

if (pg_num_rows($check_result) > 0) {
    // Se o usuário já deu upvote, remover o upvote (toggle)
    $delete_query = "DELETE FROM post_upvotes WHERE post_id = $post_id AND user_id = $user_id";
    $delete_result = pg_query($dbconn, $delete_query);
    
    if ($delete_result) {
        // Atualizar o contador de upvotes na tabela de posts
        $update_query = "UPDATE posts SET upvotes_count = upvotes_count - 1 WHERE id = $post_id";
        $update_result = pg_query($dbconn, $update_query);
        
        // Buscar o novo contador de upvotes
        $count_query = "SELECT upvotes_count FROM posts WHERE id = $post_id";
        $count_result = pg_query($dbconn, $count_query);
        $count_row = pg_fetch_assoc($count_result);
        
        echo json_encode([
            'success' => true, 
            'action' => 'removed',
            'message' => 'Upvote removido com sucesso',
            'count' => $count_row['upvotes_count']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover upvote']);
    }
} else {
    // Se o usuário ainda não deu upvote, adicionar o upvote
    $insert_query = "INSERT INTO post_upvotes (post_id, user_id) VALUES ($post_id, $user_id)";
    $insert_result = pg_query($dbconn, $insert_query);
    
    if ($insert_result) {
        // Atualizar o contador de upvotes na tabela de posts
        $update_query = "UPDATE posts SET upvotes_count = upvotes_count + 1 WHERE id = $post_id";
        $update_result = pg_query($dbconn, $update_query);
        
        // Buscar o novo contador de upvotes
        $count_query = "SELECT upvotes_count FROM posts WHERE id = $post_id";
        $count_result = pg_query($dbconn, $count_query);
        $count_row = pg_fetch_assoc($count_result);
        
        echo json_encode([
            'success' => true, 
            'action' => 'added',
            'message' => 'Upvote adicionado com sucesso',
            'count' => $count_row['upvotes_count']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar upvote']);
    }
}
?>