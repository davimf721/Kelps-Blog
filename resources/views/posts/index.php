<?php
/** @var array[] $posts */
/** @var array $currentUser */
$pageTitle  = 'Kelps Blog';
$isLoggedIn = !empty($currentUser['id']);
?>

<?php if (!$isLoggedIn): ?>
<!-- ====== LANDING ====== -->
<div class="landing-hero">
    <div class="hero-content">
        <div class="hero-badge">✨ Plataforma de blog social</div>
        <h1 class="hero-title">
            Compartilhe <span class="gradient-text">conhecimento</span>,<br>
            conecte <span class="gradient-text">pessoas</span>
        </h1>
        <p class="hero-description">
            Um espaço para escrever, aprender e se conectar com outros criadores de conteúdo.
        </p>
        <div class="hero-actions">
            <a href="/register" class="btn-primary-hero">
                <i class="fas fa-rocket"></i> Criar conta grátis
            </a>
            <a href="/login" class="btn-secondary-hero">
                <i class="fas fa-sign-in-alt"></i> Já tenho conta
            </a>
        </div>
    </div>
</div>
<h2 class="section-title" style="margin-top:2.5rem">
    <i class="fas fa-fire"></i> Posts recentes
</h2>
<?php endif; ?>

<!-- ====== FEED ====== -->
<section class="posts-container" id="posts-container">
    <?php if (empty($posts)): ?>
        <div class="no-posts">
            <?= $isLoggedIn
                ? 'Nenhum post ainda. <a href="/posts/create">Seja o primeiro!</a>'
                : 'Nenhum post ainda. Cadastre-se e publique o primeiro!' ?>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php include __DIR__ . '/../components/post-card.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Paginação simples -->
<?php if (!empty($totalPosts) && $totalPosts > $perPage): ?>
<nav class="pagination" aria-label="Paginação">
    <?php if ($page > 1): ?>
        <a href="/?page=<?= $page - 1 ?>" class="btn-page"><i class="fas fa-chevron-left"></i> Anterior</a>
    <?php endif; ?>
    <span>Página <?= $page ?></span>
    <?php if ($page * $perPage < $totalPosts): ?>
        <a href="/?page=<?= $page + 1 ?>" class="btn-page">Próxima <i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>
</nav>
<?php endif; ?>
