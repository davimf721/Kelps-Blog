<?php $pageTitle = 'Recuperar senha — Kelps Blog'; ?>

<header>
    <a href="/login" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
    <h1><i class="fas fa-key"></i> Kelps Blog</h1>
</header>

<main class="auth-main">
    <section class="auth-section">
        <h2 class="auth-section-title">Recuperar senha</h2>

        <?php if (!empty($success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success, ENT_QUOTES) ?>
            </div>
            <p><a href="/login" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao login</a></p>
        <?php else: ?>
            <p style="color:#b0b0b0;margin-bottom:4px">Informe seu e-mail e enviaremos as instruções para redefinir a senha.</p>

            <form method="POST" action="/forgot-password">
                <?= $csrf ?>
                <div>
                    <label for="email"><i class="fas fa-envelope"></i> E-mail</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Enviar instruções
                </button>
            </form>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Kelps Blog</p>
</footer>
