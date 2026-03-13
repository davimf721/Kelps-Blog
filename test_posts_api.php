<?php
// Script de teste para verificar se a API de posts está funcionando
// Sem necessidade de sessão ativa

require_once 'includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'database_connected' => isset($dbconn) && $dbconn ? 'true' : 'false',
];

try {
    // Query para contar posts
    $count_query = "SELECT COUNT(*) as total FROM posts";
    $count_result = pg_query($dbconn, $count_query);
    if ($count_result) {
        $debug['total_posts'] = (int)pg_fetch_result($count_result, 0, 0);
        pg_free_result($count_result);
    } else {
        $debug['error_count'] = pg_last_error($dbconn);
    }
    
    // Query para contar usuários
    $users_query = "SELECT COUNT(*) as total FROM users";
    $users_result = pg_query($dbconn, $users_query);
    if ($users_result) {
        $debug['total_users'] = (int)pg_fetch_result($users_result, 0, 0);
        pg_free_result($users_result);
    }
    
    // Query para contar comentários
    $comments_query = "SELECT COUNT(*) as total FROM comments";
    $comments_result = pg_query($dbconn, $comments_query);
    if ($comments_result) {
        $debug['total_comments'] = (int)pg_fetch_result($comments_result, 0, 0);
        pg_free_result($comments_result);
    }
    
    // Query para pegar últimos 3 posts com detalhes
    $recent_query = "SELECT p.id, p.title, LEFT(p.content, 100) as content_preview, 
                            p.created_at, u.username, p.upvotes_count
                    FROM posts p
                    LEFT JOIN users u ON p.user_id = u.id
                    ORDER BY p.created_at DESC
                    LIMIT 3";
    $recent_result = pg_query($dbconn, $recent_query);
    $debug['recent_posts'] = [];
    if ($recent_result) {
        while ($row = pg_fetch_assoc($recent_result)) {
            $debug['recent_posts'][] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'author' => $row['username'],
                'created_at' => $row['created_at'],
                'upvotes' => $row['upvotes_count']
            ];
        }
        pg_free_result($recent_result);
    } else {
        $debug['error_recent'] = pg_last_error($dbconn);
    }
    
} catch (Exception $e) {
    $debug['exception'] = $e->getMessage();
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
