<?php
// fetch_comments.php
require_once 'includes/db_connect.php';
$post_id = (int)$_GET['post_id'];
$query = "SELECT c.id, c.content, c.created_at, u.username 
          FROM comments c 
          JOIN users u ON c.user_id = u.id 
          WHERE c.post_id = $post_id 
          ORDER BY c.created_at DESC";
$result = pg_query($dbconn, $query);
$comments = [];
while ($row = pg_fetch_assoc($result)) {
    $comments[] = $row;
}
header('Content-Type: application/json');
echo json_encode($comments);