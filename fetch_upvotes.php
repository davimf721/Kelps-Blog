<?php
require_once 'includes/db_connect.php';
$post_id = (int)$_GET['post_id'];
$query = "SELECT COUNT(*) AS upvotes FROM upvotes WHERE post_id = $post_id";
$result = pg_query($dbconn, $query);
$row = pg_fetch_assoc($result);
header('Content-Type: application/json');
echo json_encode(['upvotes' => $row['upvotes']]);