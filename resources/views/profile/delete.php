<?php $pageTitle = 'Excluir conta — Kelps Blog'; ?>

<div class="page-header">
    <h1><i class="fas fa-trash"></i> Excluir conta</h1>
</div>

<div class="danger-zone">
    <div class="message error">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Esta ação é permanente e irreversível.</strong>
        Todos os seus posts, comentários e dados serão excluídos.
    </div>

    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="POST" action="/profile/delete">
        <?= $csrf ?>
        <div class="form-group">
            <label for="password">Confirme sua senha para continuar</label>
            <input type="password" id="password" name="password" required autofocus>
        </div>
        <div class="form-actions">
            <a href="/profile/edit" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-danger"
                    onclick="return confirm('Tem certeza? Esta ação não pode ser desfeita!')">
                <i class="fas fa-trash"></i> Excluir minha conta permanentemente
            </button>
        </div>
    </form>
</div>
