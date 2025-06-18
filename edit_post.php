<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se o ID do post foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Buscar o post e verificar se o usuário é o autor
$query = "SELECT * FROM posts WHERE id = $post_id AND user_id = $user_id";
$result = pg_query($dbconn, $query);

if (!$result || pg_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}

$post = pg_fetch_assoc($result);
$errors = [];
$success = false;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($title)) {
        $errors[] = "O título é obrigatório";
    }
    if (empty($content)) {
        $errors[] = "O conteúdo é obrigatório";
    }
    
    if (empty($errors)) {
        $title = pg_escape_string($dbconn, $title);
        $content = pg_escape_string($dbconn, $content);
        
        $check_column_query = "SELECT column_name FROM information_schema.columns 
                              WHERE table_name='posts' AND column_name='updated_at'";
        $check_column_result = pg_query($dbconn, $check_column_query);
        $column_exists = pg_num_rows($check_column_result) > 0;
        
        if ($column_exists) {
            $update_query = "UPDATE posts SET title = '$title', content = '$content', updated_at = NOW() 
                             WHERE id = $post_id AND user_id = $user_id";
        } else {
            $update_query = "UPDATE posts SET title = '$title', content = '$content' 
                             WHERE id = $post_id AND user_id = $user_id";
        }
        
        $update_result = pg_query($dbconn, $update_query);
        
        if ($update_result) {
            $success = true;
        } else {
            $errors[] = "Erro ao atualizar post: " . pg_last_error($dbconn);
        }
    }
}

// Definir variáveis para o header
$page_title = "Editar Post - Kelps Blog";
$current_page = 'edit';

// Incluir o header
include 'includes/header.php';
?>

<div class="container">
    <?php if ($success): ?>
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="success-title">Post atualizado com sucesso!</h2>
            <p class="success-message">
                Seu post foi atualizado e as alterações já estão disponíveis.
                Você pode visualizá-lo ou voltar para a página inicial.
            </p>
            <div class="success-actions">
                <a href="post.php?id=<?php echo $post_id; ?>" class="success-button primary">
                    <i class="fas fa-eye"></i> Ver meu post
                </a>
                <a href="index.php" class="success-button">
                    <i class="fas fa-home"></i> Página inicial
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
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($post['title']); ?>">
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
                    <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
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
            
            <div class="form-row" style="display: flex; justify-content: space-between; margin-top: 20px; flex-wrap: wrap; gap: 15px;">
                <button type="submit" class="action-button">
                    <i class="fas fa-save"></i> Atualizar Post
                </button>
                <a href="delete_post.php?id=<?php echo $post_id; ?>" class="delete-button" 
                   onclick="return confirm('Tem certeza que deseja excluir este post? Esta ação não pode ser desfeita.');">
                    <i class="fas fa-trash-alt"></i> Excluir Post
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
/* Reutilizar os mesmos estilos do create_post.php */
.form-row {
    margin-bottom: 20px;
}

.success-container {
    background-color: rgba(40, 40, 40, 0.9);
    border-radius: 8px;
    padding: 25px;
    text-align: center;
    max-width: 600px;
    margin: 20px auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    border: 1px solid #444;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
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

.delete-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background-color: #e03131;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 600;
}

.delete-button:hover {
    background-color: #c92a2a;
    transform: translateY(-2px);
}

.markdown-tips {
    background-color: #333;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}

.markdown-tips h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
}

.markdown-tips p {
    margin: 0;
    color: #ccc;
    line-height: 1.5;
}

.editor-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 20px;
    flex: 1;
    min-height: 400px;
    max-height: 600px; /* Altura máxima para evitar overflow */
}

@media (min-width: 992px) {
    .editor-container {
        grid-template-columns: 1fr 1fr;
    }
}

.editor, .preview {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.editor-header {
    margin-bottom: 10px;
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.editor-header label {
    font-weight: 600;
    color: #fff;
}

.editor-header span {
    font-size: 0.9rem;
    color: #ccc;
}

#content {
    flex: 1;
    min-height: 300px;
    max-height: 500px;
    font-size: 16px;
    line-height: 1.6;
    padding: 15px;
    resize: vertical;
    background-color: #2a2a2a;
    border: 1px solid #555;
    border-radius: 8px;
    color: #fff;
    font-family: 'Courier New', monospace;
}

.preview-content {
    flex: 1;
    min-height: 300px;
    max-height: 500px;
    overflow-y: auto;
    padding: 15px;
    background-color: #2a2a2a;
    border-radius: 8px;
    border: 1px solid #555;
    color: #fff;
}

/* Barra de ferramentas ajustada */
.markdown-toolbar {
    background-color: #333;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #555;
    margin-bottom: 10px;
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

/* Ações do formulário */
.form-row:last-child {
    margin-top: auto; /* Empurra os botões para baixo */
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

/* Responsividade específica */
@media (max-width: 768px) {
    .editor-container {
        grid-template-columns: 1fr;
        min-height: auto;
        max-height: none;
    }
    
    .preview {
        order: -1;
        max-height: 250px;
    }
    
    #content {
        min-height: 250px;
        max-height: 400px;
    }
    
    .preview-content {
        min-height: 200px;
        max-height: 250px;
    }
    
    .form-row:last-child {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-button,
    .delete-button {
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
}

@media (max-width: 480px) {
    .markdown-toolbar {
        padding: 5px;
    }
    
    .markdown-toolbar button {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
    
    .editor-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .editor-header span {
        margin-top: 5px;
    }
}

/* Error message */
.error-message {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid #dc3545;
    border-left: 4px solid #dc3545;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.error-message ul {
    margin: 0;
    padding-left: 20px;
    color: #dc3545;
}

.error-message li {
    margin-bottom: 5px;
}

/* Garantir que o footer não interfira */
footer {
    margin-top: auto;
    flex-shrink: 0;
}
</style>

<script>
// Mesmo script do create_post.php
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