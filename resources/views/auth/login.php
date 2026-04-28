<?php $pageTitle = 'Entrar — Kelps Blog'; ?>

<header class="auth-header">
    <a href="/"><i class="fas fa-arrow-left"></i> Voltar</a>
    <h1><i class="fas fa-sign-in-alt"></i> Entrar</h1>
</header>

<main class="auth-main">
    <section class="auth-card">

        <?php if (!empty($error)): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <?php if (!empty($flash['success'])): ?>
            <div class="message success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['success'], ENT_QUOTES) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" autocomplete="on">
            <?= $csrf ?>

            <div class="form-group">
                <label for="username_or_email"><i class="fas fa-user"></i> Usuário ou E-mail</label>
                <input type="text" id="username_or_email" name="username_or_email"
                       value="<?= htmlspecialchars($old['username_or_email'] ?? '', ENT_QUOTES) ?>"
                       required autocomplete="username" autofocus>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Senha</label>
                <input type="password" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" name="remember_me" id="remember_me">
                <label for="remember_me">Lembrar de mim</label>
            </div>

            <button type="submit" class="btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="auth-links">
            <a href="/register">Não tem conta? Cadastre-se</a>
            <a href="/forgot-password">Esqueceu a senha?</a>
        </div>
    </section>
</main>

<footer class="auth-footer">
    <p>&copy; <?= date('Y') ?> Kelps Blog</p>
</footer>
