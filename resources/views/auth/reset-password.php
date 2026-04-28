<?php $pageTitle = 'Nova senha — Kelps Blog'; ?>

<header class="auth-header">
    <h1><i class="fas fa-lock"></i> Definir nova senha</h1>
</header>

<main class="auth-main">
    <section class="auth-card">
        <?php if (!empty($error)): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <form method="POST" action="/reset-password/<?= htmlspecialchars($token ?? '', ENT_QUOTES) ?>">
            <?= $csrf ?>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Nova senha</label>
                <input type="password" id="password" name="password" required minlength="8" autofocus>
            </div>
            <div class="form-group">
                <label for="password_confirm"><i class="fas fa-lock"></i> Confirmar nova senha</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn-primary btn-block">
                <i class="fas fa-save"></i> Salvar nova senha
            </button>
        </form>
    </section>
</main>

<footer class="auth-footer">
    <p>&copy; <?= date('Y') ?> Kelps Blog</p>
</footer>
