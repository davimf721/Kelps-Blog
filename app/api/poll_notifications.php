<?php
/**
 * Polling de notificações
 * Retorna notificações novas desde o timestamp informado
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Só responde para usuários logados
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

// Timestamp da última checagem (enviado pelo cliente, validado no servidor)
$since = isset($_GET['since']) ? $_GET['since'] : null;

// Validar formato do timestamp
if ($since !== null) {
    $since = preg_replace('/[^0-9\-: T\.Z]/', '', $since);
    if (strlen($since) < 10) {
        $since = null;
    }
}

// Se não há timestamp, retorna apenas a contagem atual (chamada inicial)
if ($since === null) {
    $count_result = pg_query_params(
        $dbconn,
        "SELECT COUNT(*) FROM notifications WHERE user_id = $1 AND is_read = FALSE",
        [$user_id]
    );
    $count = $count_result ? (int)pg_fetch_result($count_result, 0, 0) : 0;
    echo json_encode(['success' => true, 'unread_count' => $count, 'notifications' => []]);
    exit;
}

// Buscar notificações novas desde o timestamp
$result = pg_query_params(
    $dbconn,
    "SELECT n.id, n.type, n.content, n.reference_id, n.created_at,
            u.username AS sender_username
     FROM notifications n
     LEFT JOIN users u ON n.sender_id = u.id
     WHERE n.user_id = $1
       AND n.created_at > $2::timestamptz
     ORDER BY n.created_at DESC
     LIMIT 10",
    [$user_id, $since]
);

$notifications = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $notifications[] = [
            'id'              => (int)$row['id'],
            'type'            => $row['type'],
            'content'         => $row['content'],
            'reference_id'    => $row['reference_id'],
            'created_at'      => $row['created_at'],
            'sender_username' => $row['sender_username'],
        ];
    }
}

// Contagem total de não lidas
$count_result = pg_query_params(
    $dbconn,
    "SELECT COUNT(*) FROM notifications WHERE user_id = $1 AND is_read = FALSE",
    [$user_id]
);
$unread_count = $count_result ? (int)pg_fetch_result($count_result, 0, 0) : 0;

echo json_encode([
    'success'       => true,
    'unread_count'  => $unread_count,
    'notifications' => $notifications,
]);
