<?php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    $_SESSION['error'] = "Você precisa estar logado para visualizar notificações.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Marcar todas as notificações como lidas quando o usuário acessa a página
$mark_read = pg_query($dbconn, "
    UPDATE notifications 
    SET is_read = TRUE 
    WHERE user_id = $user_id AND is_read = FALSE
");

// Atualizar o contador de notificações não lidas na sessão
$_SESSION['unread_notifications'] = 0;

// Atualizar o contador no banco de dados
$update_counter = pg_query($dbconn, "UPDATE users SET unread_notifications = 0 WHERE id = $user_id");

// Obter todas as notificações do usuário (mais recentes primeiro)
$notifications_query = "
    SELECT n.id, n.type, n.content, n.reference_id, n.is_read, n.created_at,
           u.username as sender_username, u.id as sender_id
    FROM notifications n
    LEFT JOIN users u ON n.sender_id = u.id
    WHERE n.user_id = $user_id
    ORDER BY n.created_at DESC
    LIMIT 50
";

$notifications_result = pg_query($dbconn, $notifications_query);

// Função para formatar a data relativa
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '') . ' atrás';
    }
    if ($diff->m > 0) {
        return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '') . ' atrás';
    }
    if ($diff->d > 0) {
        if ($diff->d == 1) {
            return 'ontem';
        }
        return $diff->d . ' dias atrás';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    }
    return 'agora mesmo';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="images/file.jpg" type="image/jpg">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .notification-item {
            background-color: #3a3a3a;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            transition: background-color 0.3s;
            position: relative;
        }
        
        .notification-item:hover {
            background-color: #444;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .notification-icon.follow {
            background-color: #28a745;
        }
        
        .notification-icon.post {
            background-color: #007bff;
        }
        
        .notification-icon.comment {
            background-color: #fd7e14;
        }
        
        .notification-icon.system {
            background-color: #6f42c1;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-text {
            margin-bottom: 5px;
        }
        
        .notification-text a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        
        .notification-text a:hover {
            text-decoration: underline;
        }
        
        .notification-time {
            font-size: 0.8em;
            color: #aaa;
        }
        
        .notification-actions {
            margin-top: 10px;
        }
        
        .notification-actions a {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            background-color: #4f4f4f;
            color: #fff;
            transition: background-color 0.3s;
            margin-right: 10px;
        }
        
        .notification-actions a:hover {
            background-color: #2196F3;
        }
        
        .notification-delete {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #aaa;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
            transition: color 0.3s;
        }
        
        .notification-delete:hover {
            color: #dc3545;
        }
        
        .empty-notifications {
            text-align: center;
            padding: 30px;
            background-color: #3a3a3a;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .empty-notifications i {
            font-size: 3em;
            color: #555;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="site-logo">
            <!-- Logo aqui se tiver -->
        </div>
        <h1 class="site-title">Kelps Blog</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="create_post.php">Criar Post</a></li>
                <li><a href="notifications.php" class="active">
                    <i class="fas fa-bell"></i>
                    <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                        <span class="notification-badge"><?php echo $_SESSION['unread_notifications']; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="profile.php">Perfil (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="notifications-container">
            <h2>Suas Notificações</h2>
            
            <?php if ($notifications_result && pg_num_rows($notifications_result) > 0): ?>
                <div class="notification-list">
                    <?php while ($notification = pg_fetch_assoc($notifications_result)): ?>
                        <div class="notification-item<?php echo $notification['is_read'] == 'f' ? ' unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                            <?php
                                // Definir ícone com base no tipo de notificação
                                switch ($notification['type']) {
                                    case 'follow':
                                        $icon_class = 'follow';
                                        $icon = '<i class="fas fa-user-plus"></i>';
                                        break;
                                    case 'post':
                                        $icon_class = 'post';
                                        $icon = '<i class="fas fa-newspaper"></i>';
                                        break;
                                    case 'comment':
                                        $icon_class = 'comment';
                                        $icon = '<i class="fas fa-comment"></i>';
                                        break;
                                    default:
                                        $icon_class = 'system';
                                        $icon = '<i class="fas fa-info-circle"></i>';
                                }
                            ?>
                            <div class="notification-icon <?php echo $icon_class; ?>">
                                <?php echo $icon; ?>
                            </div>
                            
                            <div class="notification-content">
                                <div class="notification-text">
                                    <?php 
                                        // Formatar o conteúdo com links para perfil/post
                                        $content = htmlspecialchars($notification['content']);
                                        
                                        if ($notification['sender_username']) {
                                            $username = htmlspecialchars($notification['sender_username']);
                                            $sender_link = '<a href="profile.php?user_id=' . $notification['sender_id'] . '">' . $username . '</a>';
                                            $content = str_replace($username, $sender_link, $content);
                                        }
                                        
                                        echo $content;
                                    ?>
                                </div>
                                <div class="notification-time">
                                    <?php echo time_elapsed_string($notification['created_at']); ?>
                                </div>
                                
                                <?php if ($notification['reference_id']): ?>
                                    <div class="notification-actions">
                                        <?php if ($notification['type'] === 'follow'): ?>
                                            <a href="profile.php?user_id=<?php echo $notification['reference_id']; ?>">Ver Perfil</a>
                                        <?php elseif ($notification['type'] === 'post'): ?>
                                            <a href="post.php?id=<?php echo $notification['reference_id']; ?>">Ver Post</a>
                                        <?php elseif ($notification['type'] === 'comment'): ?>
                                            <a href="post.php?id=<?php echo $notification['reference_id']; ?>">Ver Comentário</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="notification-delete" title="Excluir notificação">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-notifications">
                    <i class="far fa-bell-slash"></i>
                    <h3>Nenhuma notificação</h3>
                    <p>Você não tem notificações para exibir no momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. All rights reserved.</p>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manipular cliques nos botões de excluir notificação
            document.querySelectorAll('.notification-delete').forEach(button => {
                button.addEventListener('click', function(e) {
                    const notificationItem = this.closest('.notification-item');
                    const notificationId = notificationItem.dataset.id;
                    
                    // Enviar solicitação para excluir a notificação
                    fetch('delete_notification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `notification_id=${notificationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remover o item da interface
                            notificationItem.style.opacity = '0';
                            setTimeout(() => {
                                notificationItem.remove();
                                
                                // Se não houver mais notificações, mostrar mensagem
                                if (document.querySelectorAll('.notification-item').length === 0) {
                                    const emptyNotif = document.createElement('div');
                                    emptyNotif.className = 'empty-notifications';
                                    emptyNotif.innerHTML = `
                                        <i class="far fa-bell-slash"></i>
                                        <h3>Nenhuma notificação</h3>
                                        <p>Você não tem notificações para exibir no momento.</p>
                                    `;
                                    document.querySelector('.notification-list').replaceWith(emptyNotif);
                                }
                            }, 300);
                        } else {
                            alert('Erro ao excluir notificação: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                    });
                });
            });
        });
    </script>
</body>
</html>