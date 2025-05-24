<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'libs/Parsedown.php'; // Incluir o Parsedown

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
    <!-- Adicionando favicon para a URL do site -->
    <link rel="icon" href="images/file.jpg" type="image/jpg">
    <style>
        /* Estilo para o logo no cabeçalho */
        .site-logo {
            display: flex;
            align-items: center;
        }
        
        .site-logo img {
            height: 60px; /* Aumentado de 40px para 60px */
            width: auto;
            margin-right: 15px;
        }
        
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            position: relative;
        }
        
        header h1 {
            margin: 0;
            text-align: center;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }
        
        /* Ajuste para navegação */
        nav {
            margin-left: auto;
        }

        /* Para garantir que o logo não sobreponha o título em telas menores */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 10px;
            }
            
            header h1 {
                position: static;
                transform: none;
                margin-top: 10px;
            }
            
            nav {
                margin-left: 0;
                margin-top: 10px;
            }
        }
        
        /* Estilo para o contador de comentários */
        .post-metrics {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .metric-button {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #aaa;
            font-size: 0.9em;
            text-decoration: none;
            transition: color 0.2s;
            padding: 5px 8px;
            border-radius: 4px;
        }
        
        .metric-button:hover {
            background-color: rgba(14, 134, 202, 0.1);
        }
        
        .metric-button i {
            color: #0e86ca;
        }
        
        .upvote-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 8px;
            font-size: 0.9em;
            color: #aaa;
            display: flex;
            align-items: center;
            gap: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .upvote-button:hover {
            background-color: rgba(14, 134, 202, 0.1);
        }
        
        .upvote-button.upvoted .upvote-icon {
            color: #0e86ca;
        }
        
        .upvote-button .upvote-icon {
            color: #0e86ca;
        }
        
        .upvote-button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Substitua o CSS atual dos upvotes por este */
        .upvote-btn {
            background-color: #333;
            border: 1px solid #444;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            padding: 8px 15px;
            transition: all 0.2s ease;
            color: #ccc;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .upvote-btn:hover {
            background-color: #444;
            color: #fff;
        }

        .upvote-btn i {
            color: #0e86ca;
        }

        .upvote-active {
            background-color: #0056b3;
            color: white;
            animation: pulse 0.5s;
        }

        .upvote-active i {
            color: white;
        }

        .upvote-count {
            display: inline-block;
            min-width: 20px;
            color: inherit;
            font-size: inherit;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); box-shadow: 0 0 10px rgba(0, 123, 255, 0.5); }
            100% { transform: scale(1); }
        }

        .upvote-btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <header>
        <div class="site-logo">
            <!-- Logo no canto superior esquerdo -->
            <img src="images/file.jpg" alt="Kelps Blog Logo">
        </div>
        <h1>Bem-vindo ao Kelps Blog!</h1>
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