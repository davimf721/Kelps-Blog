<footer class="site-footer">
    <div class="footer-inner">

        <div class="footer-brand">
            <a href="/" class="footer-logo">
                <img src="/images/logo.png" alt="Kelps Blog" onerror="this.style.display='none'">
                <span>Kelps Blog</span>
            </a>
            <p class="footer-tagline">Um espaço para escrever, aprender<br>e se conectar com outros criadores.</p>
        </div>

        <div class="footer-nav">
            <div class="footer-col">
                <h4>Explorar</h4>
                <a href="/">Posts recentes</a>
                <a href="/register">Criar conta</a>
                <a href="/login">Entrar</a>
            </div>
            <div class="footer-col">
                <h4>Conta</h4>
                <?php
                $fUid   = $currentUser['id']       ?? null;
                $fUname = $currentUser['username']  ?? null;
                if ($fUid): ?>
                    <a href="/profile/<?= $fUid ?>">Meu perfil</a>
                    <a href="/posts/create">Novo post</a>
                    <a href="/notifications">Notificações</a>
                <?php else: ?>
                    <a href="/register">Cadastrar-se</a>
                    <a href="/login">Fazer login</a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Kelps Blog &mdash; Todos os direitos reservados.</p>
    </div>
</footer>
