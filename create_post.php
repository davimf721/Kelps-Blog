<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/notification_helper.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;
$post_id = null;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($title)) {
        $errors[] = 'O título é obrigatório.';
    }
    
    if (empty($content)) {
        $errors[] = 'O conteúdo é obrigatório.';
    }
    
    if (empty($errors)) {
        $query = "INSERT INTO posts (title, content, user_id) VALUES ($1, $2, $3) RETURNING id";
        $result = pg_query_params($dbconn, $query, [$title, $content, $user_id]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            $post_id = $row['id'];
            
            // Notificar seguidores sobre o novo post
            $notifications_sent = notifyFollowersAboutNewPost($dbconn, $user_id, $post_id, $title);
            
            $success = true;
            
            if ($notifications_sent > 0) {
                $_SESSION['success'] = "Post criado com sucesso! {$notifications_sent} seguidores foram notificados.";
            } else {
                $_SESSION['success'] = "Post criado com sucesso!";
            }
        } else {
            $errors[] = 'Erro ao criar o post: ' . pg_last_error($dbconn);
        }
    }
}

// Definir variáveis para o header
$page_title = "Criar Post - Kelps Blog";
$current_page = 'create';

// Incluir o header
include 'includes/header.php';
?>

<div class="container">
    <?php if ($success): ?>
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="success-title">Post publicado com sucesso!</h2>
            <p class="success-message">
                Seu post foi publicado e já está disponível no blog.
                Você pode visualizá-lo ou voltar para a página inicial para ver todos os posts.
            </p>
            <div class="success-actions">
                <a href="post.php?id=<?php echo $post_id; ?>" class="success-button primary">
                    <i class="fas fa-eye"></i> Ver meu post
                </a>
                <a href="index.php" class="success-button">
                    <i class="fas fa-home"></i> Página inicial
                </a>
                <a href="create_post.php" class="success-button">
                    <i class="fas fa-plus"></i> Criar outro post
                </a>
            </div>
        </div>
    <?php else: ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="markdown-tips">
            <h3><i class="fas fa-info-circle"></i> Este blog suporta Markdown!</h3>
            <p>Use a barra de ferramentas abaixo ou insira manualmente a sintaxe Markdown. Você verá uma prévia em tempo real do seu conteúdo.</p>
        </div>

        <form method="POST" action="">
            <div class="form-row">
                <label for="title">Título:</label>
                <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>
            
            <div class="markdown-toolbar">
                <button type="button" data-action="heading1"><i class="fas fa-heading"></i> H1</button>
                <button type="button" data-action="heading2"><i class="fas fa-heading"></i> H2</button>
                <button type="button" data-action="bold"><i class="fas fa-bold"></i> Negrito</button>
                <button type="button" data-action="italic"><i class="fas fa-italic"></i> Itálico</button>
                <button type="button" data-action="link"><i class="fas fa-link"></i> Link</button>
                <button type="button" data-action="image"><i class="fas fa-image"></i> Imagem</button>
                <button type="button" data-action="list"><i class="fas fa-list-ul"></i> Lista</button>
                <button type="button" data-action="orderedList"><i class="fas fa-list-ol"></i> Lista Numerada</button>
                <button type="button" data-action="code"><i class="fas fa-code"></i> Bloco de Código</button>
                <button type="button" data-action="quote"><i class="fas fa-quote-right"></i> Citação</button>
            </div>
            
            <div class="editor-container">
                <div class="editor">
                    <div class="editor-header">
                        <label for="content">Conteúdo:</label>
                        <span>Escrevendo Markdown</span>
                    </div>
                    <textarea id="content" name="content" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                </div>
                
                <div class="preview">
                    <div class="editor-header">
                        <label>Prévia:</label>
                        <span>Como ficará seu post</span>
                    </div>
                    <div class="preview-content" id="preview-content">
                        <!-- O conteúdo renderizado será exibido aqui -->
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <button type="submit" class="action-button">Publicar Post</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
/* Estilos específicos para criar post */
.form-row {
    margin-bottom: 20px;
}

.success-container {
    background-color: rgba(40, 40, 40, 0.9);
    border-radius: 8px;
    padding: 25px;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    border: 1px solid #444;
}

.success-icon {
    font-size: 50px;
    color: #51cf66;
    margin-bottom: 20px;
}

.success-title {
    color: #51cf66;
    font-size: 24px;
    margin-bottom: 15px;
    font-weight: bold;
}

.success-message {
    color: #f1f1f1;
    margin-bottom: 25px;
    font-size: 16px;
    line-height: 1.5;
}

.success-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.success-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #444;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.2s, transform 0.2s;
    border: none;
    cursor: pointer;
}

.success-button:hover {
    background-color: #555;
    transform: translateY(-2px);
}

.success-button.primary {
    background-color: #228be6;
}

.success-button.primary:hover {
    background-color: #1c7ed6;
}

.markdown-tips {
    background-color: #333;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.markdown-tips h3 {
    margin-top: 0;
    color: #fff;
}

.editor-container {
    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: auto 1fr;
    gap: 20px;
    height: calc(100vh - 300px);
    min-height: 500px;
    margin-bottom: 30px;
}

@media (min-width: 992px) {
    .editor-container {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr;
    }
}

.editor, .preview {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.editor-header {
    margin-bottom: 10px;
}

#content {
    flex-grow: 1;
    height: 100%;
    min-height: 400px;
    font-size: 16px;
    line-height: 1.6;
    padding: 15px;
    resize: vertical;
}

.preview-content {
    flex-grow: 1;
    height: 100%;
    min-height: 400px;
    overflow-y: auto;
    padding: 15px;
    background-color: #2a2a2a;
    border-radius: 8px;
    border: 1px solid #555;
}

.markdown-toolbar {
    background-color: #333;
    padding: 8px;
    border-radius: 4px 4px 0 0;
    border: 1px solid #555;
    border-bottom: none;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.markdown-toolbar button {
    background: #444;
    border: 1px solid #666;
    color: #fff;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.markdown-toolbar button:hover {
    background: #555;
    transform: translateY(-1px);
}

/* Responsividade */
@media (max-width: 768px) {
    .success-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .success-button {
        width: 100%;
        max-width: 250px;
        text-align: center;
    }
    
    .editor-container {
        grid-template-columns: 1fr;
        height: auto;
        min-height: auto;
    }
    
    .preview {
        order: -1;
        max-height: 300px;
    }
    
    #content {
        min-height: 300px;
    }
    
    .preview-content {
        min-height: 250px;
    }
    
    .markdown-toolbar {
        flex-wrap: wrap;
    }
    
    .markdown-toolbar button {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentTextarea = document.getElementById('content');
    const previewDiv = document.getElementById('preview-content');
    
    if (contentTextarea && previewDiv) {
        function updatePreview() {
            const markdown = contentTextarea.value;
            
            const formData = new FormData();
            formData.append('markdown', markdown);
            
            fetch('markdown_preview.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.html) {
                    previewDiv.innerHTML = data.html;
                }
            })
            .catch(error => {
                console.error('Erro ao renderizar markdown:', error);
            });
        }
        
        contentTextarea.addEventListener('input', updatePreview);
        updatePreview();
        
        // Barra de ferramentas Markdown
        const toolbarButtons = document.querySelectorAll('.markdown-toolbar button');
        
        toolbarButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const action = this.getAttribute('data-action');
                
                const start = contentTextarea.selectionStart;
                const end = contentTextarea.selectionEnd;
                const selectedText = contentTextarea.value.substring(start, end);
                
                let insertText = '';
                
                switch(action) {
                    case 'heading1':
                        insertText = `# ${selectedText || 'Título principal'}`;
                        break;
                    case 'heading2':
                        insertText = `## ${selectedText || 'Subtítulo'}`;
                        break;
                    case 'bold':
                        insertText = `**${selectedText || 'texto em negrito'}**`;
                        break;
                    case 'italic':
                        insertText = `*${selectedText || 'texto em itálico'}*`;
                        break;
                    case 'link':
                        insertText = `[${selectedText || 'texto do link'}](https://exemplo.com)`;
                        break;
                    case 'image':
                        insertText = `![${selectedText || 'descrição da imagem'}](https://exemplo.com/imagem.jpg)`;
                        break;
                    case 'list':
                        insertText = `- ${selectedText || 'Item da lista'}\n- Outro item\n- Mais um item`;
                        break;
                    case 'orderedList':
                        insertText = `1. ${selectedText || 'Primeiro item'}\n2. Segundo item\n3. Terceiro item`;
                        break;
                    case 'code':
                        insertText = `\`\`\`\n${selectedText || 'seu código aqui'}\n\`\`\``;
                        break;
                    case 'quote':
                        insertText = `> ${selectedText || 'Citação'}`;
                        break;
                }
                
                contentTextarea.value = 
                    contentTextarea.value.substring(0, start) +
                    insertText +
                    contentTextarea.value.substring(end);
                
                contentTextarea.focus();
                contentTextarea.setSelectionRange(start + insertText.length, start + insertText.length);
                
                updatePreview();
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>