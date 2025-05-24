<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// fetch_comments.php
require_once 'includes/db_connect.php';
$post_id = (int)$_GET['post_id'];
$query = "SELECT c.id, c.content, c.created_at, u.username, c.user_id, c.parent_id
          FROM comments c 
          JOIN users u ON c.user_id = u.id 
          WHERE c.post_id = $post_id 
          ORDER BY c.created_at ASC";
$result = pg_query($dbconn, $query);
$comments = [];
while ($row = pg_fetch_assoc($result)) {
    $row['can_delete'] = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];
    $comments[] = $row;
}
header('Content-Type: application/json');
echo json_encode($comments);