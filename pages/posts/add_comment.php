<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado.']);
    exit;
}

if (!isset($_POST['post_id']) || !isset($_POST['content']) || empty($_POST['content'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$content = trim($_POST['content']);
$user_id = (int)$_SESSION['user_id'];
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

// Usar prepared statement
$query = "INSERT INTO comments (post_id, user_id, content, parent_id) 
          VALUES ($1, $2, $3, $4) RETURNING id, created_at";
$params = [$post_id, $user_id, $content, $parent_id];

$result = pg_query_params($dbconn, $query, $params);

if ($result) {
    $comment = pg_fetch_assoc($result);
    // Buscar nome do usuário e status de admin
    $user_query = pg_query_params($dbconn, "SELECT username, is_admin FROM users WHERE id = $1", [$user_id]);
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