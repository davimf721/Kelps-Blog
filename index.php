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
        
        <section id="posts-container">
            <h2>Blog Posts</h2>
            <?php
            // Buscar posts do banco de dados - Consulta atualizada para incluir contagem de comentários
            $query = "SELECT p.id, p.title, p.content, p.created_at, u.username AS author, p.upvotes_count,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
                      FROM posts p
                      JOIN users u ON p.user_id = u.id
                      ORDER BY p.created_at DESC";
            
            $result = pg_query($dbconn, $query);

            if ($result && pg_num_rows($result) > 0) {
                while ($post = pg_fetch_assoc($result)) {
                    // Converte markdown para HTML e depois remove as tags para o resumo
                    $html_content = $parsedown->text($post['content']);
                    $plain_content = strip_tags($html_content);
                    $summary = mb_substr($plain_content, 0, 150);
                    if (mb_strlen($plain_content) > 150) {
                        $summary .= "...";
                    }
                    
                    // Verifica se o usuário atual já deu upvote neste post
                    $has_upvoted = false;
                    if (isset($_SESSION['user_id'])) {
                        $upvote_check = "SELECT id FROM post_upvotes WHERE post_id = {$post['id']} AND user_id = {$_SESSION['user_id']}";
                        $upvote_result = pg_query($dbconn, $upvote_check);
                        $has_upvoted = $upvote_result && pg_num_rows($upvote_result) > 0;
                    }
            ?>
                <article class="post-summary">
                    <h3><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                    <p class="post-meta">Por: <?php echo htmlspecialchars($post['author']); ?> em <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></p>
                    <p><?php echo htmlspecialchars($summary); ?></p>
                    
                    <div class="post-metrics">
                        <button class="upvote-button <?php echo $has_upvoted ? 'upvoted' : ''; ?>" 
                                data-post-id="<?php echo $post['id']; ?>"
                                <?php echo !isset($_SESSION['user_id']) ? 'disabled title="Faça login para dar upvote"' : ''; ?>>
                            <i class="fas fa-arrow-up upvote-icon"></i>
                            <span class="upvote-count"><?php echo $post['upvotes_count']; ?></span>
                        </button>
                        
                        <a href="post.php?id=<?php echo $post['id']; ?>#comments" class="metric-button">
                            <i class="far fa-comment"></i>
                            <span><?php echo $post['comments_count']; ?></span>
                            <?php echo $post['comments_count'] == 1 ? 'comentário' : 'comentários'; ?>
                        </a>
                    </div>
                    
                    <div class="post-actions">
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="action-button">Leia mais</a>
                    </div>
                </article>
            <?php
                }
            } else {
                echo "<p>Nenhum post encontrado.</p>";
            }
            ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar listeners para os botões de upvote
        const upvoteButtons = document.querySelectorAll('.upvote-button');
        
        upvoteButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.hasAttribute('disabled')) {
                    alert('Você precisa estar logado para dar upvote');
                    return;
                }
                
                const postId = this.getAttribute('data-post-id');
                const upvoteCount = this.querySelector('.upvote-count');
                
                // Enviar solicitação AJAX para processar o upvote
                const formData = new FormData();
                formData.append('post_id', postId);
                
                fetch('upvote.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar o contador de upvotes
                        upvoteCount.textContent = data.count;
                        
                        // Alternar a classe 'upvoted' para feedback visual
                        if (data.action === 'added') {
                            this.classList.add('upvoted');
                        } else {
                            this.classList.remove('upvoted');
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro ao processar upvote:', error);
                    alert('Ocorreu um erro ao processar seu upvote');
                });
            });
        });
    });
    </script>
</body>
</html>