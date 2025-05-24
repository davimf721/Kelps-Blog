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
$query = "SELECT p.id, p.title, p.content, p.created_at, u.username AS author, p.user_id, p.upvotes_count
          FROM posts p
          JOIN users u ON p.user_id = u.id
          WHERE p.id = $post_id";

$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}

$post = pg_fetch_assoc($result);

// Verificar se o usuário atual é o autor do post
$is_author = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id'];

// Inicializar o Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true); // Ativar modo seguro para prevenir XSS

// Verifica se o usuário atual já deu upvote neste post
$has_upvoted = false;
if (isset($_SESSION['user_id'])) {
    $upvote_check = "SELECT id FROM post_upvotes WHERE post_id = {$post['id']} AND user_id = {$_SESSION['user_id']}";
    $upvote_result = pg_query($dbconn, $upvote_check);
    $has_upvoted = $upvote_result && pg_num_rows($upvote_result) > 0;
}

// Buscar comentários do post
$comments_query = "SELECT c.id, c.content, c.created_at, u.username 
                  FROM comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.post_id = $post_id 
                  ORDER BY c.created_at DESC";
$comments_result = pg_query($dbconn, $comments_query);

// Processar o envio de um novo comentário
$comment_error = '';
$comment_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content']) && isset($_SESSION['user_id'])) {
    $comment_content = trim($_POST['comment_content']);
    
    if (empty($comment_content)) {
        $comment_error = "O comentário não pode estar vazio";
    } else {
        // Escapar conteúdo para prevenir SQL injection
        $comment_content = pg_escape_string($dbconn, $comment_content);
        $user_id = $_SESSION['user_id'];
        $parent_id = isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';

        $insert_query = "INSERT INTO comments (post_id, user_id, content, parent_id) VALUES ($post_id, $user_id, '$comment_content', $parent_id)";
        $insert_result = pg_query($dbconn, $insert_query);
        
        if ($insert_result) {
            $comment_success = true;
            // Recarregar a página para exibir o novo comentário
            header("Location: post.php?id=$post_id&comment_added=true");
            exit;
        } else {
            $comment_error = "Erro ao adicionar comentário: " . pg_last_error($dbconn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos para melhorar a aparência do conteúdo Markdown renderizado */
        .markdown-content {
            line-height: 1.6;
            overflow-wrap: break-word;
            color: #e6e6e6;
        }
        .markdown-content h1,
        .markdown-content h2,
        .markdown-content h3,
        .markdown-content h4,
        .markdown-content h5,
        .markdown-content h6 {
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            color: #fff;
        }
        .markdown-content p {
            margin-bottom: 1em;
        }
        .markdown-content ul,
        .markdown-content ol {
            margin-left: 2em;
            margin-bottom: 1em;
        }
        .markdown-content blockquote {
            border-left: 4px solid #444;
            padding-left: 1em;
            margin-left: 0;
            color: #aaa;
        }
        .markdown-content code {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-family: monospace;
            color: #e0e0e0;
        }
        .markdown-content pre {
            background-color: #1a1a1a;
            padding: 1em;
            border-radius: 5px;
            overflow-x: auto;
            margin-bottom: 1em;
        }
        .markdown-content pre code {
            background-color: transparent;
            padding: 0;
        }
        .markdown-content img {
            max-width: 100%;
            height: auto;
        }
        .markdown-content table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1em;
        }
        .markdown-content table th,
        .markdown-content table td {
            border: 1px solid #444;
            padding: 0.5em;
        }
        
        .post-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .edit-button {
            display: flex;
            align-items: center;
            gap: 5px;
            background-color: #0e86ca;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        
        .edit-button:hover {
            background-color: #0a6aa8;
        }
        
        .action-center {
            margin-top: 30px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .action-button {
            background-color: #0e86ca;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-button:hover {
            background-color: #0a6aa8;
        }
        
        /* Estilos para a seção de comentários - Tema escuro similar ao site */
        .comments-section {
            margin-top: 40px;
            border-top: 1px solid #333;
            padding-top: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .comments-section h2 {
            font-size: 1.5em;
            margin-bottom: 25px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .comments-section h2 i {
            color: #0e86ca;
        }
        
        .comments-list {
            margin-bottom: 30px;
        }
        
        .comment {
            padding: 15px 20px;
            margin-bottom: 20px;
            background-color: #2a2a2a;
            border-radius: 4px;
            border-left: 3px solid #0e86ca;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #aaa;
        }
        
        .comment-author {
            font-weight: bold;
            color: #0e86ca;
        }
        
        .comment-content {
            line-height: 1.5;
            color: #e0e0e0;
        }
        
        .comment-form {
            background-color: #2a2a2a;
            padding: 22px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .comment-form h3 {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .comment-form h3 i {
            color: #0e86ca;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #444;
            border-radius: 4px;
            min-height: 120px;
            margin-bottom: 15px;
            font-family: inherit;
            font-size: 1em;
            resize: vertical;
            background-color: #1a1a1a;
            color: #e0e0e0;
            transition: border-color 0.3s;
        }
        
        .comment-form textarea:focus {
            border-color: #0e86ca;
            outline: none;
        }
        
        .comment-form button {
            background-color: #0e86ca;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .comment-form button:hover {
            background-color: #0a6aa8;
        }
        
        .comment-error {
            color: #ff6b6b;
            margin-bottom: 15px;
            font-size: 0.9em;
            padding: 10px;
            background-color: rgba(255, 107, 107, 0.1);
            border-radius: 4px;
        }
        
        .comment-success {
            color: #69db7c;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(105, 219, 124, 0.1);
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .no-comments {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 25px 20px;
            background-color: #222;
            border-radius: 4px;
        }
        
        .login-to-comment {
            text-align: center;
            padding: 22px;
            background-color: #222;
            border-radius: 4px;
            margin-top: 20px;
            border: 1px dashed #444;
        }
        
        .login-to-comment a {
            color: #0e86ca;
            font-weight: bold;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .login-to-comment a:hover {
            color: #0a6aa8;
            text-decoration: underline;
        }
        
        .replies {
            margin-left: 20px;
            border-left: 2px solid #333;
            padding-left: 15px;
            margin-top: 10px;
        }

        .comment-reply {
            background-color: #222;
        }

        .comment-actions {
            display: flex;
            gap: 10px;
        }

        .delete-comment-btn,
        .reply-comment-btn {
            background: none;
            border: none;
            color: #0e86ca;
            cursor: pointer;
            font-size: 0.85em;
            padding: 0;
        }

        .delete-comment-btn:hover {
            color: #ff6b6b;
            text-decoration: underline;
        }

        .reply-comment-btn:hover {
            color: #69db7c;
            text-decoration: underline;
        }

        #parent_id {
            display: none;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="create_post.php">Criar Post</a></li>
                    <li><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <article class="full-post">
            <p class="post-meta">Por: <?php echo htmlspecialchars($post['author']); ?> em <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
            
            <?php if ($is_author): ?>
            <div class="post-actions">
                <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> Editar Post
                </a>
            </div>
            <?php endif; ?>
            
            <div class="post-content markdown-content">
                <?php echo $parsedown->text($post['content']); ?>
            </div>
            <div class="post-feedback">
                <button class="upvote-button <?php echo $has_upvoted ? 'upvoted' : ''; ?>" 
                        data-post-id="<?php echo $post['id']; ?>"
                        <?php echo !isset($_SESSION['user_id']) ? 'disabled title="Faça login para dar upvote"' : ''; ?>>
                    <i class="fas fa-arrow-up upvote-icon"></i>
                    <span class="upvote-count"><?php echo $post['upvotes_count']; ?></span>
                </button>
            </div>
        </article>
        
        <!-- Seção de comentários -->
        <section class="comments-section">
            <h2><i class="far fa-comments"></i> Comentários (<?php echo $comments_result ? pg_num_rows($comments_result) : 0; ?>)</h2>
            
            <!-- Lista de comentários -->
            <div class="comments-list">
                <?php if ($comments_result && pg_num_rows($comments_result) > 0): ?>
                    <?php while ($comment = pg_fetch_assoc($comments_result)): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-comments">
                        <p><i class="far fa-comment-dots"></i> Nenhum comentário ainda. Seja o primeiro a comentar!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulário para adicionar comentário -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="comment-form">
                    <h3><i class="far fa-edit"></i> Deixe seu comentário</h3>
                    
                    <?php if (isset($_GET['comment_added']) && $_GET['comment_added'] == 'true'): ?>
                        <div class="comment-success">
                            <i class="fas fa-check-circle"></i> Comentário adicionado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($comment_error): ?>
                        <div class="comment-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $comment_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="comment-form">
                        <textarea name="content" required></textarea>
                        <input type="hidden" name="parent_id" id="parent_id" value="">
                        <button type="submit">Comentar</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="login-to-comment">
                    <p>Para deixar um comentário, <a href="login.php">faça login</a> ou <a href="register.php">registre-se</a>.</p>
                </div>
            <?php endif; ?>
        </section>

        <div class="action-center">
            <a href="index.php" class="action-button"><i class="fas fa-arrow-left"></i> Voltar para a lista de posts</a>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para construir a árvore de comentários
        function buildCommentTree(comments) {
            const map = {};
            const roots = [];
            
            comments.forEach(c => {
                c.replies = [];
                map[c.id] = c;
            });
            
            comments.forEach(c => {
                if (c.parent_id && map[c.parent_id]) {
                    map[c.parent_id].replies.push(c);
                } else {
                    roots.push(c);
                }
            });
            
            return roots;
        }
        
        // Função para renderizar comentários
        function renderComment(comment, container, isReply = false) {
            const div = document.createElement('div');
            div.className = 'comment' + (isReply ? ' comment-reply' : '');
            div.setAttribute('data-comment-id', comment.id);
            
            div.innerHTML = `
                <div class="comment-header">
                    <span class="comment-author">${comment.username}</span>
                    <span class="comment-date">${new Date(comment.created_at).toLocaleString('pt-BR')}</span>
                    <div class="comment-actions">
                        ${comment.can_delete ? '<button class="delete-comment-btn">Excluir</button>' : ''}
                        <button class="reply-comment-btn">Responder</button>
                    </div>
                </div>
                <div class="comment-content">${comment.content.replace(/\n/g, '<br>')}</div>
                <div class="replies"></div>
            `;
            
            container.appendChild(div);
            
            // Renderizar respostas recursivamente
            if (comment.replies && comment.replies.length > 0) {
                const repliesContainer = div.querySelector('.replies');
                comment.replies.forEach(reply => {
                    renderComment(reply, repliesContainer, true);
                });
            }
        }
        
        // Função para buscar e exibir comentários
        function fetchComments() {
            fetch('fetch_comments.php?post_id=<?php echo $post_id; ?>')
                .then(response => response.json())
                .then(comments => {
                    const commentsList = document.querySelector('.comments-list');
                    commentsList.innerHTML = '';
                    
                    if (comments.length === 0) {
                        commentsList.innerHTML = '<div class="no-comments"><p><i class="far fa-comment-dots"></i> Nenhum comentário ainda. Seja o primeiro a comentar!</p></div>';
                    } else {
                        const tree = buildCommentTree(comments);
                        tree.forEach(comment => {
                            renderComment(comment, commentsList);
                        });
                    }
                });
        }
        
        // Inicializar comentários e atualizar periodicamente
        fetchComments();
        const commentInterval = setInterval(fetchComments, 10000); // Atualiza a cada 10s
        
        // Handler para formulário de comentários
        document.getElementById('comment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const content = this.querySelector('textarea[name="content"]').value.trim();
            const parentId = document.getElementById('parent_id').value;
            
            if (!content) return;
            
            fetch('add_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=<?php echo $post_id; ?>&content=${encodeURIComponent(content)}&parent_id=${parentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpar o formulário
                    this.querySelector('textarea[name="content"]').value = '';
                    document.getElementById('parent_id').value = '';
                    
                    // Atualizar comentários imediatamente
                    fetchComments();
                } else {
                    alert(data.message || 'Erro ao adicionar comentário.');
                }
            });
        });
        
        // Adicionar listener para o botão de upvote
        const upvoteButton = document.querySelector('.upvote-button');
        
        if (upvoteButton) {
            upvoteButton.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) {
                    alert('Você precisa estar logado para dar upvote');
                    return;
                }
                
                const postId = this.getAttribute('data-post-id');
                const upvoteCount = this.querySelector('.upvote-count');
                
                fetch('upvote.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'post_id=' + postId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        upvoteCount.textContent = data.count;
                        
                        if (data.action === 'added') {
                            this.classList.add('upvoted');
                        } else {
                            this.classList.remove('upvoted');
                        }
                    } else {
                        alert(data.message);
                    }
                });
            });
        }
        
        // Event delegation para botões de excluir e responder comentários
        document.addEventListener('click', function(e) {
            // Excluir comentário
            if (e.target.classList.contains('delete-comment-btn')) {
                const commentDiv = e.target.closest('.comment');
                const commentId = commentDiv.getAttribute('data-comment-id');
                
                if (confirm('Deseja excluir este comentário?')) {
                    fetch('delete_comment.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'comment_id=' + commentId
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            fetchComments(); // Atualizar toda a árvore de comentários
                        } else {
                            alert(data.message);
                        }
                    });
                }
            }
            
            // Responder comentário
            if (e.target.classList.contains('reply-comment-btn')) {
                const commentDiv = e.target.closest('.comment');
                const commentId = commentDiv.getAttribute('data-comment-id');
                const authorName = commentDiv.querySelector('.comment-author').textContent;
                
                document.getElementById('parent_id').value = commentId;
                const textarea = document.querySelector('#comment-form textarea');
                textarea.value = `@${authorName} `;
                textarea.focus();
                
                // Scroll suave até o formulário
                document.querySelector('.comment-form').scrollIntoView({ behavior: 'smooth' });
            }
        });
        
        // Função para buscar upvotes
        function fetchUpvotes() {
            fetch('fetch_upvotes.php?post_id=<?php echo $post_id; ?>')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.upvote-count').textContent = data.upvotes;
                });
        }
        setInterval(fetchUpvotes, 5000); // Atualiza a cada 5s
    });
    </script>
</body>
</html>