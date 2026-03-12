<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';
require_once 'app/config/translations.php';

// Incluir Parsedown se existir
if (file_exists('libs/Parsedown.php')) {
    require_once 'libs/Parsedown.php';
}

// Verificar se o usuário está banido (será redirecionado automaticamente se necessário)
check_user_access();

// Definir variáveis para o header
$page_title = __('page_title');
$current_page = 'home';
$is_logged = is_logged_in();

// Incluir o header compartilhado (também carrega LanguageManager)
include 'includes/header.php';
?>

<?php if (!$is_logged): ?>
<!-- LANDING PAGE PARA VISITANTES -->
<div class="landing-hero">
    <div class="hero-content">
        <div class="hero-badge"><?php echo __('hero_badge'); ?></div>
        <h1 class="hero-title">
            <?php echo __('hero_title_share'); ?> <span class="gradient-text"><?php echo __('hero_title_knowledge'); ?></span>,
            <br><?php echo __('hero_title_connect'); ?> <span class="gradient-text"><?php echo __('hero_title_people'); ?></span>
        </h1>
        <p class="hero-description">
            <?php echo __('hero_description'); ?>
        </p>
        <div class="hero-actions">
            <a href="register.php" class="btn-primary-hero">
                <i class="fas fa-rocket"></i> <?php echo __('create_free_account'); ?>
            </a>
            <a href="login.php" class="btn-secondary-hero">
                <i class="fas fa-sign-in-alt"></i> <?php echo __('already_have_account'); ?>
            </a>
        </div>
        <div class="hero-stats">
            <?php
            // Buscar estatísticas
            $stats_users = pg_query($dbconn, "SELECT COUNT(*) FROM users");
            $stats_posts = pg_query($dbconn, "SELECT COUNT(*) FROM posts");
            $stats_comments = pg_query($dbconn, "SELECT COUNT(*) FROM comments");
            $total_users = $stats_users ? pg_fetch_result($stats_users, 0, 0) : 0;
            $total_posts = $stats_posts ? pg_fetch_result($stats_posts, 0, 0) : 0;
            $total_comments = $stats_comments ? pg_fetch_result($stats_comments, 0, 0) : 0;
            ?>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($total_users); ?></span>
                <span class="stat-label"><?php echo __('members'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($total_posts); ?></span>
                <span class="stat-label"><?php echo __('publications'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($total_comments); ?></span>
                <span class="stat-label"><?php echo __('comments'); ?></span>
            </div>
        </div>
    </div>
    <div class="hero-visual">
        <div class="floating-cards">
            <div class="floating-card card-1">
                <i class="fas fa-pen-fancy"></i>
                <span><?php echo __('create_posts_card'); ?></span>
            </div>
            <div class="floating-card card-2">
                <i class="fas fa-comments"></i>
                <span><?php echo __('comment_card'); ?></span>
            </div>
            <div class="floating-card card-3">
                <i class="fas fa-heart"></i>
                <span><?php echo __('interact_card'); ?></span>
            </div>
            <div class="floating-card card-4">
                <i class="fas fa-users"></i>
                <span><?php echo __('connect_card'); ?></span>
            </div>
        </div>
    </div>
</div>

<section class="features-section">
    <h2 class="section-title"><?php echo __('why_choose'); ?> <span class="gradient-text">Kelps Blog</span>?</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3><?php echo __('secure_reliable'); ?></h3>
            <p><?php echo __('secure_desc'); ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <h3><?php echo __('fast_modern'); ?></h3>
            <p><?php echo __('fast_desc'); ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-code"></i>
            </div>
            <h3><?php echo __('markdown_support'); ?></h3>
            <p><?php echo __('markdown_desc'); ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-bell"></i>
            </div>
            <h3><?php echo __('notifications_title'); ?></h3>
            <p><?php echo __('notifications_desc'); ?></p>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="cta-content">
        <h2><?php echo __('ready_to_start'); ?></h2>
        <p><?php echo __('ready_desc'); ?></p>
        <a href="register.php" class="btn-cta">
            <i class="fas fa-user-plus"></i> <?php echo __('create_my_account'); ?>
        </a>
    </div>
</section>

<h2 class="section-title" style="margin-top: 40px;">
    <i class="fas fa-fire"></i> <?php echo __('recent_posts'); ?>
</h2>
<?php endif; ?>

<section class="posts-container" id="posts-container"></section>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function fetchAndDisplayPosts(page = 1) {
        fetch('fetch_posts.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta da rede');
            }
            return response.json();
        })
        .then(posts => {
            const container = document.querySelector('.posts-container');
            container.innerHTML = '';
            
            if (!posts || posts.length === 0) {
                container.innerHTML = '<div class="no-posts"><?php echo addslashes(__('no_posts_first')); ?></div>';
                return;
            }
            
            // Exibir apenas os posts da página atual
            const postsPerPage = 10;
            const startIndex = (page - 1) * postsPerPage;
            const endIndex = Math.min(startIndex + postsPerPage, posts.length);
            const currentPagePosts = posts.slice(startIndex, endIndex);
            
            currentPagePosts.forEach(post => {
                const adminBadge = post.is_admin 
                    ? '<span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>' 
                    : '';
                
                // Verificar se o usuário está logado (será passado do PHP)
                const isLoggedIn = <?php echo is_logged_in() ? 'true' : 'false'; ?>;
                const loginToUpvoteMsg = '<?php echo addslashes(__('login_to_upvote')); ?>';
                const readMoreText = '<?php echo addslashes(__('read_more')); ?>';
                const postLocale = '<?php echo __('locale'); ?>';
                
                // Usar o preview já processado pelo servidor
                let contentPreview = post.content || '';
                
                // Criar HTML para imagem se existir
                const imageHtml = post.first_image 
                    ? `<div class="post-image-preview">
                         <a href="post.php?id=${post.id}">
                           <img src="${escapeHtml(post.first_image)}" alt="${escapeHtml(post.title)}" loading="lazy" onerror="this.parentElement.style.display='none'">
                         </a>
                       </div>`
                    : '';
                
                container.innerHTML += `
                    <article class="post-card">
                        ${imageHtml}
                        <div class="post-card-content">
                            <h3 class="post-card-title">
                                <a href="post.php?id=${post.id}">${escapeHtml(post.title)}</a>
                            </h3>
                            <p class="post-card-meta">
                                <span class="post-author-info">
                                    <i class="fas fa-user"></i>
                                    <a href="profile.php?user_id=${post.user_id}" class="author-link">
                                        ${escapeHtml(post.author)}
                                    </a>
                                    ${adminBadge}
                                </span>
                                <span class="post-date-info">
                                    <i class="fas fa-clock"></i>
                                    ${new Date(post.created_at).toLocaleDateString(postLocale)}
                                </span>
                            </p>
                            <p class="post-card-excerpt">${escapeHtml(contentPreview)}</p>
                            <div class="post-card-actions">
                                <button class="upvote-btn ${post.user_has_upvoted ? 'upvoted' : ''}" 
                                        data-post-id="${post.id}"
                                        ${!isLoggedIn ? `disabled title="${loginToUpvoteMsg}"` : ''}>
                                    <i class="fas fa-arrow-up"></i>
                                    <span class="upvote-count">${post.upvotes_count || 0}</span>
                                </button>
                                <span class="comments-info">
                                    <i class="far fa-comment"></i>
                                    ${post.comments_count || 0}
                                </span>
                                <a href="post.php?id=${post.id}" class="read-more-btn">
                                    ${readMoreText} <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                `;
            });
            
            // Adicionar event listeners para os botões de upvote
            setupUpvoteButtons();
        })
        .catch(error => {
            console.error('Error loading posts:', error);
            const container = document.querySelector('.posts-container');
            container.innerHTML = '<div class="error-message"><?php echo addslashes(__('error_loading_posts')); ?></div>';
        });
    }
    
    // Função para escapar HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Função para configurar os botões de upvote
    function setupUpvoteButtons() {
        document.querySelectorAll('.upvote-button, .upvote-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (this.disabled) return;
                
                const postId = this.dataset.postId;
                const countElement = this.querySelector('.upvote-count');
                const currentCount = parseInt(countElement.textContent);
                const isUpvoted = this.classList.contains('upvoted');
                
                // Desabilitar temporariamente para evitar cliques múltiplos
                this.disabled = true;
                
                fetch('upvote_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        action: isUpvoted ? 'remove' : 'add'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar a interface
                        countElement.textContent = data.upvotes_count;
                        this.classList.toggle('upvoted');
                    } else {
                        alert(data.message || '<?php echo addslashes(__('error_upvote')); ?>');
                    }
                })
                .catch(error => {
                    console.error('Upvote error:', error);
                    alert('<?php echo addslashes(__('error_upvote')); ?>');
                })
                .finally(() => {
                    // Reabilitar o botão
                    this.disabled = false;
                });
            });
        });
    }
    
    // Iniciar carregamento dos posts
    fetchAndDisplayPosts();
    
    // ========== EFEITO HYPRLAND - Mouse glow nos cards ==========
    const featureCards = document.querySelectorAll('.feature-card');
    
    featureCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const percentX = (x / rect.width) * 100;
            const percentY = (y / rect.height) * 100;
            
            this.style.setProperty('--mouse-x', percentX + '%');
            this.style.setProperty('--mouse-y', percentY + '%');
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.setProperty('--mouse-x', '50%');
            this.style.setProperty('--mouse-y', '50%');
        });
    });
});
</script>

<!-- CSS específico para a página inicial já está no style.css principal -->
