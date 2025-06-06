<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado.']);
    exit;
}

if (!isset($_POST['post_id']) || !isset($_POST['content']) || empty($_POST['content'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$content = pg_escape_string($dbconn, $_POST['content']);
$user_id = $_SESSION['user_id'];
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';

$query = "INSERT INTO comments (post_id, user_id, content, parent_id) 
          VALUES ($post_id, $user_id, '$content', $parent_id) RETURNING id, created_at";

$result = pg_query($dbconn, $query);

if ($result) {
    $comment = pg_fetch_assoc($result);
    // Buscar nome do usuário e status de admin
    $user_query = pg_query($dbconn, "SELECT username, is_admin FROM users WHERE id = $user_id");
    $user = pg_fetch_assoc($user_query);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'comment' => [
            'id' => $comment['id'],
            'content' => htmlspecialchars($_POST['content']),
            'created_at' => $comment['created_at'],
            'username' => htmlspecialchars($user['username']),
            'user_id' => $user_id,
            'parent_id' => $parent_id == 'NULL' ? null : $parent_id,
            'is_admin' => $user['is_admin'] == 't',
            'can_delete' => true
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar comentário.']);
}