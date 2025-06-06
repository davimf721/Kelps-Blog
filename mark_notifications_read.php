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

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($input['action'] === 'mark_all_read') {
    // Marcar todas as notificações como lidas
    $update_query = "UPDATE notifications SET is_read = TRUE WHERE user_id = $1 AND is_read = FALSE";
    $update_result = pg_query_params($dbconn, $update_query, [$user_id]);
    
    if ($update_result) {
        // Atualizar contador na tabela users
        $counter_query = "UPDATE users SET unread_notifications = 0 WHERE id = $1";
        pg_query_params($dbconn, $counter_query, [$user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Todas as notificações foram marcadas como lidas.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao marcar notificações como lidas.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
}
?>