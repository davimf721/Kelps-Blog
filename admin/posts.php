<?php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

// Verificar se o usuário está logado e é admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "Você não tem permissão para acessar esta área.";
    header("Location: ../index.php");
    exit();
}

// Processar ação de excluir post
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
    
    // Primeiro, excluir upvotes relacionados ao post
    $delete_upvotes = pg_query($dbconn, "DELETE FROM post_upvotes WHERE post_id = $post_id");
    
    // Depois, excluir comentários relacionados ao post
    $delete_comments = pg_query($dbconn, "DELETE FROM comments WHERE post_id = $post_id");
    
    // Excluir notificações relacionadas ao post
    $delete_notifications = pg_query($dbconn, "DELETE FROM notifications WHERE reference_id = $post_id AND type IN ('new_post', 'upvote', 'comment')");
    
    // Finalmente, excluir o post
    $delete_post = pg_query($dbconn, "DELETE FROM posts WHERE id = $post_id");
    
    if ($delete_post) {
        $_SESSION['admin_success'] = "Post excluído com sucesso.";
    } else {
        $_SESSION['admin_error'] = "Erro ao excluir post: " . pg_last_error($dbconn);
    }
    
    // Redirecionar para evitar resubmissão
    header("Location: posts.php");
    exit();
}

// Processar via parâmetro delete_post (compatibilidade com os links existentes)
if (isset($_GET['delete_post']) && is_numeric($_GET['delete_post'])) {
    $post_id = (int)$_GET['delete_post'];
    
    // Primeiro, excluir upvotes relacionados ao post
    $delete_upvotes = pg_query($dbconn, "DELETE FROM post_upvotes WHERE post_id = $post_id");
    
    // Depois, excluir comentários relacionados ao post
    $delete_comments = pg_query($dbconn, "DELETE FROM comments WHERE post_id = $post_id");
    
    // Excluir notificações relacionadas ao post
    $delete_notifications = pg_query($dbconn, "DELETE FROM notifications WHERE reference_id = $post_id AND type IN ('new_post', 'upvote', 'comment')");
    
    // Finalmente, excluir o post
    $delete_post = pg_query($dbconn, "DELETE FROM posts WHERE id = $post_id");
    
    if ($delete_post) {
        $_SESSION['admin_success'] = "Post excluído com sucesso.";
    } else {
        $_SESSION['admin_error'] = "Erro ao excluir post: " . pg_last_error($dbconn);
    }
    
    // Redirecionar para evitar resubmissão
    header("Location: posts.php");
    exit();
}

// Buscar todos os posts
$posts_result = pg_query($dbconn, "SELECT p.id, p.title, p.created_at, u.username, p.user_id,
                                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                                    COALESCE(p.upvotes_count, 0) as upvotes_count
                                   FROM posts p
                                   JOIN users u ON p.user_id = u.id
                                   ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Posts - Kelps Blog</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .admin-header {
            background-color: #212121;
            color: #fff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .admin-header h1 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-section {
            background-color: #2a2a2a;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .admin-section h2 {
            color: #fff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            display: flex;
            gap: 20px;
            background-color: #333;
            border-radius: 5px;
            padding: 10px 20px;
        }
        
        .admin-nav a {
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: #007bff;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #333;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
            color: #fff;
        }
        
        .admin-table th {
            background-color: #444;
            font-weight: bold;
        }
        
        .admin-table tr:hover {
            background-color: #3a3a3a;
        }
        
        .admin-btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 2px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.85em;
            transition: background-color 0.3s;
        }
        
        .admin-btn {
            background-color: #007bff;
            color: #fff;
        }
        
        .admin-btn:hover {
            background-color: #0056b3;
            color: #fff;
        }
        
        .admin-btn.delete {
            background-color: #dc3545;
        }
        
        .admin-btn.delete:hover {
            background-color: #c82333;
        }
        
        .admin-actions {
            white-space: nowrap;
        }
        
        .admin-message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-message.success {
            background-color: rgba(14, 202, 14, 0.1);
            color: #0eca0e;
            border-left: 4px solid #0eca0e;
        }
        
        .admin-message.error {
            background-color: rgba(202, 14, 14, 0.1);
            color: #ca0e0e;
            border-left: 4px solid #ca0e0e;
        }
        
        .table-title {
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .table-title a {
            color: #fff;
            text-decoration: none;
        }
        
        .table-title a:hover {
            color: #007bff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h1>Kelps Blog</h1>
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../profile.php">Perfil</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1><i class="fas fa-shield-alt"></i> Painel de Administração</h1>
            </div>
            
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="users.php">Usuários</a></li>
                    <li><a href="posts.php" class="active">Posts</a></li>
                    <li><a href="comments.php">Comentários</a></li>
                </ul>
            </nav>
            
            <?php if (isset($_SESSION['admin_success'])): ?>
                <div class="admin-message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['admin_success']); ?>
                    <?php unset($_SESSION['admin_success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['admin_error'])): ?>
                <div class="admin-message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
                    <?php unset($_SESSION['admin_error']); ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <h2><i class="fas fa-file-alt"></i> Gerenciar Posts</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Data</th>
                            <th>Comentários</th>
                            <th>Upvotes</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($posts_result && pg_num_rows($posts_result) > 0): ?>
                            <?php while ($post = pg_fetch_assoc($posts_result)): ?>
                                <tr>
                                    <td><?php echo $post['id']; ?></td>
                                    <td class="table-title">
                                        <a href="../post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="../profile.php?user_id=<?php echo $post['user_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($post['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></td>
                                    <td class="text-center"><?php echo $post['comments_count']; ?></td>
                                    <td class="text-center"><?php echo $post['upvotes_count']; ?></td>
                                    <td class="admin-actions">
                                        <a href="../post.php?id=<?php echo $post['id']; ?>" class="admin-btn" target="_blank">Ver</a>
                                        <a href="../edit_post.php?id=<?php echo $post['id']; ?>" class="admin-btn">Editar</a>
                                        <a href="posts.php?delete_post=<?php echo $post['id']; ?>" 
                                           class="admin-btn delete" 
                                           onclick="return confirm('Tem certeza que deseja excluir este post? Esta ação irá excluir também todos os comentários relacionados e é irreversível.');">
                                            Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">Nenhum post encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>
</body>
</html>