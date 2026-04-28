<?php $pageTitle = 'Criar conta — Kelps Blog'; ?>

<header class="auth-header">
    <a href="/"><i class="fas fa-arrow-left"></i> Voltar</a>
    <h1><i class="fas fa-user-plus"></i> Criar conta</h1>
</header>

<main class="auth-main">
    <section class="auth-card">

        <?php if (!empty($error)): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <?= $csrf ?>

            <div class="form-group">
                <label for="username"><i class="fas fa-at"></i> Nome de usuário</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES) ?>"
                       required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+"
                       title="Apenas letras, números e _" autofocus>
                <small>3–30 caracteres. Letras, números e _</small>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> E-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES) ?>"
                       required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Senha</label>
                <input type="password" id="password" name="password"
                       required minlength="8" autocomplete="new-password">
                <small>Mínimo 8 caracteres</small>
            </div>

            <div class="form-group">
                <label for="password_confirm"><i class="fas fa-lock"></i> Confirmar senha</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Criar conta
            </button>
        </form>

        <div class="auth-links">
            <a href="/login">Já tem conta? Entrar</a>
        </div>
    </section>
</main>

<footer class="auth-footer">
    <p>&copy; <?= date('Y') ?> Kelps Blog</p>
</footer>
