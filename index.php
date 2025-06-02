<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'libs/Parsedown.php'; // Incluir o Parsedown
require_once 'includes/auth.php';

// Definir variáveis para o header
$page_title = 'Kelps Blog';
$current_page = 'home';

// Inicializar o Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true); // Ativar modo seguro para prevenir XSS

// Incluir o header compartilhado
include 'includes/header.php';
?>

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
                container.innerHTML = '<div class="no-posts">Nenhum post encontrado. Seja o primeiro a criar um post!</div>';
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
                
                container.innerHTML += `
                    <article class="post-summary">
                        <h3><a href="post.php?id=${post.id}">${post.title}</a></h3>
                        <p class="post-meta">
                            Por: <a href="profile.php?user_id=${post.user_id}" class="author-link">
                                ${post.author}${adminBadge}
                            </a> 
                            em ${new Date(post.created_at).toLocaleString('pt-BR')}
                        </p>
                        <p>${post.content.substring(0, 150)}${post.content.length > 150 ? '...' : ''}</p>
                        <div class="post-stats">
                            <span class="upvote-count"><i class="fas fa-arrow-up"></i> ${post.upvotes_count || 0}</span>
                            <span class="comment-count"><i class="far fa-comment"></i> ${post.comments_count || 0} comentários</span>
                        </div>
                        <a href="post.php?id=${post.id}" class="read-more-link">
                            Leia mais <i class="fas fa-arrow-right"></i>
                        </a>
                    </article>
                `;
            });
        })
        .catch(error => {
            console.error('Erro:', error);
            const container = document.querySelector('.posts-container');
            container.innerHTML = '<div class="error-message">Erro ao carregar posts. Por favor, tente novamente mais tarde.</div>';
        });
    }
    
    // Iniciar carregamento dos posts
    fetchAndDisplayPosts();
});
</script>
