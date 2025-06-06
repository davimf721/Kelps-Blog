<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Você precisa estar logado para excluir posts.";
    header('Location: login.php');
    exit;
}

// Verificar se o ID do post foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Post inválido.";
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Verificar se o post existe e se o usuário tem permissão para excluí-lo
if ($is_admin) {
    // Admin pode excluir qualquer post
    $query = "SELECT * FROM posts WHERE id = $post_id";
} else {
    // Usuário comum só pode excluir seus próprios posts
    $query = "SELECT * FROM posts WHERE id = $post_id AND user_id = $user_id";
}

$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    $_SESSION['error'] = "Post não encontrado ou você não tem permissão para excluí-lo.";
    header('Location: index.php');
    exit;
}

$post = pg_fetch_assoc($result);

// Excluir dados relacionados antes de excluir o post
// 1. Excluir upvotes do post
$delete_upvotes = pg_query($dbconn, "DELETE FROM post_upvotes WHERE post_id = $post_id");

// 2. Excluir comentários do post
$delete_comments = pg_query($dbconn, "DELETE FROM comments WHERE post_id = $post_id");

// 3. Excluir notificações relacionadas ao post
$delete_notifications = pg_query($dbconn, "DELETE FROM notifications WHERE reference_id = $post_id AND type IN ('new_post', 'upvote', 'comment')");

// 4. Finalmente, excluir o post
if ($is_admin) {
    $delete_query = "DELETE FROM posts WHERE id = $post_id";
} else {
    $delete_query = "DELETE FROM posts WHERE id = $post_id AND user_id = $user_id";
}

$delete_result = pg_query($dbconn, $delete_query);

if ($delete_result && pg_affected_rows($delete_result) > 0) {
    $_SESSION['success'] = "Post excluído com sucesso.";
    header('Location: index.php');
    exit;
} else {
    $_SESSION['error'] = "Erro ao excluir o post: " . pg_last_error($dbconn);
    header('Location: post.php?id=' . $post_id);
    exit;
}
?>