<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'libs/Parsedown.php'; // Incluir o Parsedown
require_once 'includes/auth.php';

// Inicializar o Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true); // Ativar modo seguro para prevenir XSS
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="images/file.jpg" type="image/jpg">
</head>
<body>
    <header>
        <div class="site-logo">
            <!-- Se você tiver uma imagem de logo, ela iria aqui. Ex: <img src="images/logo.png" alt="Kelps Blog Logo"> -->
        </div>
        <h1 class="site-title">Bem-vindo ao Kelps Blog!</h1>
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <section id="posts-container"></section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. All rights reserved.</p>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    async function fetchAndDisplayPosts() {
        const container = document.getElementById('posts-container');
        const response = await fetch('fetch_posts.php');
        const posts = await response.json();
        container.innerHTML = '';
        
        posts.forEach(post => {
            container.innerHTML += `
                <article class="post-summary">
                    <h3><a href="post.php?id=${post.id}">${post.title}</a></h3>
                    <p class="post-meta">Por: ${post.author} em ${new Date(post.created_at).toLocaleString('pt-BR')}</p>
                    <p>${post.content.substring(0, 150)}...</p>
                    <div class="post-metrics">
                        <button class="upvote-btn" data-post-id="${post.id}" 
                                ${!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?> ? 'disabled title="Faça login para dar upvote"' : ''}>
                            <i class="fas fa-arrow-up"></i>
                            <span class="upvote-count">${post.upvotes_count}</span>
                        </button>
                        <span class="comment-count"><i class="far fa-comment"></i> ${post.comments_count} comentários</span>
                    </div>
                    <a href="post.php?id=${post.id}" class="action-button">Leia mais</a>
                </article>
            `;
        });
    }
    
    // Delegação de eventos para botões de upvote
    document.addEventListener('click', function(event) {
        if (event.target.closest('.upvote-btn')) {
            const button = event.target.closest('.upvote-btn');
            
            if (button.hasAttribute('disabled')) {
                alert('Você precisa estar logado para dar upvote');
                return;
            }
            
            const postId = button.getAttribute('data-post-id');
            
            fetch('upvote.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualiza o contador de upvotes diretamente
                    const upvoteCount = button.querySelector('.upvote-count');
                    if (upvoteCount) upvoteCount.textContent = data.count;
                    
                    // Feedback visual
                    if (data.action === 'added') {
                        button.classList.add('upvote-active');
                    } else {
                        button.classList.remove('upvote-active');
                    }
                } else {
                    alert(data.message || 'Você precisa estar logado para votar.');
                }
            });
        }
    });
    
    fetchAndDisplayPosts();
    setInterval(fetchAndDisplayPosts, 10000); // Atualiza a cada 10s
    
    function updateAllUpvotes() {
        document.querySelectorAll('.upvote-btn').forEach(button => {
            const postId = button.getAttribute('data-post-id');
            fetch('fetch_upvotes.php?post_id=' + postId)
                .then(response => response.json())
                .then(data => {
                    const upvoteCount = button.querySelector('.upvote-count');
                    if (upvoteCount) upvoteCount.textContent = data.upvotes;
                });
        });
    }
    setInterval(updateAllUpvotes, 5000);
});
</script>
</body>
</html>