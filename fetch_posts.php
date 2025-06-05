<?php
require_once 'includes/db_connect.php';

// Verificar se a coluna is_admin existe
$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                 WHERE table_name='users' AND column_name='is_admin'");
$has_is_admin = pg_num_rows($check_column) > 0;

// Construir a query base
$base_query = "SELECT p.id, p.title, p.content, p.created_at, p.user_id, p.upvotes_count,
               u.username AS author, COALESCE(u.is_admin, FALSE) as is_admin,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count";

// Se o usuário estiver logado, verificar se ele deu upvote
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $base_query .= ", (SELECT COUNT(*) > 0 FROM post_upvotes WHERE post_id = p.id AND user_id = $user_id) as user_has_upvoted";
} else {
    $base_query .= ", FALSE as user_has_upvoted";
}

$base_query .= " FROM posts p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";

$result = pg_query($dbconn, $base_query);
$posts = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $post = [
            'id' => $row['id'],
            'title' => htmlspecialchars($row['title']),
            'content' => htmlspecialchars(strip_tags($row['content'])),
            'created_at' => $row['created_at'],
            'author' => htmlspecialchars($row['author']),
            'user_id' => $row['user_id'],
            'upvotes_count' => $row['upvotes_count'] ?? 0,
            'comments_count' => $row['comments_count'] ?? 0,
            'is_admin' => $row['is_admin'] == 't',
            'user_has_upvoted' => $row['user_has_upvoted'] ?? false
        ];
        
        $posts[] = $post;
    }
}

header('Content-Type: application/json');
echo json_encode($posts);