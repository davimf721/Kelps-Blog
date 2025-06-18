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

// Definir variáveis para o header
$page_title = "Painel de Administração - Kelps Blog";
$current_page = 'admin';

// Incluir o header padrão
include '../includes/header.php';

// Estatísticas do site
$stats = [
    'users' => 0,
    'posts' => 0,
    'comments' => 0
];

$users_query = pg_query($dbconn, "SELECT COUNT(*) FROM users");
$posts_query = pg_query($dbconn, "SELECT COUNT(*) FROM posts");
$comments_query = pg_query($dbconn, "SELECT COUNT(*) FROM comments");

if ($users_query) $stats['users'] = pg_fetch_result($users_query, 0, 0);
if ($posts_query) $stats['posts'] = pg_fetch_result($posts_query, 0, 0);
if ($comments_query) $stats['comments'] = pg_fetch_result($comments_query, 0, 0);

// Buscar todos os usuários com informação de banimento
$users_result = pg_query($dbconn, "SELECT id, username, email, is_admin, is_banned, created_at FROM users ORDER BY created_at DESC LIMIT 10");
?>

<div class="admin-container"
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #2a2a2a;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #0e86ca;
            font-size: 1rem;
        }
        
        .stat-card p {
            font-size: 2rem;
            margin: 10px 0;
            font-weight: bold;
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
            display: inline-block;
        }
        
        .admin-btn:hover {
            background-color: #0a6aa8;
            color: white;
        }
        
        .admin-btn.delete, .admin-btn.ban {
            background-color: #ca0e0e;
        }
        
        .admin-btn.delete:hover, .admin-btn.ban:hover {
            background-color: #a80a0a;
        }
        
        .admin-btn.unban {
            background-color: #28a745;
        }
        
        .admin-btn.unban:hover {
            background-color: #218838;
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
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="users.php">Usuários</a></li>
                    <li><a href="posts.php">Posts</a></li>
                    <li><a href="comments.php">Comentários</a></li>
                </ul>
            </nav>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>USUÁRIOS</h3>
                    <p><?php echo $stats['users']; ?></p>
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card">
                    <h3>POSTS</h3>
                    <p><?php echo $stats['posts']; ?></p>
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-card">
                    <h3>COMENTÁRIOS</h3>
                    <p><?php echo $stats['comments']; ?></p>
                    <i class="fas fa-comments"></i>
                </div>
            </div>
            
            <section class="admin-section">
                <h2><i class="fas fa-users"></i> Usuários Recentes</h2>
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
                                        <?php if (isset($user['is_banned']) && $user['is_banned'] == 't'): ?>
                                            <span class="admin-badge banned">Banido</span>
                                        <?php else: ?>
                                            <span>Ativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="admin-actions">
                                        <a href="../profile.php?user_id=<?php echo $user['id']; ?>" class="admin-btn">Ver</a>
                                        <?php if ($user['is_admin'] != 't'): ?>
                                            <?php if ($user['is_banned'] == 't'): ?>
                                                <a href="users.php?action=toggle_ban&id=<?php echo $user['id']; ?>" 
                                                   class="admin-btn unban"
                                                   onclick="return confirm('Tem certeza que deseja desbanir este usuário?');">
                                                   Desbanir
                                                </a>
                                            <?php else: ?>
                                                <a href="users.php?action=toggle_ban&id=<?php echo $user['id']; ?>" 
                                                   class="admin-btn ban"
                                                   onclick="return confirm('Tem certeza que deseja banir este usuário?');">
                                                   Banir
                                                </a>
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

<?php include '../includes/footer.php'; ?>

<style>
/* Estilos específicos para o painel admin */
.admin-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 30px;
    background-color: #3a3a3a;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.admin-header {
    background-color: #212121;
    color: #fff;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.admin-header h1 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 2rem;
    color: #fff;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #0e86ca, #4ecdc4);
    color: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(14, 134, 202, 0.3);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(14, 134, 202, 0.4);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 1.2rem;
    opacity: 0.9;
}

.stat-card .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}

.admin-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-section h2 {
    color: #fff;
    margin: 0 0 20px 0;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-table {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    overflow: hidden;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
}

.admin-table th {
    background: rgba(0, 0, 0, 0.3);
    font-weight: 600;
    color: #0e86ca;
}

.admin-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.admin-action-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-right: 8px;
}

.ban-link {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.ban-link:hover {
    background: rgba(220, 53, 69, 0.3);
    border-color: #dc3545;
}

.unban-link {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.unban-link:hover {
    background: rgba(40, 167, 69, 0.3);
    border-color: #28a745;
}

.admin-link {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.admin-link:hover {
    background: rgba(255, 193, 7, 0.3);
    border-color: #ffc107;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-admin {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
}

.badge-banned {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

@media (max-width: 768px) {
    .admin-container {
        margin: 15px;
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .admin-table {
        font-size: 0.9rem;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 8px 10px;
    }
    
    .admin-action-link {
        font-size: 0.8rem;
        padding: 4px 8px;
        margin-bottom: 5px;
        display: block;
        text-align: center;
    }
}
</style>