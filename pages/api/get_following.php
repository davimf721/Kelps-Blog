<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'user_id inválido']);
    exit;
}

$user_id = (int)$_GET['user_id'];
$current_user_id = $_SESSION['user_id'] ?? null;

// Buscar quem este usuário está seguindo
$query = "
    SELECT u.id, u.username, u.is_admin
    FROM users u
    INNER JOIN followers f ON u.id = f.following_id
    WHERE f.follower_id = $1
    ORDER BY f.created_at DESC
";

$result = pg_query_params($dbconn, $query, [$user_id]);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar seguindo']);
    exit;
}

$following = [];
while ($row = pg_fetch_assoc($result)) {
    $is_following = false;
    
    // Verificar se o usuário atual segue esta pessoa
    if ($current_user_id && $current_user_id != $row['id']) {
        $follow_check = pg_query_params(
            $dbconn,
            "SELECT id FROM followers WHERE follower_id = $1 AND following_id = $2",
            [$current_user_id, $row['id']]
        );
        $is_following = pg_num_rows($follow_check) > 0;
    }
    
    $following[] = [
        'id' => $row['id'],
        'username' => htmlspecialchars($row['username']),
        'is_admin' => $row['is_admin'] === 't' || $row['is_admin'] === true,
        'is_following' => $is_following,
        'is_current_user' => $current_user_id == $row['id']
    ];
}

echo json_encode([
    'success' => true,
    'following' => $following,
    'count' => count($following)
]);
