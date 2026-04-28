<?php
/** @var array $post */
$pageTitle = 'Editar post — Kelps Blog';
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> Editar post</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
<?php endif; ?>

<form method="POST" action="/posts/<?= $post['id'] ?>/edit" class="post-form">
    <?= $csrf ?>

    <div class="form-group">
        <label for="title">Título</label>
        <input type="text" id="title" name="title" required maxlength="200"
               value="<?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES) ?>">
    </div>

    <div class="form-group">
        <label for="content">Conteúdo <small>(suporte a Markdown)</small></label>
        <div class="editor-toolbar">
            <button type="button" data-action="bold"><b>B</b></button>
            <button type="button" data-action="italic"><i>I</i></button>
            <button type="button" data-action="heading">H</button>
            <button type="button" data-action="link"><i class="fas fa-link"></i></button>
            <button type="button" data-action="image"><i class="fas fa-image"></i></button>
            <button type="button" id="preview-toggle">Visualizar</button>
        </div>
        <textarea id="content" name="content" required rows="16"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES) ?></textarea>
        <div id="preview-area" class="markdown-content" style="display:none"></div>
    </div>

    <div class="form-actions">
        <a href="/posts/<?= $post['id'] ?>" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">
            <i class="fas fa-save"></i> Salvar alterações
        </button>
    </div>
</form>

<script src="/js/editor.js"></script>
