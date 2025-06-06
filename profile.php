<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Verificar se foi fornecido um ID de usuário na URL
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $profile_user_id = (int)$_GET['user_id'];
    $viewing_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id;
} else {
    // Se não foi fornecido ID, mostrar o próprio perfil (se estiver logado)
    if (isset($_SESSION['user_id'])) {
        $profile_user_id = $_SESSION['user_id'];
        $viewing_own_profile = true;
    } else {
        $_SESSION['error'] = "Perfil não encontrado.";
        header("Location: index.php");
        exit();
    }
}

// Verificar se a tabela followers existe e criar se necessário
$check_followers_table = pg_query($dbconn, "SELECT to_regclass('public.followers')");
$followers_table_exists = (pg_fetch_result($check_followers_table, 0, 0) !== NULL);

if (!$followers_table_exists) {
    $create_followers_table = "
    CREATE TABLE IF NOT EXISTS followers (
        id SERIAL PRIMARY KEY,
        follower_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        following_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(follower_id, following_id)
    )";
    pg_query($dbconn, $create_followers_table);
    
    // Criar índices para melhor performance
    pg_query($dbconn, "CREATE INDEX IF NOT EXISTS idx_followers_follower_id ON followers(follower_id)");
    pg_query($dbconn, "CREATE INDEX IF NOT EXISTS idx_followers_following_id ON followers(following_id)");
}

// Verificar se a tabela user_profiles existe e criar se necessário
$check_table = pg_query($dbconn, "SELECT to_regclass('public.user_profiles')");
$table_exists = (pg_fetch_result($check_table, 0, 0) !== NULL);

if (!$table_exists) {
    $create_profiles_table = "
    CREATE TABLE IF NOT EXISTS user_profiles (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        profile_image TEXT DEFAULT '/images/default-profile.png',
        banner_image TEXT DEFAULT '/images/default-banner.png',
        bio TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT user_profiles_user_id_key UNIQUE (user_id)
    )";
    pg_query($dbconn, $create_profiles_table);
}

// Buscar nome de usuário
$username_query = "SELECT username, is_admin, is_banned FROM users WHERE id = $profile_user_id";
$username_result = pg_query($dbconn, $username_query);

if (!$username_result || pg_num_rows($username_result) == 0) {
    $_SESSION['error'] = "Usuário não encontrado.";
    header("Location: index.php");
    exit();
}

$user_data = pg_fetch_assoc($username_result);
$username_of_profile = $user_data['username'];

// Determina se o PERFIL SENDO VISUALIZADO é de um administrador
$is_profile_admin = !empty($user_data['is_admin']) && ($user_data['is_admin'] === true || $user_data['is_admin'] === 't' || $user_data['is_admin'] === 1 || $user_data['is_admin'] === '1');

// Determina se o PERFIL SENDO VISUALIZADO está banido
$is_profile_banned = !empty($user_data['is_banned']) && ($user_data['is_banned'] === true || $user_data['is_banned'] === 't' || $user_data['is_banned'] === 1 || $user_data['is_banned'] === '1');

// SEMPRE calcular contadores de seguidores - independente de estar logado ou não
$follower_count = 0;
$following_count = 0;
$is_following = false;

// Contar seguidores (quantas pessoas seguem este perfil)
$follower_count_query = pg_query($dbconn, "SELECT COUNT(*) FROM followers WHERE following_id = $profile_user_id");
if ($follower_count_query) {
    $follower_count = (int)pg_fetch_result($follower_count_query, 0, 0);
}

// Contar seguindo (quantas pessoas este perfil segue)
$following_count_query = pg_query($dbconn, "SELECT COUNT(*) FROM followers WHERE follower_id = $profile_user_id");
if ($following_count_query) {
    $following_count = (int)pg_fetch_result($following_count_query, 0, 0);
}

// Verificar se o usuário atual está seguindo este perfil (apenas se estiver logado e não for o próprio perfil)
if (isset($_SESSION['user_id']) && !$viewing_own_profile) {
    $current_user_id = $_SESSION['user_id'];
    
    $follow_check = pg_query($dbconn, "SELECT id FROM followers 
                                    WHERE follower_id = $current_user_id 
                                    AND following_id = $profile_user_id");
    $is_following = ($follow_check && pg_num_rows($follow_check) > 0);
}

// Verificar se já existem dados de perfil para este usuário
$profile_query = "SELECT * FROM user_profiles WHERE user_id = $profile_user_id";
$profile_result = pg_query($dbconn, $profile_query);

// Valores padrão com caminhos absolutos
$profile_image = "/images/default-profile.png";
$banner_image = "/images/default-banner.png";
$bio = "";

// Se houver erro na consulta ou nenhum perfil, criar um se for o próprio perfil
if (!$profile_result || pg_num_rows($profile_result) == 0) {
    if ($viewing_own_profile) {
        $insert_profile = pg_query($dbconn, "INSERT INTO user_profiles (user_id, profile_image, banner_image, bio) 
                                        VALUES ($profile_user_id, '$profile_image', '$banner_image', '$bio')");
    }
} else {
    $profile_data = pg_fetch_assoc($profile_result);
    
    // Garantir que os caminhos comecem com / ou http para funcionar corretamente
    $profile_image = $profile_data['profile_image'];
    
    // Verificar se as imagens estão vazias e usar valores padrão
    if (empty($profile_image)) {
        $profile_image = "/images/default-profile.png";
    } else if (
        !str_starts_with($profile_image, '/') &&
        !str_starts_with($profile_image, 'http') &&
        !str_starts_with($profile_image, 'data:image/')
    ) {
        $profile_image = '/' . $profile_image;
    }
    
    $banner_image = $profile_data['banner_image'];
    if (empty($banner_image)) {
        $banner_image = "/images/default-banner.png";
    } else if (
        !str_starts_with($banner_image, '/') &&
        !str_starts_with($banner_image, 'http') &&
        !str_starts_with($banner_image, 'data:image/')
    ) {
        $banner_image = '/' . $banner_image;
    }
    
    $bio = $profile_data['bio'];
}

// Buscar posts do usuário
$posts_query = "SELECT p.id, p.title, p.content, p.created_at, p.upvotes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
               FROM posts p
               WHERE p.user_id = $profile_user_id
               ORDER BY p.created_at DESC";
$posts_result = pg_query($dbconn, $posts_query);

// Definir variáveis para o header
$page_title = "Perfil de " . htmlspecialchars($username_of_profile) . " - Kelps Blog";
$current_page = 'profile';

// Incluir o header compartilhado
include 'includes/header.php';
?>

<style>
    /* Estilos melhorados para a página de perfil */
    .profile-container {
        max-width: 1000px;
        margin: 0 auto 40px;
        background-color: #222;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }
    
    .profile-banner {
        width: 100%;
        height: 250px;
        background-color: #444;
        background-size: cover;
        background-position: center;
        position: relative;
    }
    
    .profile-banner::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 80px;
        background: linear-gradient(to bottom, rgba(34, 34, 34, 0), rgba(34, 34, 34, 1));
    }
    
    .profile-header {
        position: relative;
        padding: 30px 25px 25px 190px;
        min-height: 150px;
    }
    
    .profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid #222;
        position: absolute;
        top: -75px;
        left: 25px;
        background-size: cover;
        background-position: center;
        z-index: 10;
        background-color: #333;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease;
    }
    
    .profile-image:hover {
        transform: scale(1.03);
    }
    
    .profile-info {
        margin-top: 0;
    }
    
    .profile-username {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        color: #fff;
    }
    
    .admin-badge {
        background-color: #ff9800;
        color: #fff;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7em;
        margin-left: 12px;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .admin-badge i {
        margin-right: 4px;
    }
    
    .profile-stats {
        display: flex;
        gap: 20px;
        margin: 15px 0;
        font-size: 15px;
    }
    
    .stat {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #bbb;
    }
    
    .stat-value {
        font-weight: bold;
        color: #fff;
        font-size: 1.1em;
    }
    
    .profile-bio {
        color: #e0e0e0;
        margin: 20px 0;
        max-width: 700px;
        line-height: 1.6;
        background-color: rgba(0, 0, 0, 0.2);
        padding: 20px;
        border-radius: 8px;
        border-left: 3px solid #0e86ca;
    }
    
    .profile-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
    }
    
    .edit-profile-btn, .follow-btn {
        background-color: #0e86ca;
        color: white;
        padding: 10px 20px;
        border-radius: 30px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(14, 134, 202, 0.3);
    }
    
    .edit-profile-btn:hover {
        background-color: #0a6aa8;
        transform: translateY(-2px);
    }
    
    .follow-btn {
        background-color: #28a745;
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    }
    
    .follow-btn:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }
    
    .follow-btn.unfollow {
        background-color: #dc3545;
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
    }
    
    .follow-btn.unfollow:hover {
        background-color: #c82333;
    }
    
    /* Seção de posts */
    .content-wrapper {
        display: flex;
        gap: 30px;
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .user-posts-section {
        flex: 1;
    }
    
    .sidebar {
        width: 300px;
        flex-shrink: 0;
    }
    
    .section-title {
        font-size: 22px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #0e86ca;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .posts-container {
        display: grid;
        gap: 20px;
    }
    
    .post-summary {
        background-color: #2d2d2d;
        border-radius: 10px;
        padding: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        border-left: 3px solid #0e86ca;
    }
    
    .post-summary:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .post-summary h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 18px;
    }
    
    .post-summary h3 a {
        color: #fff;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .post-summary h3 a:hover {
        color: #0e86ca;
    }
    
    .post-meta {
        font-size: 12px;
        color: #aaa;
        margin-bottom: 10px;
    }
    
    .post-stats {
        margin-top: 15px;
        display: flex;
        gap: 15px;
        color: #aaa;
        font-size: 14px;
    }
    
    .upvote-count, .comment-count {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .read-more-link {
        display: inline-block;
        margin-top: 15px;
        color: #0e86ca;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    
    .read-more-link:hover {
        color: #fff;
        transform: translateX(5px);
    }
    
    .no-posts-message {
        background-color: #2d2d2d;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        color: #aaa;
    }
    
    .sidebar-widget {
        background-color: #2d2d2d;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .sidebar-widget h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
        color: #fff;
        padding-bottom: 10px;
        border-bottom: 1px solid #444;
    }
    
    @media (max-width: 900px) {
        .content-wrapper {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
        }
        
        .profile-header {
            padding-left: 25px;
            padding-top: 100px;
        }
        
        .profile-image {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .profile-image:hover {
            transform: translateX(-50%) scale(1.03);
        }
    }
</style>

<div class="profile-container">
    <div class="profile-banner" style="background-image: url('<?php echo htmlspecialchars($banner_image); ?>');"></div>
    <div class="profile-header">
        <div class="profile-image" style="background-image: url('<?php echo htmlspecialchars($profile_image); ?>');"></div>
        
        <div class="profile-info">
            <h2 class="profile-username">
                <?php echo htmlspecialchars($username_of_profile); ?>
                <?php if ($is_profile_admin): ?>
                    <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                <?php endif; ?>
            </h2>
            
            <div class="profile-stats">
                <div class="stat posts-count">
                    <i class="fas fa-file-alt"></i>
                    <span class="stat-value"><?php echo $posts_result ? pg_num_rows($posts_result) : 0; ?></span> posts
                </div>
                <div class="stat followers-count">
                    <i class="fas fa-user-friends"></i>
                    <span class="stat-value" id="followers-count"><?php echo $follower_count; ?></span> seguidores
                </div>
                <div class="stat following-count">
                    <i class="fas fa-heart"></i>
                    <span class="stat-value" id="following-count"><?php echo $following_count; ?></span> seguindo
                </div>
            </div>
            
            <?php if (!empty(trim($bio))): ?>
            <div class="profile-bio">
                <?php echo nl2br(htmlspecialchars($bio)); ?>
            </div>
            <?php endif; ?>
            
            <div class="profile-actions">
                <?php if ($viewing_own_profile): ?>
                    <a href="edit_profile.php" class="edit-profile-btn">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <?php if ($is_following): ?>
                        <button class="follow-btn unfollow" data-user-id="<?php echo $profile_user_id; ?>">
                            <i class="fas fa-user-minus"></i> Deixar de Seguir
                        </button>
                    <?php else: ?>
                        <button class="follow-btn" data-user-id="<?php echo $profile_user_id; ?>">
                            <i class="fas fa-user-plus"></i> Seguir
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="content-wrapper">
    <div class="user-posts-section">
        <h2 class="section-title">
            <i class="fas fa-file-alt"></i> Posts de <?php echo htmlspecialchars($username_of_profile); ?>
        </h2>
        
        <?php if ($posts_result && pg_num_rows($posts_result) > 0): ?>
            <div class="posts-container">
                <?php while ($post = pg_fetch_assoc($posts_result)): ?>
                    <article class="post-summary">
                        <h3><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                        <p class="post-meta">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                        </p>
                        <p><?php echo substr(strip_tags($post['content']), 0, 150); ?>...</p>
                        <div class="post-stats">
                            <span class="upvote-count"><i class="fas fa-arrow-up"></i> <?php echo $post['upvotes_count'] ?? 0; ?></span>
                            <span class="comment-count"><i class="far fa-comment"></i> <?php echo $post['comments_count'] ?? 0; ?> comentários</span>
                        </div>
                        <!-- <a href="post.php?id=${post.id}" class="read-more-link">
                            Leia mais <i class="fas fa-arrow-right"></i>
                        </a> -->
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-posts-message">
                <i class="fas fa-inbox" style="font-size: 40px; display: block; margin-bottom: 15px; color: #555;"></i>
                <p>Este usuário ainda não publicou posts.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="sidebar">
        <div class="sidebar-widget">
            <h3><i class="fas fa-info-circle"></i> Sobre <?php echo htmlspecialchars($username_of_profile); ?></h3>
            <ul style="list-style: none; padding: 0; margin: 0; color: #ddd;">
                <li style="margin-bottom: 10px;"><i class="fas fa-calendar-alt" style="margin-right: 8px; color: #0e86ca;"></i> Membro desde: 
                    <?php 
                    $join_date_query = pg_query($dbconn, "SELECT created_at FROM users WHERE id = $profile_user_id");
                    if ($join_date_query && $date = pg_fetch_result($join_date_query, 0, 0)) {
                        echo date('d/m/Y', strtotime($date));
                    } else {
                        echo "Desconhecido";
                    }
                    ?>
                </li>
                <li style="margin-bottom: 10px;"><i class="fas fa-star" style="margin-right: 8px; color: #0e86ca;"></i> Status: 
                    <?php
                    if ($is_profile_admin) {
                        echo '<span style="color: #ff9800;">Administrador</span>';
                    } else {
                        if ($is_profile_banned) { // Use a nova variável para banido
                            echo '<span style="color: #dc3545;">Banido</span>';
                        } else {
                            echo '<span style="color: #28a745;">Ativo</span>';
                        }
                    }
                    ?>
                </li>
                <li><i class="fas fa-trophy" style="margin-right: 8px; color: #0e86ca;"></i> Upvotes totais: 
                    <?php 
                    $upvotes_query = pg_query($dbconn, "SELECT SUM(upvotes_count) FROM posts WHERE user_id = $profile_user_id");
                    echo ($upvotes_query && $upvotes = pg_fetch_result($upvotes_query, 0, 0)) ? $upvotes : "0";
                    ?>
                </li>
            </ul>
        </div>
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id): ?>
        <div class="sidebar-widget">
            <h3><i class="fas fa-cog"></i> Ações Rápidas</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="create_post.php" style="background-color: #0e86ca; color: #fff; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold;">
                    <i class="fas fa-plus"></i> Novo Post
                </a>
                <a href="edit_profile.php" style="background-color: #28a745; color: #fff; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold;">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a>
                <?php if ($is_profile_admin): ?>
                <a href="/admin/" style="background-color: #ff9800; color: #fff; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold;">
                    <i class="fas fa-shield-alt"></i> Painel Admin
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php if (isset($_SESSION['user_id']) && !$viewing_own_profile): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const followBtn = document.querySelector('.follow-btn');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const isFollowing = this.classList.contains('unfollow');
                
                // Desabilitar botão durante a requisição
                this.disabled = true;
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                
                // Fazer requisição AJAX para seguir/deixar de seguir
                fetch('follow_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&action=${isFollowing ? 'unfollow' : 'follow'}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar a interface do botão
                        if (isFollowing) {
                            this.innerHTML = '<i class="fas fa-user-plus"></i> Seguir';
                            this.classList.remove('unfollow');
                            // Decrementar contador de seguidores
                            const followersElement = document.getElementById('followers-count');
                            const currentCount = parseInt(followersElement.textContent);
                            followersElement.textContent = Math.max(0, currentCount - 1);
                        } else {
                            this.innerHTML = '<i class="fas fa-user-minus"></i> Deixar de Seguir';
                            this.classList.add('unfollow');
                            // Incrementar contador de seguidores
                            const followersElement = document.getElementById('followers-count');
                            const currentCount = parseInt(followersElement.textContent);
                            followersElement.textContent = currentCount + 1;
                        }
                        
                        // Mostrar mensagem de sucesso temporária
                        showMessage(data.message, 'success');
                    } else {
                        // Restaurar conteúdo original em caso de erro
                        this.innerHTML = originalContent;
                        showMessage('Erro: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    this.innerHTML = originalContent;
                    showMessage('Ocorreu um erro ao processar sua solicitação.', 'error');
                })
                .finally(() => {
                    // Reabilitar botão
                    this.disabled = false;
                });
            });
        }
    });
    
    function showMessage(message, type) {
        // Remover mensagem anterior se existir
        const existingMessage = document.querySelector('.follow-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Criar nova mensagem
        const messageDiv = document.createElement('div');
        messageDiv.className = `follow-message follow-message-${type}`;
        messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        
        // Adicionar estilos
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            ${type === 'success' ? 'background-color: #28a745;' : 'background-color: #dc3545;'}
        `;
        
        document.body.appendChild(messageDiv);
        
        // Animar entrada
        setTimeout(() => {
            messageDiv.style.opacity = '1';
            messageDiv.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover após 3 segundos
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }
</script>
<?php endif; ?>