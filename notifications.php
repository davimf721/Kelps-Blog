<?php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
if (!is_logged_in()) {
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

// Incluir helper de notificações se existir
if (file_exists('includes/notification_helper.php')) {
    require_once 'includes/notification_helper.php';
}

// Definir variáveis para o header
$page_title = 'Notificações - Kelps Blog';
$current_page = 'notifications';

// Incluir o header compartilhado
include 'includes/header.php';
?>

<main>
    <div class="notifications-container">
        <div class="notifications-header">
            <h2><i class="fas fa-bell"></i> Suas Notificações</h2>
            <?php if ($notifications_result && pg_num_rows($notifications_result) > 0): ?>
                <button class="mark-all-read-btn" onclick="markAllAsRead()">
                    <i class="fas fa-check-double"></i> Marcar todas como lidas
                </button>
            <?php endif; ?>
        </div>
        
        <?php if ($notifications_result && pg_num_rows($notifications_result) > 0): ?>
            <div class="notification-list">
                <?php while ($notification = pg_fetch_assoc($notifications_result)): ?>
                    <div class="notification-item<?php echo $notification['is_read'] == 'f' ? ' unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                        <?php
                            // Definir ícone e classe com base no tipo de notificação
                            switch ($notification['type']) {
                                case 'follow':
                                    $icon_class = 'follow';
                                    $icon = '<i class="fas fa-user-plus"></i>';
                                    break;
                                case 'new_post':
                                    $icon_class = 'new_post';
                                    $icon = '<i class="fas fa-newspaper"></i>';
                                    break;
                                case 'comment':
                                    $icon_class = 'comment';
                                    $icon = '<i class="fas fa-comment"></i>';
                                    break;
                                case 'upvote':
                                    $icon_class = 'upvote';
                                    $icon = '<i class="fas fa-arrow-up"></i>';
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
                                <i class="far fa-clock"></i>
                                <?php echo time_elapsed_string($notification['created_at']); ?>
                            </div>
                            
                            <?php if ($notification['reference_id']): ?>
                                <div class="notification-actions">
                                    <?php if ($notification['type'] === 'follow'): ?>
                                        <a href="profile.php?user_id=<?php echo $notification['reference_id']; ?>">
                                            <i class="fas fa-user"></i> Ver Perfil
                                        </a>
                                    <?php elseif (in_array($notification['type'], ['new_post', 'comment', 'upvote'])): ?>
                                        <a href="post.php?id=<?php echo $notification['reference_id']; ?>">
                                            <i class="fas fa-external-link-alt"></i> Ver Post
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button class="notification-delete" title="Excluir notificação" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-notifications">
                <i class="far fa-bell-slash"></i>
                <h3>Nenhuma notificação</h3>
                <p>Você não tem notificações para exibir no momento.<br>
                Siga outros usuários para receber notificações sobre seus posts!</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Função para deletar notificação individual
    function deleteNotification(notificationId) {
        const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
        
        if (!notificationItem) return;
        
        fetch('delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remover o item da interface com animação
                notificationItem.style.opacity = '0';
                notificationItem.style.transform = 'translateX(-100%)';
                
                setTimeout(() => {
                    notificationItem.remove();
                    
                    // Se não houver mais notificações, mostrar mensagem
                    if (document.querySelectorAll('.notification-item').length === 0) {
                        const emptyNotif = document.createElement('div');
                        emptyNotif.className = 'empty-notifications';
                        emptyNotif.innerHTML = `
                            <i class="far fa-bell-slash"></i>
                            <h3>Nenhuma notificação</h3>
                            <p>Você não tem notificações para exibir no momento.<br>
                            Siga outros usuários para receber notificações sobre seus posts!</p>
                        `;
                        document.querySelector('.notification-list').replaceWith(emptyNotif);
                        
                        // Esconder header de ações
                        const markAllBtn = document.querySelector('.mark-all-read-btn');
                        if (markAllBtn) {
                            markAllBtn.style.display = 'none';
                        }
                    }
                }, 300);
            } else {
                alert('Erro ao excluir notificação: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir notificação');
        });
    }
    
    // Função para marcar todas como lidas
    function markAllAsRead() {
        fetch('mark_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'mark_all_read' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remover classe unread de todas as notificações
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Esconder botão
                const markAllBtn = document.querySelector('.mark-all-read-btn');
                if (markAllBtn) {
                    markAllBtn.style.display = 'none';
                }
                
                // Atualizar contador no header (se existir)
                const notificationBadge = document.querySelector('.notification-badge');
                if (notificationBadge) {
                    notificationBadge.style.display = 'none';
                }
            } else {
                alert('Erro ao marcar notificações como lidas: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao marcar notificações como lidas');
        });
    }
</script>

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
    transition: all 0.3s ease;
    position: relative;
    border-left: 4px solid transparent;
}

.notification-item:hover {
    background-color: #444;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.notification-item.unread {
    border-left-color: #007bff;
    background-color: rgba(0, 123, 255, 0.05);
}

.notification-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
    font-size: 1.1em;
}

.notification-icon.follow {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.notification-icon.new_post {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.notification-icon.comment {
    background: linear-gradient(135deg, #fd7e14, #e55a4e);
}

.notification-icon.upvote {
    background: linear-gradient(135deg, #6f42c1, #e83e8c);
}

.notification-icon.system {
    background: linear-gradient(135deg, #6c757d, #495057);
}

.notification-content {
    flex-grow: 1;
}

.notification-text {
    margin-bottom: 8px;
    line-height: 1.5;
    color: #e0e0e0;
}

.notification-text a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

.notification-text a:hover {
    text-decoration: underline;
    color: #0056b3;
}

.notification-time {
    font-size: 0.85em;
    color: #aaa;
    display: flex;
    align-items: center;
    gap: 5px;
}

.notification-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.notification-actions a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85em;
    background-color: #4f4f4f;
    color: #fff;
    transition: all 0.3s ease;
    border: 1px solid #666;
}

.notification-actions a:hover {
    background-color: #007bff;
    border-color: #007bff;
    transform: translateY(-1px);
}

.notification-delete {
    position: absolute;
    top: 12px;
    right: 12px;
    color: #666;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1em;
    transition: all 0.3s ease;
    padding: 4px;
    border-radius: 4px;
}

.notification-delete:hover {
    color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}

.empty-notifications {
    text-align: center;
    padding: 40px 30px;
    background-color: #3a3a3a;
    border-radius: 12px;
    margin-top: 20px;
}

.empty-notifications i {
    font-size: 3.5em;
    color: #555;
    margin-bottom: 20px;
    opacity: 0.7;
}

.empty-notifications h3 {
    color: #ccc;
    margin-bottom: 10px;
}

.empty-notifications p {
    color: #999;
    line-height: 1.5;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #444;
}

.notifications-header h2 {
    margin: 0;
    color: #fff;
}

.mark-all-read-btn {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.mark-all-read-btn:hover {
    background: linear-gradient(135deg, #20c997, #17a2b8);
    transform: translateY(-1px);
}

/* Responsividade */
@media (max-width: 768px) {
    .notifications-container {
        margin: 10px;
        padding: 15px;
    }
    
    .notification-item {
        padding: 12px;
        flex-direction: column;
        text-align: left;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        margin-right: 0;
        margin-bottom: 10px;
        align-self: flex-start;
    }
    
    .notification-delete {
        position: static;
        align-self: flex-end;
        margin-top: 10px;
    }
    
    .notifications-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .notification-actions {
        justify-content: flex-start;
    }
}
</style>

<?php include 'includes/footer.php'; ?>