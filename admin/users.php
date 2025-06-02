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

// Processar ação de banir usuário
if (isset($_GET['action']) && $_GET['action'] == 'toggle_ban' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Verificar se o usuário existe e não é admin
    $check_user = pg_query($dbconn, "SELECT is_admin, is_banned FROM users WHERE id = $user_id");
    if ($check_user && pg_num_rows($check_user) > 0) {
        $user = pg_fetch_assoc($check_user);
        
        // Não permitir banir administradores
        if ($user['is_admin'] == 't') {
            $_SESSION['admin_error'] = "Não é possível banir um administrador.";
        } else {
            $new_status = ($user['is_banned'] == 't') ? 'FALSE' : 'TRUE';
            $update = pg_query($dbconn, "UPDATE users SET is_banned = $new_status WHERE id = $user_id");
            
            if ($update) {
                $_SESSION['admin_success'] = "Status do usuário atualizado com sucesso.";
            } else {
                $_SESSION['admin_error'] = "Erro ao atualizar status do usuário.";
            }
        }
    }
    
    // Redirecionar para evitar resubmissão
    header("Location: users.php");
    exit();
}

// Buscar todos os usuários
$users_result = pg_query($dbconn, "SELECT id, username, email, is_admin, is_banned, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Kelps Blog</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos da página admin... (mesmo do index.php admin) */
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
        
        .admin-btn.delete, .admin-btn.ban {
            background-color: #ca0e0e;
        }
        
        .admin-btn.delete:hover, .admin-btn.ban:hover {
            background-color: #a80a0a;
        }
        
        .admin-btn.unban {
            background-color: #0eca0e;
        }
        
        .admin-btn.unban:hover {
            background-color: #0aa80a;
        }
        
        .admin-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        .admin-badge.admin {
            background-color: #0e86ca;
            color: white;
        }
        
        .admin-badge.banned {
            background-color: #ca0e0e;
            color: white;
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
                    <li><a href="users.php" class="active">Usuários</a></li>
                    <li><a href="posts.php">Posts</a></li>
                    <li><a href="comments.php">Comentários</a></li>
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
                <h2><i class="fas fa-users"></i> Gerenciar Usuários</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Data de Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && pg_num_rows($users_result) > 0): ?>
                            <?php while ($user = pg_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if ($user['is_admin'] == 't'): ?>
                                            <span class="admin-badge admin">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['is_banned'] == 't'): ?>
                                            <span class="admin-badge banned">Banido</span>
                                        <?php else: ?>
                                            <span>Ativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="admin-actions">
                                        <a href="../profile.php?user_id=<?php echo $user['id']; ?>" class="admin-btn">Ver Perfil</a>
                                        <?php if ($user['is_admin'] != 't'): ?>
                                            <?php if ($user['is_banned'] == 't'): ?>
                                                <a href="users.php?action=toggle_ban&id=<?php echo $user['id']; ?>" class="admin-btn unban">Desbanir</a>
                                            <?php else: ?>
                                                <a href="users.php?action=toggle_ban&id=<?php echo $user['id']; ?>" class="admin-btn ban">Banir</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Nenhum usuário encontrado.</td>
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