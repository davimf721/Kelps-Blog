<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para dar upvote.']);
    exit;
}

// Verificar se o ID do post foi fornecido
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de post inválido.']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Verificar se o post existe
$check_post = pg_query($dbconn, "SELECT id FROM posts WHERE id = $post_id");
if (pg_num_rows($check_post) == 0) {
    echo json_encode(['success' => false, 'message' => 'Post não encontrado.']);
    exit;
}

// Verificar se o usuário já deu upvote
$check_upvote = pg_query($dbconn, "SELECT id FROM post_upvotes WHERE post_id = $post_id AND user_id = $user_id");
$has_upvoted = pg_num_rows($check_upvote) > 0;

if ($has_upvoted) {
    // Remover upvote
    pg_query($dbconn, "DELETE FROM post_upvotes WHERE post_id = $post_id AND user_id = $user_id");
    pg_query($dbconn, "UPDATE posts SET upvotes_count = upvotes_count - 1 WHERE id = $post_id");
    $action = 'removed';
} else {
    // Adicionar upvote
    pg_query($dbconn, "INSERT INTO post_upvotes (post_id, user_id) VALUES ($post_id, $user_id)");
    pg_query($dbconn, "UPDATE posts SET upvotes_count = upvotes_count + 1 WHERE id = $post_id");
    $action = 'added';
}

// Buscar o novo total de upvotes
$result = pg_query($dbconn, "SELECT upvotes_count FROM posts WHERE id = $post_id");
$row = pg_fetch_assoc($result);

echo json_encode([
    'success' => true,
    'action' => $action,
    'count' => $row['upvotes_count'],
    'upvotes' => $row['upvotes_count'] // Para compatibilidade
]);
?>