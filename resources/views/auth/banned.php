<?php $pageTitle = 'Conta suspensa — Kelps Blog'; ?>

<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:2rem;">
    <i class="fas fa-ban" style="font-size:4rem;color:#e74c3c;margin-bottom:1rem;"></i>
    <h1>Conta suspensa</h1>
    <p style="max-width:400px;margin:.5rem auto 2rem;">
        Sua conta foi suspensa pela administração do Kelps Blog.<br>
        Se acredita que isso foi um engano, entre em contato.
    </p>
    <form method="POST" action="/logout">
        <?= $csrf ?>
        <button type="submit" class="btn-primary">
            <i class="fas fa-sign-out-alt"></i> Sair
        </button>
    </form>
</div>
