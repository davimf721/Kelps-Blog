<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'libs/Parsedown.php'; // Incluir o Parsedown
require_once 'includes/auth.php';
require_once 'includes/notification_helper.php'; // Adicionar esta linha

// Verificar se o ID do post foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Buscar post do banco de dados
$query = "SELECT p.id, p.title, p.content, p.created_at, u.username AS author, p.user_id, p.upvotes_count,
          COALESCE(u.is_admin, FALSE) as author_is_admin
          FROM posts p
          JOIN users u ON p.user_id = u.id
          WHERE p.id = $post_id";

$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    // Talvez uma mensagem de erro mais amigável ou log
    $_SESSION['error'] = "Post não encontrado.";
    header('Location: index.php');
    exit;
}

$post = pg_fetch_assoc($result);

// Definir variáveis para o header
$page_title = htmlspecialchars($post['title']) . ' - Kelps Blog';
$current_page = 'post';

// Verificar se o usuário atual é o autor do post
$is_author = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id'];

// Inicializar o Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true); // Ativar modo seguro para prevenir XSS

// Verifica se o usuário atual já deu upvote neste post
$has_upvoted = false;
if (isset($_SESSION['user_id'])) {
    $upvote_check_query = "SELECT id FROM post_upvotes WHERE post_id = $1 AND user_id = $2";
    $upvote_result = pg_query_params($dbconn, $upvote_check_query, array($post['id'], $_SESSION['user_id']));
    $has_upvoted = $upvote_result && pg_num_rows($upvote_result) > 0;
}

// Buscar comentários do post
$comments_query_sql = "SELECT c.id, c.content, c.created_at, c.user_id, u.username,
                       COALESCE(u.is_admin, FALSE) as is_admin
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.post_id = $1
                       ORDER BY c.created_at DESC";
$comments_result = pg_query_params($dbconn, $comments_query_sql, array($post_id));

// Processar o envio de um novo comentário
$comment_error = '';
if (isset($_SESSION['comment_error'])) {
    $comment_error = $_SESSION['comment_error'];
    unset($_SESSION['comment_error']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content']) && isset($_SESSION['user_id'])) {
    $comment_content = trim($_POST['comment_content']);
    
    if (empty($comment_content)) {
        $_SESSION['comment_error'] = "O comentário não pode estar vazio.";
    } else {
        $user_id_comment = $_SESSION['user_id'];
        // Usar pg_query_params para segurança
        $insert_query = "INSERT INTO comments (post_id, user_id, content) VALUES ($1, $2, $3)";
        $insert_result = pg_query_params($dbconn, $insert_query, array($post_id, $user_id_comment, $comment_content));
        
        if ($insert_result) {
            // *** NOVA FUNCIONALIDADE: Notificar autor do post sobre novo comentário ***
            notifyUserAboutNewComment($dbconn, $post['user_id'], $user_id_comment, $post_id, $post['title']);
            
            // Limpar erro da sessão se houver
            unset($_SESSION['comment_error']);
            header("Location: post.php?id=$post_id&comment_added=true#comments-section");
            exit;
        } else {
            $_SESSION['comment_error'] = "Erro ao adicionar comentário: " . pg_last_error($dbconn);
        }
    }
    // Se houve erro no POST, recarregar para mostrar o erro
    header("Location: post.php?id=$post_id#comment-form-area");
    exit;
}

// Incluir o header compartilhado
include 'includes/header.php';
?>

<div class="container post-view-container">
    <article class="full-post-content">
        <header class="post-header">
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta-info">
                <span class="post-author">
                    Publicado por 
                    <a href="profile.php?user_id=<?php echo $post['user_id']; ?>" class="author-name-link">
                        <?php echo htmlspecialchars($post['author']); ?>
                        <?php if ($post['author_is_admin'] === true || $post['author_is_admin'] === 't'): ?>
                            <span class="admin-badge-inline">
                                <i class="fas fa-shield-alt"></i> Admin
                            </span>
                        <?php endif; ?>
                    </a>
                </span>
                <time class="post-date" datetime="<?php echo date('Y-m-d\TH:i:s', strtotime($post['created_at'])); ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('d/m/Y \à\s H:i', strtotime($post['created_at'])); ?>
                </time>
            </div>
            
            <?php if ($is_author || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
                <div class="post-management-actions">
                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="action-button edit-button">
                        <i class="fas fa-edit"></i> Editar Post
                    </a>
                    <a href="delete_post.php?id=<?php echo $post['id']; ?>" 
                       class="action-button delete-button" 
                       onclick="return confirm('⚠️ ATENÇÃO!\n\nTem certeza que deseja excluir este post?\n\n• O post será permanentemente removido\n• Todos os comentários serão excluídos\n• Esta ação não pode ser desfeita\n\nClique em OK para confirmar a exclusão.');">
                        <i class="fas fa-trash-alt"></i> 
                        Excluir Post<?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && !$is_author) ? ' (Admin)' : ''; ?>
                    </a>
                </div>
            <?php endif; ?>
        </header>

        <div class="post-body markdown-content">
            <?php echo $parsedown->text($post['content']); ?>
        </div>

        <footer class="post-footer-feedback">
            <button class="upvote-button <?php echo $has_upvoted ? 'upvoted' : ''; ?>" 
                    data-post-id="<?php echo $post['id']; ?>"
                    <?php echo !is_logged_in() ? 'disabled title="Faça login para dar upvote"' : ''; ?>
                    aria-label="<?php echo $has_upvoted ? 'Remover upvote' : 'Dar upvote'; ?>">
                <i class="fas fa-arrow-up upvote-icon"></i>
                <span class="upvote-count"><?php echo (int)$post['upvotes_count']; ?></span>
                <span class="upvote-text">Upvotes</span>
            </button>
            
            <div class="comments-count-display">
                <i class="far fa-comment"></i> 
                <span class="comments-text">
                    <?php 
                    $comment_count = $comments_result ? pg_num_rows($comments_result) : 0;
                    echo $comment_count . ' ' . ($comment_count === 1 ? 'Comentário' : 'Comentários');
                    ?>
                </span>
            </div>
        </footer>
    </article>

    <section id="comments-section" class="comments-area">
        <h2>
            <span class="comments-title">Comentários</span>
            <span class="comments-counter">(<?php echo $comment_count; ?>)</span>
        </h2>
        
        <?php if (is_logged_in()): ?>
            <div id="comment-form-area" class="comment-form-wrapper">
                <h3>
                    <i class="fas fa-pen"></i>
                    Deixe seu comentário
                </h3>
                
                <?php if (!empty($comment_error)): ?>
                    <div class="error-message alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($comment_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['comment_added']) && $_GET['comment_added'] == 'true'): ?>
                    <div class="success-message alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Comentário adicionado com sucesso!
                    </div>
                <?php endif; ?>
                
                <form action="post.php?id=<?php echo $post_id; ?>" method="post" class="new-comment-form">
                    <div class="form-group">
                        <label for="comment_content" class="sr-only">Seu comentário</label>
                        <textarea name="comment_content" 
                                  id="comment_content"
                                  rows="5" 
                                  required 
                                  placeholder="Compartilhe sua opinião, faça uma pergunta ou contribua para a discussão..."
                                  aria-label="Escreva seu comentário"
                                  maxlength="1000"></textarea>
                        <div class="character-counter">
                            <span id="char-count">0</span>/1000 caracteres
                        </div>
                    </div>
                    <button type="submit" class="submit-button primary-button">
                        <i class="fas fa-paper-plane"></i> 
                        Publicar Comentário
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt-comment">
                <div class="login-prompt-content">
                    <i class="fas fa-user-lock"></i>
                    <p>
                        Para participar da discussão, por favor 
                        <a href="login.php?redirect=<?php echo urlencode('post.php?id=' . $post_id); ?>">faça login</a> 
                        ou 
                        <a href="register.php">crie uma conta gratuita</a>.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="comments-list-wrapper">
            <?php if ($comments_result && pg_num_rows($comments_result) > 0): ?>
                <?php $comment_index = 0; ?>
                <?php while ($comment = pg_fetch_assoc($comments_result)): ?>
                    <?php $comment_index++; ?>
                    <article class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                        <header class="comment-author-info">
                            <div class="comment-author-details">
                                <a href="profile.php?user_id=<?php echo $comment['user_id']; ?>" class="author-name-link">
                                    <i class="fas fa-user-circle"></i>
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </a>
                                <?php if ($comment['is_admin'] === true || $comment['is_admin'] === 't'): ?>
                                    <span class="admin-badge-inline">
                                        <i class="fas fa-shield-alt"></i> Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                            <time class="comment-timestamp" datetime="<?php echo date('Y-m-d\TH:i:s', strtotime($comment['created_at'])); ?>">
                                <i class="fas fa-clock"></i>
                                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                            </time>
                        </header>
                        
                        <div class="comment-text markdown-content">
                            <?php echo $parsedown->text($comment['content']); ?>
                        </div>
                        
                        <?php if (is_logged_in() && ($_SESSION['user_id'] == $comment['user_id'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']))): ?>
                            <footer class="comment-actions">
                                <a href="delete_comment.php?id=<?php echo $comment['id']; ?>&post_id=<?php echo $post_id; ?>" 
                                   class="action-link delete-comment-link"
                                   onclick="return confirm('Tem certeza que deseja excluir este comentário?\n\nEsta ação não pode ser desfeita.');">
                                   <i class="fas fa-trash-alt"></i>
                                   Excluir<?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $_SESSION['user_id'] != $comment['user_id']) ? ' (Admin)' : ''; ?>
                                </a>
                            </footer>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-comments-message">
                    <div class="no-comments-content">
                        <i class="far fa-comment-dots"></i>
                        <p>Ainda não há comentários neste post.</p>
                        <p class="no-comments-cta">Seja o primeiro a compartilhar sua opinião!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <nav class="page-navigation-links" aria-label="Navegação da página">
        <a href="index.php" class="back-to-home-link">
            <i class="fas fa-chevron-left"></i> 
            Voltar para todos os posts
        </a>
    </nav>
</div>

<script>
// Script melhorado para upvote e interações
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidade de upvote
    const upvoteButton = document.querySelector('.upvote-button');
    
    if (upvoteButton && !upvoteButton.disabled) {
        upvoteButton.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const countElement = this.querySelector('.upvote-count');
            const isUpvoted = this.classList.contains('upvoted');
            
            // Desabilitar temporariamente e mostrar loading
            this.disabled = true;
            this.style.opacity = '0.7';
            
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
                    countElement.textContent = data.upvotes_count;
                    this.classList.toggle('upvoted');
                    this.setAttribute('aria-label', this.classList.contains('upvoted') ? 'Remover upvote' : 'Dar upvote');
                    
                    // Animação de feedback
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                } else {
                    alert(data.message || 'Erro ao processar upvote');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar upvote. Tente novamente.');
            })
            .finally(() => {
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    }
    
    // Contador de caracteres no comentário
    const commentTextarea = document.getElementById('comment_content');
    const charCounter = document.getElementById('char-count');
    
    if (commentTextarea && charCounter) {
        commentTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCounter.textContent = count;
            
            if (count > 900) {
                charCounter.style.color = '#dc3545';
            } else if (count > 750) {
                charCounter.style.color = '#ffc107';
            } else {
                charCounter.style.color = '#28a745';
            }
        });
    }
    
    // Scroll suave para comentários quando clicado no contador
    const commentsCountDisplay = document.querySelector('.comments-count-display');
    if (commentsCountDisplay) {
        commentsCountDisplay.style.cursor = 'pointer';
        commentsCountDisplay.addEventListener('click', function() {
            document.getElementById('comments-section').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    }
    
    // Auto-resize do textarea
    if (commentTextarea) {
        commentTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(this.scrollHeight, 120) + 'px';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<style>
/* Estilos do post - ajustados para mobile apenas */
.post-view-container {
    margin-left: auto;
    margin-right: auto;
    padding-bottom: 2rem;
    width: 100%;
}
.full-post-content {
    width: 100%;
    margin-top: 0;
    margin-bottom: 1.5rem;
    box-sizing: border-box;
}
header.header-spacer {
    display: none !important;
}

/* Remover espaço extra acima do header do post */
.post-header {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

/* Remover espaço acima do header principal */
body {
    padding-top: 0 !important;
}

@media (max-width: 900px) {
    .post-view-container {
        max-width: 98vw;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .full-post-content {
        margin-top: 0.3rem;
    }
}
@media (max-width: 600px) {
    .post-view-container {
        max-width: 100vw;
        padding-left: 0.1rem;
        padding-right: 0.1rem;
    }
    .full-post-content {
        margin-top: 0.1rem;
    }
}

/* Estilos específicos apenas para elementos únicos desta página */
.character-counter {
    text-align: right;
    font-size: 0.8rem;
    color: #888;
    margin-top: 5px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.comments-counter {
    color: #888;
    font-weight: normal;
    font-size: 1.2rem;
}

.login-prompt-content {
    display: flex;
    align-items: center;
    gap: 15px;
    justify-content: center;
}

.login-prompt-content i {
    font-size: 1.5rem;
}

.no-comments-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.no-comments-content i {
    font-size: 2rem;
    color: #555;
}

.no-comments-cta {
    font-size: 0.9rem;
    color: #666;
}

.comment-author-details {
    display: flex;
    align-items: center;
    gap: 10px;
}

.comment-item:target {
    border-color: #0e86ca;
    box-shadow: 0 0 0 3px rgba(14, 134, 202, 0.2);
}

/* Garantir que o container do post tenha o mesmo fundo da página inicial */
.post-view-container {
    background: #2B2B2B; /* Mesmo fundo da página inicial */
}

/* CORREÇÃO ESPECÍFICA PARA O PROBLEMA DO Z-INDEX */
/* Forçar que o header do post não cubra o header principal */
.post-header {
    position: relative !important;
    z-index: 1 !important;
    /* Nunca usar position: fixed ou sticky aqui */
}

.post-header h1 {
    position: relative !important;
    z-index: 1 !important;
    /* Garantir que o título não flutue */
}

.post-meta-info {
    position: relative !important;
    z-index: 1 !important;
    /* Garantir que as informações do autor não flutuem */
}

.post-management-actions {
    position: relative !important;
    z-index: 1 !important;
}

/* Garantir que o header principal tenha prioridade máxima */
header {
    z-index: 9999 !important;
    position: sticky !important;
    top: 0 !important;
}

/* Garantir que o nav também tenha z-index alto */
header nav {
    z-index: 10000 !important;
}

/* Remover qualquer transform que possa estar causando novo contexto de empilhamento */
.full-post-content {
    transform: none !important;
}

.post-header {
    transform: none !important;
}

@media (max-width: 900px) {
    .post-view-container {
        padding: 0 0.5rem;
        margin: 0;
        min-width: 0;
    }
    .full-post-content {
        padding: 1rem 0.5rem;
        border-radius: 0;
        box-shadow: none;
    }
    .post-header h1 {
        font-size: 1.3rem;
        word-break: break-word;
        line-height: 1.2;
    }
    .post-meta-info {
        flex-direction: column;
        gap: 0.5rem;
        font-size: 0.95rem;
    }
    .post-body.markdown-content {
        font-size: 1rem;
        padding: 0.5rem 0;
        word-break: break-word;
    }
    .post-footer-feedback {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    .upvote-button {
        width: 100%;
        font-size: 1rem;
        padding: 0.7em 0;
    }
    .comments-area {
        padding: 0.5rem 0;
    }
    .comments-list-wrapper {
        padding: 0;
    }
    .comment-item {
        padding: 0.7rem 0.5rem;
        font-size: 0.98rem;
    }
    .comment-author-details {
        flex-direction: row;
        gap: 0.5rem;
    }
    .comment-text {
        font-size: 1rem;
        word-break: break-word;
    }
    .comment-form-wrapper, .new-comment-form {
        padding: 0.5rem 0;
    }
    .character-counter {
        font-size: 0.85rem;
    }
    .login-prompt-content {
        flex-direction: column;
        gap: 0.5rem;
        font-size: 1rem;
    }
    .no-comments-content {
        font-size: 1rem;
    }
    .page-navigation-links {
        margin: 1.5rem 0 0 0;
        text-align: center;
    }
    .back-to-home-link {
        font-size: 1.1rem;
        padding: 0.7em 1.2em;
    }
}
@media (max-width: 600px) {
    .post-view-container {
        padding: 0 0.2rem;
    }
    .full-post-content {
        padding: 0.7rem 0.2rem;
    }
    .post-header h1 {
        font-size: 1.05rem;
    }
    .post-body.markdown-content {
        font-size: 0.97rem;
    }
    .comment-item {
        font-size: 0.95rem;
    }
    .upvote-button {
        font-size: 0.97rem;
    }
}
/* Garante que o menu hambúrguer do header sempre fique visível e acima do conteúdo */
header {
    z-index: 9999 !important;
    position: sticky !important;
    top: 0 !important;
}
.mobile-menu-toggle {
    display: block !important;
}
</style>
