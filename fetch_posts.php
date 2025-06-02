<?php
require_once 'includes/db_connect.php';

// Verificar se a coluna is_admin existe
$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                 WHERE table_name='users' AND column_name='is_admin'");
$has_is_admin = pg_num_rows($check_column) > 0;

// Buscar todos os posts
if ($has_is_admin) {
    $query = "SELECT p.id, p.title, p.content, p.created_at, p.user_id, u.username AS author, p.upvotes_count,
             (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
             u.is_admin
             FROM posts p
             JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC";
} else {
    $query = "SELECT p.id, p.title, p.content, p.created_at, p.user_id, u.username AS author, p.upvotes_count,
             (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
             FROM posts p
             JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC";
}

$result = pg_query($dbconn, $query);
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
            'comments_count' => $row['comments_count'] ?? 0
        ];
        
        // Adicionar campo is_admin apenas se a coluna existir
        if ($has_is_admin) {
            $post['is_admin'] = $row['is_admin'] == 't';
        } else {
            $post['is_admin'] = false;
        }
        
        $posts[] = $post;
    }
}

header('Content-Type: application/json');
echo json_encode($posts);