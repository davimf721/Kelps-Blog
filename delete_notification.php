<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado.']);
    exit;
}

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da notificação não fornecido.']);
    exit;
}

$notification_id = (int)$input['notification_id'];
$user_id = $_SESSION['user_id'];

// Verificar se a notificação pertence ao usuário
$check_query = "SELECT id FROM notifications WHERE id = $1 AND user_id = $2";
$check_result = pg_query_params($dbconn, $check_query, [$notification_id, $user_id]);

if (!$check_result || pg_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Notificação não encontrada.']);
    exit;
}

// Deletar a notificação
$delete_query = "DELETE FROM notifications WHERE id = $1 AND user_id = $2";
$delete_result = pg_query_params($dbconn, $delete_query, [$notification_id, $user_id]);

if ($delete_result) {
    // Atualizar contador de notificações não lidas
    $update_counter = pg_query_params($dbconn, 
        "UPDATE users SET unread_notifications = (
            SELECT COUNT(*) FROM notifications WHERE user_id = $1 AND is_read = FALSE
        ) WHERE id = $1",
        [$user_id]
    );
    
    echo json_encode(['success' => true, 'message' => 'Notificação excluída com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir notificação.']);
}
?>