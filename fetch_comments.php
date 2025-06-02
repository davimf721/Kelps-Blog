<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
$post_id = (int)$_GET['post_id'];

// Verificar se a coluna is_admin existe
$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                 WHERE table_name='users' AND column_name='is_admin'");
$has_is_admin = pg_num_rows($check_column) > 0;

if ($has_is_admin) {
    // Se a coluna existir, usamos ela normalmente
    $query = "SELECT c.id, c.content, c.created_at, u.username, c.user_id, c.parent_id, u.is_admin
              FROM comments c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.post_id = $post_id 
              ORDER BY c.created_at ASC";
} else {
    // Se não existir, usamos uma versão simplificada da query
    $query = "SELECT c.id, c.content, c.created_at, u.username, c.user_id, c.parent_id
              FROM comments c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.post_id = $post_id 
              ORDER BY c.created_at ASC";
}

$result = pg_query($dbconn, $query);
$comments = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        // Escapar conteúdo para prevenir XSS
        $row['content'] = htmlspecialchars($row['content']);
        $row['username'] = htmlspecialchars($row['username']);
        
        // Adicionar flag para usuário atual poder excluir seu próprio comentário
        $row['can_delete'] = isset($_SESSION['user_id']) && 
                             ($_SESSION['user_id'] == $row['user_id'] || 
                              (isset($_SESSION['is_admin']) && $_SESSION['is_admin']));
        
        // Se não temos info de admin, definir como false
        if (!isset($row['is_admin'])) {
            $row['is_admin'] = false;
        } else {
            $row['is_admin'] = ($row['is_admin'] == 't');
        }
        
        $comments[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($comments);