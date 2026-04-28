<?php
/** @var array $post */
/** @var array[] $comments */
$pageTitle  = htmlspecialchars($post['title'], ENT_QUOTES) . ' — Kelps Blog';
$isLoggedIn = !empty($currentUser['id']);
$isOwner    = $isLoggedIn && (int)$currentUser['id'] === (int)$post['user_id'];
$isAdmin    = !empty($currentUser['is_admin']);
?>

<article class="post-detail">
    <header class="post-header">
        <h1><?= htmlspecialchars($post['title'], ENT_QUOTES) ?></h1>
        <div class="post-meta">
            <a href="/profile/<?= $post['user_id'] ?>" class="author-link">
                <i class="fas fa-user"></i>
                <?= htmlspecialchars($post['author'], ENT_QUOTES) ?>
                <?php if (!empty($post['author_is_admin']) && $post['author_is_admin'] !== 'f'): ?>
                    <span class="admin-badge"><i class="fas fa-shield-alt"></i></span>
                <?php endif; ?>
            </a>
            <span><i class="fas fa-clock"></i> <?= date('d/m/Y \à\s H:i', strtotime($post['created_at'])) ?></span>
            <?php if (!empty($post['updated_at'])): ?>
                <span><i class="fas fa-edit"></i> Editado em <?= date('d/m/Y', strtotime($post['updated_at'])) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($isOwner || $isAdmin): ?>
            <div class="post-actions">
                <a href="/posts/<?= $post['id'] ?>/edit" class="btn-secondary btn-sm">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <form method="POST" action="/posts/<?= $post['id'] ?>/delete" style="display:inline"
                      onsubmit="return confirm('Deletar este post?')">
                    <?= $csrf ?>
                    <button class="btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Deletar
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <div class="post-body markdown-content">
        <?= $post['content_html'] ?? htmlspecialchars($post['content'], ENT_QUOTES) ?>
    </div>

    <!-- Upvote -->
    <div class="post-upvote">
        <button class="upvote-btn <?= (!empty($post['user_has_upvoted']) && $post['user_has_upvoted'] !== 'f') ? 'upvoted' : '' ?>"
                data-post-id="<?= $post['id'] ?>"
                <?= !$isLoggedIn ? 'disabled title="Faça login para votar"' : '' ?>>
            <i class="fas fa-arrow-up"></i>
            <span class="upvote-count"><?= (int)($post['upvotes_count'] ?? 0) ?></span>
            <span>votos</span>
        </button>
    </div>
</article>

<!-- Comentários -->
<section class="comments-section">
    <h2><i class="far fa-comment"></i> Comentários (<?= count($comments) ?>)</h2>

    <?php if ($isLoggedIn): ?>
        <form class="comment-form" id="comment-form" method="POST" data-post-id="<?= $post['id'] ?>">
            <?= $csrf ?>
            <textarea name="content" placeholder="Escreva um comentário..." required rows="3"></textarea>
            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Comentar
            </button>
        </form>
    <?php else: ?>
        <p><a href="/login">Faça login</a> para comentar.</p>
    <?php endif; ?>

    <div class="comments-list" id="comments-list">
        <?php foreach ($comments as $comment): ?>
            <?php include __DIR__ . '/../components/comment.php'; ?>
        <?php endforeach; ?>

        <?php if (empty($comments)): ?>
            <p class="no-comments">Nenhum comentário ainda. Seja o primeiro!</p>
        <?php endif; ?>
    </div>
</section>

<script src="/js/posts.js"></script>
