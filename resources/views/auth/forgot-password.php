<?php $pageTitle = 'Recuperar senha — Kelps Blog'; ?>

<header class="auth-header">
    <a href="/login"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
    <h1><i class="fas fa-key"></i> Recuperar senha</h1>
</header>

<main class="auth-main">
    <section class="auth-card">
        <?php if (!empty($success)): ?>
            <div class="message success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES) ?></div>
        <?php else: ?>
            <p>Informe seu e-mail cadastrado e enviaremos as instruções para redefinir sua senha.</p>

            <form method="POST" action="/forgot-password">
                <?= $csrf ?>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> E-mail</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <button type="submit" class="btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Enviar instruções
                </button>
            </form>
        <?php endif; ?>
    </section>
</main>

<footer class="auth-footer">
    <p>&copy; <?= date('Y') ?> Kelps Blog</p>
</footer>
