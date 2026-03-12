<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth.php';

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
$username_result = pg_query_params($dbconn, "SELECT username, is_admin, is_banned FROM users WHERE id = $1", [$profile_user_id]);

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
$follower_count_query = pg_query_params($dbconn, "SELECT COUNT(*) FROM followers WHERE following_id = $1", [$profile_user_id]);
if ($follower_count_query) {
    $follower_count = (int)pg_fetch_result($follower_count_query, 0, 0);
}

// Contar seguindo (quantas pessoas este perfil segue)
$following_count_query = pg_query_params($dbconn, "SELECT COUNT(*) FROM followers WHERE follower_id = $1", [$profile_user_id]);
if ($following_count_query) {
    $following_count = (int)pg_fetch_result($following_count_query, 0, 0);
}

// Verificar se o usuário atual está seguindo este perfil (apenas se estiver logado e não for o próprio perfil)
if (isset($_SESSION['user_id']) && !$viewing_own_profile) {
    $current_user_id = (int)$_SESSION['user_id'];
    
    $follow_check = pg_query_params($dbconn, "SELECT id FROM followers WHERE follower_id = $1 AND following_id = $2", [$current_user_id, $profile_user_id]);
    $is_following = ($follow_check && pg_num_rows($follow_check) > 0);
}

// Verificar se já existem dados de perfil para este usuário
$profile_result = pg_query_params($dbconn, "SELECT * FROM user_profiles WHERE user_id = $1", [$profile_user_id]);

// Valores padrão com caminhos absolutos
$profile_image = "/images/default-profile.png";
$banner_image = "/images/default-banner.png";
$bio = "";

// Se houver erro na consulta ou nenhum perfil, criar um se for o próprio perfil
if (!$profile_result || pg_num_rows($profile_result) == 0) {
    if ($viewing_own_profile) {
        $insert_profile = pg_query_params($dbconn, 
            "INSERT INTO user_profiles (user_id, profile_image, banner_image, bio) VALUES ($1, $2, $3, $4)",
            [$profile_user_id, $profile_image, $banner_image, $bio]
        );
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
$posts_result = pg_query_params($dbconn, 
    "SELECT p.id, p.title, p.content, p.created_at, p.upvotes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
    FROM posts p
    WHERE p.user_id = $1
    ORDER BY p.created_at DESC", 
    [$profile_user_id]
);

// Definir variáveis para o header
$page_title = "Perfil de " . htmlspecialchars($username_of_profile) . " - Kelps Blog";
$current_page = 'profile';

// Incluir o header compartilhado
include __DIR__ . '/../../includes/header.php';
?>

<div class="profile-container">
    <div class="profile-banner" style="background-image: url('<?php echo htmlspecialchars($banner_image); ?>');"></div>
    <div class="profile-header">
        <div class="profile-image" style="background-image: url('<?php echo htmlspecialchars($profile_image); ?>');"></div>
        
        <div class="profile-info">
            <div class="profile-top-row">
                <div class="profile-username-section">
                    <h2 class="profile-username">
                        <?php echo htmlspecialchars($username_of_profile); ?>
                        <?php if ($is_profile_admin): ?>
                            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                        <?php endif; ?>
                        <?php if ($is_profile_banned): ?>
                            <span class="banned-badge"><i class="fas fa-ban"></i> Banido</span>
                        <?php endif; ?>
                    </h2>
                    
                    <?php if (!empty(trim($bio))): ?>
                    <div class="profile-bio">
                        <?php echo nl2br(htmlspecialchars($bio)); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $posts_result ? pg_num_rows($posts_result) : 0; ?></span>
                    <span class="stat-label">Posts</span>
                </div>
                <div class="stat-box clickable-stat" data-modal="followers" data-user-id="<?php echo $profile_user_id; ?>">
                    <span class="stat-number" id="followers-count"><?php echo $follower_count; ?></span>
                    <span class="stat-label">Seguidores</span>
                </div>
                <div class="stat-box clickable-stat" data-modal="following" data-user-id="<?php echo $profile_user_id; ?>">
                    <span class="stat-number" id="following-count"><?php echo $following_count; ?></span>
                    <span class="stat-label">Seguindo</span>
                </div>
            </div>
            
            <div class="profile-actions">
                <?php if ($viewing_own_profile): ?>
                    <a href="edit_profile.php" class="btn-profile btn-edit-profile">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <?php if ($is_following): ?>
                        <button class="btn-profile btn-following follow-btn" data-user-id="<?php echo $profile_user_id; ?>">
                            <i class="fas fa-user-check"></i> Seguindo
                        </button>
                    <?php else: ?>
                        <button class="btn-profile btn-follow follow-btn" data-user-id="<?php echo $profile_user_id; ?>">
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
            <div class="user-posts-grid">
                <?php while ($post = pg_fetch_assoc($posts_result)): ?>
                    <article class="user-post-card">
                        <h4><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h4>
                        <p class="post-meta">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                        </p>
                        <div class="post-stats">
                            <span><i class="fas fa-arrow-up"></i> <?php echo $post['upvotes_count'] ?? 0; ?></span>
                            <span><i class="far fa-comment"></i> <?php echo $post['comments_count'] ?? 0; ?></span>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-posts">
                <i class="fas fa-inbox"></i>
                <p>Este usuário ainda não publicou posts.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="sidebar">
        <div class="sidebar-widget">
            <h3><i class="fas fa-info-circle"></i> Sobre <?php echo htmlspecialchars($username_of_profile); ?></h3>
            <ul class="about-list">
                <li><i class="fas fa-calendar-alt"></i> Membro desde: 
                    <?php 
                    $join_date_query = pg_query_params($dbconn, "SELECT created_at FROM users WHERE id = $1", [$profile_user_id]);
                    if ($join_date_query && $date = pg_fetch_result($join_date_query, 0, 0)) {
                        echo date('d/m/Y', strtotime($date));
                    } else {
                        echo "Desconhecido";
                    }
                    ?>
                </li>
                <li><i class="fas fa-star"></i> Status: 
                    <?php
                    if ($is_profile_admin) {
                        echo '<span class="status-admin">Administrador</span>';
                    } else {
                        if ($is_profile_banned) {
                            echo '<span class="status-banned">Banido</span>';
                        } else {
                            echo '<span class="status-active">Ativo</span>';
                        }
                    }
                    ?>
                </li>
                <li><i class="fas fa-trophy"></i> Upvotes totais: 
                    <?php 
                    $upvotes_query = pg_query_params($dbconn, "SELECT COALESCE(SUM(upvotes_count), 0) FROM posts WHERE user_id = $1", [$profile_user_id]);
                    echo ($upvotes_query) ? pg_fetch_result($upvotes_query, 0, 0) : "0";
                    ?>
                </li>
            </ul>
        </div>
        
        <?php if ($viewing_own_profile): ?>
        <div class="sidebar-widget quick-actions">
            <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
            <div class="actions-list">
                <a href="create_post.php" class="action-btn action-primary">
                    <i class="fas fa-plus"></i> Novo Post
                </a>
                <a href="edit_profile.php" class="action-btn action-success">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a>
                <?php if ($is_profile_admin): ?>
                <a href="/admin/" class="action-btn action-warning">
                    <i class="fas fa-shield-alt"></i> Painel Admin
                </a>
                <?php endif; ?>
                <a href="delete_account.php" class="action-btn action-danger">
                    <i class="fas fa-user-times"></i> Excluir Conta
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE SEGUIDORES/SEGUINDO -->
<div id="users-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Seguidores</h2>
            <button class="modal-close" id="close-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modal-body">
            <div class="loading">
                <i class="fas fa-spinner"></i> Carregando...
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('users-modal');
    const closeBtn = document.getElementById('close-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const clickableStats = document.querySelectorAll('.clickable-stat');
    
    // Fechar modal ao clicar no X
    closeBtn.addEventListener('click', function() {
        modal.classList.remove('active');
    });
    
    // Fechar modal ao clicar fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
    
    // Abrir modal ao clicar nos stats
    clickableStats.forEach(stat => {
        stat.addEventListener('click', function() {
            const type = this.dataset.modal;
            const userId = this.dataset.userId;
            
            if (type === 'followers') {
                modalTitle.textContent = 'Seguidores';
                loadFollowers(userId);
            } else if (type === 'following') {
                modalTitle.textContent = 'Seguindo';
                loadFollowing(userId);
            }
            
            modal.classList.add('active');
        });
    });
    
    function loadFollowers(userId) {
        modalBody.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Carregando...</div>';
        
        fetch(`/pages/api/get_followers.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.followers.length === 0) {
                        modalBody.innerHTML = '<div class="empty-users"><p>Nenhum seguidor ainda</p></div>';
                    } else {
                        modalBody.innerHTML = data.followers.map(user => createUserItem(user)).join('');
                    }
                } else {
                    modalBody.innerHTML = '<div class="error"><p>Erro ao carregar seguidores</p></div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                modalBody.innerHTML = '<div class="error"><p>Erro ao carregar seguidores</p></div>';
            });
    }
    
    function loadFollowing(userId) {
        modalBody.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Carregando...</div>';
        
        fetch(`/pages/api/get_following.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.following.length === 0) {
                        modalBody.innerHTML = '<div class="empty-users"><p>Ainda não está seguindo ninguém</p></div>';
                    } else {
                        modalBody.innerHTML = data.following.map(user => createUserItem(user)).join('');
                    }
                } else {
                    modalBody.innerHTML = '<div class="error"><p>Erro ao carregar seguindo</p></div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                modalBody.innerHTML = '<div class="error"><p>Erro ao carregar seguindo</p></div>';
            });
    }
    
    function createUserItem(user) {
        let actionBtn = '';
        
        if (user.is_current_user) {
            // Não mostrar botão para o usuário atual
            actionBtn = '<span class="user-badge">Você</span>';
        } else {
            // Mostrar botão de seguir/deixar de seguir
            actionBtn = `<button class="follow-action-btn ${user.is_following ? 'following' : '' }" data-user-id="${user.id}">
                ${user.is_following ? '<i class="fas fa-user-check"></i> Seguindo' : '<i class="fas fa-user-plus"></i> Seguir'}
            </button>`;
        }
        
        return `
            <div class="user-item">
                <div class="user-info">
                    <a href="/profile.php?user_id=${user.id}" class="user-name">
                        ${user.username}
                        ${user.is_admin ? '<span class="admin-badge-small"><i class="fas fa-shield-alt"></i></span>' : ''}
                    </a>
                </div>
                <div class="user-action">
                    ${actionBtn}
                </div>
            </div>
        `;
    }
    
    // Handler para botões de seguir dentro do modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('.follow-action-btn')) {
            const btn = e.target.closest('.follow-action-btn');
            const userId = btn.dataset.userId;
            const isFollowing = btn.classList.contains('following');
            
            fetch('/follow_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    action: isFollowing ? 'unfollow' : 'follow'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('following');
                    if (isFollowing) {
                        btn.innerHTML = '<i class="fas fa-user-plus"></i> Seguir';
                    } else {
                        btn.innerHTML = '<i class="fas fa-user-check"></i> Seguindo';
                    }
                }
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

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