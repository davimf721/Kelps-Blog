<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado.']);
    exit;
}
if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Comentário inválido.']);
    exit;
}
$comment_id = (int)$_POST['comment_id'];
$user_id = $_SESSION['user_id'];

// Só permite excluir se for o autor ou admin (ajuste conforme sua lógica)
$query = "DELETE FROM comments WHERE id = $comment_id AND user_id = $user_id";
$result = pg_query($dbconn, $query);

if (pg_affected_rows($result) > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Permissão negada ou comentário não encontrado.']);
}