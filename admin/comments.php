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

// Processar ação de excluir comentário
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $comment_id = (int)$_GET['id'];
    
    // Excluir comentário
    $delete_comment = pg_query($dbconn, "DELETE FROM comments WHERE id = $comment_id");
    
    if ($delete_comment) {
        $_SESSION['admin_success'] = "Comentário excluído com sucesso.";
    } else {
        $_SESSION['admin_error'] = "Erro ao excluir comentário.";
    }
    
    // Redirecionar para evitar resubmissão
    header("Location: comments.php");
    exit();
}

// Buscar todos os comentários
$comments_result = pg_query($dbconn, "SELECT c.id, c.content, c.created_at, 
                                      u.username, u.id as user_id, 
                                      p.id as post_id, p.title as post_title
                                      FROM comments c
                                      JOIN users u ON c.user_id = u.id
                                      JOIN posts p ON c.post_id = p.id
                                      ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Comentários - Kelps Blog</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Mesmos estilos das outras páginas de admin */
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
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        
        .admin-table th {
            background-color: #1a1a1a;
            color: #0e86ca;
        }
        
        .admin-table tr:hover {
            background-color: #333;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .admin-btn {
            background-color: #0e86ca;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .admin-btn:hover {
            background-color: #0a6aa8;
        }
        
        .admin-btn.delete {
            background-color: #ca0e0e;
        }
        
        .admin-btn.delete:hover {
            background-color: #a80a0a;
        }
        
        .admin-nav {
            margin-bottom: 20px;
        }
        
        .admin-nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
            padding: 0;
            margin: 0;
            background-color: #212121;
            border-radius: 5px;
            padding: 10px;
        }
        
        .admin-nav a {
            color: #0e86ca;
            text-decoration: none;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: #333;
        }
        
        .admin-message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 3px;
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
        
        .table-content {
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
                    <li><a href="posts.php">Posts</a></li>
                    <li><a href="comments.php" class="active">Comentários</a></li>
                </ul>
            </nav>
            
            <?php if (isset($_SESSION['admin_success'])): ?>
                <div class="admin-message success">
                    <?php echo htmlspecialchars($_SESSION['admin_success']); ?>
                    <?php unset($_SESSION['admin_success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['admin_error'])): ?>
                <div class="admin-message error">
                    <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
                    <?php unset($_SESSION['admin_error']); ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <h2><i class="fas fa-comments"></i> Gerenciar Comentários</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Conteúdo</th>
                            <th>Autor</th>
                            <th>Post</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($comments_result && pg_num_rows($comments_result) > 0): ?>
                            <?php while ($comment = pg_fetch_assoc($comments_result)): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td class="table-content"><?php echo htmlspecialchars(mb_substr($comment['content'], 0, 50)); ?><?php echo strlen($comment['content']) > 50 ? '...' : ''; ?></td>
                                    <td>
                                        <a href="../profile.php?user_id=<?php echo $comment['user_id']; ?>">
                                            <?php echo htmlspecialchars($comment['username']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="../post.php?id=<?php echo $comment['post_id']; ?>">
                                            <?php echo htmlspecialchars(mb_substr($comment['post_title'], 0, 30)); ?><?php echo strlen($comment['post_title']) > 30 ? '...' : ''; ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                                    <td class="admin-actions">
                                        <a href="../post.php?id=<?php echo $comment['post_id']; ?>" class="admin-btn">Ver Post</a>
                                        <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" 
                                           class="admin-btn delete" 
                                           onclick="return confirm('Tem certeza que deseja excluir este comentário? Esta ação não pode ser desfeita.')">
                                            Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Nenhum comentário encontrado.</td>
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