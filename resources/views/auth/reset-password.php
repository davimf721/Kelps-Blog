<?php $pageTitle = 'Nova senha — Kelps Blog'; ?>

<header>
    <a href="/login" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
    <h1><i class="fas fa-shield-alt"></i> Kelps Blog</h1>
</header>

<main class="auth-main">
    <section class="auth-section">
        <h2 class="auth-section-title">Definir nova senha</h2>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error, ENT_QUOTES) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/reset-password/<?= htmlspecialchars($token ?? '', ENT_QUOTES) ?>">
            <?= $csrf ?>
            <div>
                <label for="password"><i class="fas fa-lock"></i> Nova senha</label>
                <input type="password" id="password" name="password" required minlength="8" autofocus>
                <small>Mínimo 8 caracteres</small>
            </div>
            <div>
                <label for="password_confirm"><i class="fas fa-lock"></i> Confirmar nova senha</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit">
                <i class="fas fa-save"></i> Salvar nova senha
            </button>
        </form>
    </section>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Kelps Blog</p>
</footer>
