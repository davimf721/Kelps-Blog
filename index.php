<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Incluir Parsedown se existir
if (file_exists('libs/Parsedown.php')) {
    require_once 'libs/Parsedown.php';
}

// Verificar se o usuário está banido (será redirecionado automaticamente se necessário)
check_user_access();

// Definir variáveis para o header
$page_title = 'Kelps Blog';
$current_page = 'home';

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
                
                // Verificar se o usuário está logado (será passado do PHP)
                const isLoggedIn = <?php echo is_logged_in() ? 'true' : 'false'; ?>;
                
                // Limitar o conteúdo do post para preview
                let contentPreview = post.content;
                if (contentPreview.length > 200) {
                    contentPreview = contentPreview.substring(0, 200) + '...';
                }
                
                container.innerHTML += `
                    <article class="post-summary">
                        <h3><a href="post.php?id=${post.id}">${escapeHtml(post.title)}</a></h3>
                        <p class="post-meta">
                            Por: <a href="profile.php?user_id=${post.user_id}" class="author-link">
                                ${escapeHtml(post.author)}${adminBadge}
                            </a> 
                            em ${new Date(post.created_at).toLocaleString('pt-BR')}
                        </p>
                        <div class="post-content-preview">${escapeHtml(contentPreview)}</div>
                        <div class="post-stats">
                            <button class="upvote-button ${post.user_has_upvoted ? 'upvoted' : ''}" 
                                    data-post-id="${post.id}"
                                    ${!isLoggedIn ? 'disabled title="Faça login para dar upvote"' : ''}>
                                <i class="fas fa-arrow-up upvote-icon"></i>
                                <span class="upvote-count">${post.upvotes_count || 0}</span>
                            </button>
                            <span class="comment-count"><i class="far fa-comment"></i> ${post.comments_count || 0} comentários</span>
                        </div>
                       
                    </article>
                `;
            });
            
            // Adicionar event listeners para os botões de upvote
            setupUpvoteButtons();
        })
        .catch(error => {
            console.error('Erro ao carregar posts:', error);
            const container = document.querySelector('.posts-container');
            container.innerHTML = '<div class="error-message">Erro ao carregar posts. Tente novamente mais tarde.</div>';
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
        document.querySelectorAll('.upvote-button').forEach(button => {
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
                        alert(data.message || 'Erro ao processar upvote');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar upvote');
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
});
</script>

<!-- CSS específico para a página inicial já está no style.css principal -->
