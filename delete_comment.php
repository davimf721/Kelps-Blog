<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Você precisa estar logado para excluir comentários.";
    header('Location: login.php');
    exit;
}

// Verificar se o ID do comentário foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Comentário inválido.";
    header('Location: index.php');
    exit;
}

$comment_id = (int)$_GET['id'];
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : null;
$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Verificar se o comentário existe e se o usuário tem permissão para excluí-lo
if ($is_admin) {
    // Admin pode excluir qualquer comentário
    $query = "SELECT c.*, p.id as post_id FROM comments c 
              JOIN posts p ON c.post_id = p.id 
              WHERE c.id = $comment_id";
} else {
    // Usuário comum só pode excluir seus próprios comentários
    $query = "SELECT c.*, p.id as post_id FROM comments c 
              JOIN posts p ON c.post_id = p.id 
              WHERE c.id = $comment_id AND c.user_id = $user_id";
}

$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    $_SESSION['error'] = "Comentário não encontrado ou você não tem permissão para excluí-lo.";
    if ($post_id) {
        header("Location: post.php?id=$post_id");
    } else {
        header('Location: index.php');
    }
    exit;
}

$comment = pg_fetch_assoc($result);
$actual_post_id = $post_id ?: $comment['post_id'];

// Excluir o comentário
if ($is_admin) {
    $delete_query = "DELETE FROM comments WHERE id = $comment_id";
} else {
    $delete_query = "DELETE FROM comments WHERE id = $comment_id AND user_id = $user_id";
}

$delete_result = pg_query($dbconn, $delete_query);

if ($delete_result && pg_affected_rows($delete_result) > 0) {
    $_SESSION['success'] = "Comentário excluído com sucesso.";
    header("Location: post.php?id=$actual_post_id");
    exit;
} else {
    $_SESSION['error'] = "Erro ao excluir o comentário: " . pg_last_error($dbconn);
    header("Location: post.php?id=$actual_post_id");
    exit;
}
?>