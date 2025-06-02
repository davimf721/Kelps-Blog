<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Definir cabeçalho para retornar JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para realizar esta ação.']);
    exit;
}

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

// Validar dados
if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de notificação inválido.']);
    exit;
}

// Verificar se a notificação pertence ao usuário atual
$check_notification = pg_query_params($dbconn, 
    "SELECT id FROM notifications WHERE id = $1 AND user_id = $2",
    [$notification_id, $user_id]
);

if (!$check_notification || pg_num_rows($check_notification) === 0) {
    echo json_encode(['success' => false, 'message' => 'Notificação não encontrada ou não pertence a você.']);
    exit;
}

// Excluir a notificação
$delete_notification = pg_query_params($dbconn,
    "DELETE FROM notifications WHERE id = $1 AND user_id = $2",
    [$notification_id, $user_id]
);

if ($delete_notification) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir notificação: ' . pg_last_error($dbconn)]);
}
?>