<?php

/**
 * Função para criar uma notificação
 */
function createNotification($dbconn, $user_id, $type, $content, $sender_id = null, $reference_id = null) {
    $query = "INSERT INTO notifications (user_id, type, content, sender_id, reference_id, created_at) 
              VALUES ($1, $2, $3, $4, $5, NOW())";
    
    $result = pg_query_params($dbconn, $query, [
        $user_id,
        $type,
        $content,
        $sender_id,
        $reference_id
    ]);
    
    if ($result) {
        // Atualizar contador de notificações não lidas
        updateUnreadNotificationsCount($dbconn, $user_id);
        return true;
    }
    
    return false;
}

/**
 * Função para atualizar o contador de notificações não lidas
 */
function updateUnreadNotificationsCount($dbconn, $user_id) {
    $count_query = "SELECT COUNT(*) as unread_count FROM notifications 
                    WHERE user_id = $1 AND is_read = FALSE";
    $count_result = pg_query_params($dbconn, $count_query, [$user_id]);
    
    if ($count_result) {
        $count_row = pg_fetch_assoc($count_result);
        $unread_count = $count_row['unread_count'];
        
        // Atualizar na tabela users
        $update_query = "UPDATE users SET unread_notifications = $1 WHERE id = $2";
        pg_query_params($dbconn, $update_query, [$unread_count, $user_id]);
        
        return $unread_count;
    }
    
    return 0;
}

/**
 * Função para notificar seguidores sobre novo post
 */
function notifyFollowersAboutNewPost($dbconn, $author_id, $post_id, $post_title) {
    // Buscar todos os seguidores do autor
    $followers_query = "SELECT follower_id FROM user_follows WHERE followed_id = $1";
    $followers_result = pg_query_params($dbconn, $followers_query, [$author_id]);
    
    if (!$followers_result) {
        return false;
    }
    
    // Buscar informações do autor
    $author_query = "SELECT username FROM users WHERE id = $1";
    $author_result = pg_query_params($dbconn, $author_query, [$author_id]);
    
    if (!$author_result) {
        return false;
    }
    
    $author = pg_fetch_assoc($author_result);
    $author_username = $author['username'];
    
    // Criar notificação para cada seguidor
    $notifications_sent = 0;
    while ($follower = pg_fetch_assoc($followers_result)) {
        $follower_id = $follower['follower_id'];
        
        // Verificar se o seguidor não é o próprio autor
        if ($follower_id != $author_id) {
            $content = "{$author_username} publicou um novo post: \"{$post_title}\"";
            
            if (createNotification($dbconn, $follower_id, 'new_post', $content, $author_id, $post_id)) {
                $notifications_sent++;
            }
        }
    }
    
    return $notifications_sent;
}

/**
 * Função para notificar sobre novo seguidor
 */
function notifyUserAboutNewFollower($dbconn, $followed_user_id, $follower_id) {
    // Buscar informações do seguidor
    $follower_query = "SELECT username FROM users WHERE id = $1";
    $follower_result = pg_query_params($dbconn, $follower_query, [$follower_id]);
    
    if (!$follower_result) {
        return false;
    }
    
    $follower = pg_fetch_assoc($follower_result);
    $follower_username = $follower['username'];
    
    $content = "{$follower_username} começou a seguir você";
    
    return createNotification($dbconn, $followed_user_id, 'follow', $content, $follower_id, $follower_id);
}

/**
 * Função para notificar sobre novo comentário
 */
function notifyUserAboutNewComment($dbconn, $post_author_id, $commenter_id, $post_id, $post_title) {
    // Verificar se o comentarista não é o próprio autor do post
    if ($post_author_id == $commenter_id) {
        return true; // Não notificar se o autor comentou no próprio post
    }
    
    // Buscar informações do comentarista
    $commenter_query = "SELECT username FROM users WHERE id = $1";
    $commenter_result = pg_query_params($dbconn, $commenter_query, [$commenter_id]);
    
    if (!$commenter_result) {
        return false;
    }
    
    $commenter = pg_fetch_assoc($commenter_result);
    $commenter_username = $commenter['username'];
    
    $content = "{$commenter_username} comentou no seu post \"{$post_title}\"";
    
    return createNotification($dbconn, $post_author_id, 'comment', $content, $commenter_id, $post_id);
}

/**
 * Função para notificar sobre upvote em post
 */
function notifyUserAboutPostUpvote($dbconn, $post_author_id, $upvoter_id, $post_id, $post_title) {
    // Verificar se o upvoter não é o próprio autor do post
    if ($post_author_id == $upvoter_id) {
        return true; // Não notificar se o autor deu upvote no próprio post
    }
    
    // Verificar se já existe uma notificação de upvote deste usuário para este post nas últimas 24 horas
    $check_query = "SELECT id FROM notifications 
                    WHERE user_id = $1 AND sender_id = $2 AND reference_id = $3 AND type = 'upvote'
                    AND created_at > NOW() - INTERVAL '24 hours'";
    $check_result = pg_query_params($dbconn, $check_query, [$post_author_id, $upvoter_id, $post_id]);
    
    if ($check_result && pg_num_rows($check_result) > 0) {
        return true; // Já existe notificação recente, não criar duplicata
    }
    
    // Buscar informações do upvoter
    $upvoter_query = "SELECT username FROM users WHERE id = $1";
    $upvoter_result = pg_query_params($dbconn, $upvoter_query, [$upvoter_id]);
    
    if (!$upvoter_result) {
        return false;
    }
    
    $upvoter = pg_fetch_assoc($upvoter_result);
    $upvoter_username = $upvoter['username'];
    
    $content = "{$upvoter_username} deu upvote no seu post \"{$post_title}\"";
    
    return createNotification($dbconn, $post_author_id, 'upvote', $content, $upvoter_id, $post_id);
}

/**
 * Função para obter notificações de um usuário
 */
function getUserNotifications($dbconn, $user_id, $limit = 50, $offset = 0) {
    $query = "SELECT n.id, n.type, n.content, n.reference_id, n.is_read, n.created_at,
                     u.username as sender_username, u.id as sender_id
              FROM notifications n
              LEFT JOIN users u ON n.sender_id = u.id
              WHERE n.user_id = $1
              ORDER BY n.created_at DESC
              LIMIT $2 OFFSET $3";
              
    return pg_query_params($dbconn, $query, [$user_id, $limit, $offset]);
}

/**
 * Função para marcar notificação como lida
 */
function markNotificationAsRead($dbconn, $notification_id, $user_id) {
    $query = "UPDATE notifications SET is_read = TRUE 
              WHERE id = $1 AND user_id = $2";
    
    $result = pg_query_params($dbconn, $query, [$notification_id, $user_id]);
    
    if ($result) {
        // Atualizar contador
        updateUnreadNotificationsCount($dbconn, $user_id);
        return true;
    }
    
    return false;
}

/**
 * Função para marcar todas as notificações como lidas
 */
function markAllNotificationsAsRead($dbconn, $user_id) {
    $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = $1 AND is_read = FALSE";
    $result = pg_query_params($dbconn, $query, [$user_id]);
    
    if ($result) {
        // Zerar contador
        $update_query = "UPDATE users SET unread_notifications = 0 WHERE id = $1";
        pg_query_params($dbconn, $update_query, [$user_id]);
        return true;
    }
    
    return false;
}

/**
 * Função para deletar uma notificação
 */
function deleteNotification($dbconn, $notification_id, $user_id) {
    $query = "DELETE FROM notifications WHERE id = $1 AND user_id = $2";
    $result = pg_query_params($dbconn, $query, [$notification_id, $user_id]);
    
    if ($result) {
        // Atualizar contador
        updateUnreadNotificationsCount($dbconn, $user_id);
        return true;
    }
    
    return false;
}

?>