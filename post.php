<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'libs/Parsedown.php'; // Incluir o Parsedown
require_once 'includes/auth.php';

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
            <p class="post-meta-info">
                Publicado por 
                <a href="profile.php?user_id=<?php echo $post['user_id']; ?>" class="author-name-link">
                    <?php echo htmlspecialchars($post['author']); ?>
                    <?php if ($post['author_is_admin'] === true || $post['author_is_admin'] === 't'): ?>
                        <span class="admin-badge-inline"><i class="fas fa-shield-alt"></i> Admin</span>
                    <?php endif; ?>
                </a> 
                em <?php echo date('d/m/Y \à\s H:i', strtotime($post['created_at'])); ?>
            </p>
            <?php if ($is_author || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])): ?>
                <div class="post-management-actions">
                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="action-button edit-button">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                         <a href="admin/manage_posts.php?delete_post=<?php echo $post['id']; ?>" 
                           class="action-button delete-button" 
                           onclick="return confirm('Tem certeza que deseja excluir este post? Esta ação é irreversível.');">
                            <i class="fas fa-trash-alt"></i> Excluir (Admin)
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="post-body markdown-content">
            <?php echo $parsedown->text($post['content']); ?>
        </div>

        <footer class="post-footer-feedback">
            <button class="upvote-button <?php echo $has_upvoted ? 'upvoted' : ''; ?>" 
                    data-post-id="<?php echo $post['id']; ?>"
                    <?php echo !is_logged_in() ? 'disabled title="Faça login para dar upvote"' : ''; ?>>
                <i class="fas fa-arrow-up upvote-icon"></i>
                <span class="upvote-count"><?php echo (int)$post['upvotes_count']; ?></span>
            </button>
            <span class="comments-count-display">
                <i class="far fa-comment"></i> <?php echo $comments_result ? pg_num_rows($comments_result) : 0; ?> Comentários
            </span>
        </footer>
    </article>

    <section id="comments-section" class="comments-area">
        <h2>Comentários</h2>
        
        <?php if (is_logged_in()): ?>
            <div id="comment-form-area" class="comment-form-wrapper">
                <h3>Deixe seu comentário</h3>
                <?php if (!empty($comment_error)): ?>
                    <div class="error-message alert alert-danger"><?php echo htmlspecialchars($comment_error); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['comment_added']) && $_GET['comment_added'] == 'true'): ?>
                    <div class="success-message alert alert-success">Comentário adicionado com sucesso!</div>
                <?php endif; ?>
                <form action="post.php?id=<?php echo $post_id; ?>" method="post" class="new-comment-form">
                    <div class="form-group">
                        <textarea name="comment_content" rows="4" required placeholder="Escreva seu comentário aqui..." aria-label="Seu comentário"></textarea>
                    </div>
                    <button type="submit" class="submit-button primary-button"><i class="fas fa-paper-plane"></i> Enviar Comentário</button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt-comment">
                <p>Para comentar, por favor <a href="login.php?redirect=post.php?id=<?php echo $post_id; ?>">faça login</a> ou <a href="register.php">crie uma conta</a>.</p>
            </div>
        <?php endif; ?>

        <div class="comments-list-wrapper">
            <?php if ($comments_result && pg_num_rows($comments_result) > 0): ?>
                <?php while ($comment = pg_fetch_assoc($comments_result)): ?>
                    <div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                        <div class="comment-author-info">
                            <a href="profile.php?user_id=<?php echo $comment['user_id']; ?>" class="author-name-link">
                                <?php echo htmlspecialchars($comment['username']); ?>
                            </a>
                            <?php if ($comment['is_admin'] === true || $comment['is_admin'] === 't'): ?>
                                <span class="admin-badge-inline"><i class="fas fa-shield-alt"></i> Admin</span>
                            <?php endif; ?>
                            <span class="comment-timestamp"> - <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="comment-text markdown-content">
                            <?php echo $parsedown->text($comment['content']); ?>
                        </div>
                         <?php if (is_logged_in() && ($_SESSION['user_id'] == $comment['user_id'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']))): ?>
                            <div class="comment-actions">
                                <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
                                    <!-- <a href="edit_comment.php?id=<?php echo $comment['id']; ?>" class="action-link edit-comment-link">Editar</a> -->
                                <?php endif; ?>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <a href="admin/manage_comments.php?delete_comment=<?php echo $comment['id']; ?>&post_id=<?php echo $post_id; ?>" 
                                       class="action-link delete-comment-link"
                                       onclick="return confirm('Tem certeza que deseja excluir este comentário?');">
                                       Excluir (Admin)
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-comments-message">
                    <p>Ainda não há comentários. Seja o primeiro a comentar!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="page-navigation-links">
        <a href="index.php" class="read-more-link back-to-home-link"><i class="fas fa-chevron-left"></i> Voltar para a lista de posts</a>
    </div>

</div>

<?php include 'includes/footer.php'; ?>