<?php $pageTitle = 'Novo post — Kelps Blog'; ?>

<div class="page-header">
    <h1><i class="fas fa-pen-fancy"></i> Novo post</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
<?php endif; ?>

<form method="POST" action="/posts" class="post-form">
    <?= $csrf ?>

    <div class="form-group">
        <label for="title">Título</label>
        <input type="text" id="title" name="title" required maxlength="200"
               value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES) ?>"
               placeholder="Título do post">
    </div>

    <div class="form-group">
        <label for="content">Conteúdo <small>(suporte a Markdown)</small></label>
        <div class="editor-toolbar">
            <button type="button" data-action="bold" title="Negrito"><b>B</b></button>
            <button type="button" data-action="italic" title="Itálico"><i>I</i></button>
            <button type="button" data-action="heading" title="Título">H</button>
            <button type="button" data-action="link" title="Link"><i class="fas fa-link"></i></button>
            <button type="button" data-action="image" title="Imagem"><i class="fas fa-image"></i></button>
            <button type="button" data-action="code" title="Código"><i class="fas fa-code"></i></button>
            <button type="button" id="preview-toggle">Visualizar</button>
        </div>
        <textarea id="content" name="content" required rows="16"
                  placeholder="Escreva seu post em Markdown..."><?= htmlspecialchars($old['content'] ?? '', ENT_QUOTES) ?></textarea>
        <div id="preview-area" class="markdown-content" style="display:none"></div>
    </div>

    <div class="form-actions">
        <a href="/" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">
            <i class="fas fa-paper-plane"></i> Publicar
        </button>
    </div>
</form>

<script src="/js/editor.js"></script>
