<?php
require_once 'includes/db_connect.php';
$query = "SELECT p.id, p.title, p.content, p.created_at, u.username AS author, p.upvotes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
          FROM posts p
          JOIN users u ON p.user_id = u.id
          ORDER BY p.created_at DESC";
$result = pg_query($dbconn, $query);
$posts = [];
while ($row = pg_fetch_assoc($result)) {
    $posts[] = $row;
}
header('Content-Type: application/json');
echo json_encode($posts);