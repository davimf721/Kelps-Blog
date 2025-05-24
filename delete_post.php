<?php
session_start();
require_once 'includes/db_connect.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se o ID do post foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verificar se o post pertence ao usuário
$query = "SELECT * FROM posts WHERE id = $post_id AND user_id = $user_id";
$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    // O post não existe ou o usuário não é o autor
    $_SESSION['error'] = "Você não tem permissão para excluir este post.";
    header('Location: index.php');
    exit;
}

// Se chegou até aqui, o post existe e pertence ao usuário. Podemos excluir.
$delete_query = "DELETE FROM posts WHERE id = $post_id AND user_id = $user_id";
$delete_result = pg_query($dbconn, $delete_query);

if ($delete_result) {
    // Post excluído com sucesso
    $_SESSION['success'] = "Post excluído com sucesso.";
    header('Location: index.php');
    exit;
} else {
    // Erro ao excluir o post
    $_SESSION['error'] = "Erro ao excluir o post: " . pg_last_error($dbconn);
    header('Location: edit_post.php?id=' . $post_id);
    exit;
}
?>