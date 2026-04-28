<?php
/** @var array $post */
$isLoggedIn = !empty($currentUser['id']);
?>
<article class="post-card">
    <?php if (!empty($post['first_image'])): ?>
        <div class="post-image-preview">
            <a href="/posts/<?= $post['id'] ?>">
                <img src="<?= htmlspecialchars($post['first_image'], ENT_QUOTES) ?>"
                     alt="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>"
                     loading="lazy"
                     onerror="this.parentElement.style.display='none'">
            </a>
        </div>
    <?php endif; ?>

    <div class="post-card-content">
        <h3 class="post-card-title">
            <a href="/posts/<?= $post['id'] ?>"><?= htmlspecialchars($post['title'], ENT_QUOTES) ?></a>
        </h3>

        <p class="post-card-meta">
            <span>
                <i class="fas fa-user"></i>
                <a href="/profile/<?= $post['user_id'] ?>" class="author-link">
                    <?= htmlspecialchars($post['author'], ENT_QUOTES) ?>
                </a>
                <?php if (!empty($post['author_is_admin']) && $post['author_is_admin'] !== 'f'): ?>
                    <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                <?php endif; ?>
            </span>
            <span>
                <i class="fas fa-clock"></i>
                <?= date('d/m/Y', strtotime($post['created_at'])) ?>
            </span>
        </p>

        <p class="post-card-excerpt"><?= htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES) ?></p>

        <div class="post-card-actions">
            <button class="upvote-btn <?= (!empty($post['user_has_upvoted']) && $post['user_has_upvoted'] !== 'f') ? 'upvoted' : '' ?>"
                    data-post-id="<?= $post['id'] ?>"
                    <?= !$isLoggedIn ? 'disabled title="Faça login para votar"' : '' ?>>
                <i class="fas fa-arrow-up"></i>
                <span class="upvote-count"><?= (int)($post['upvotes_count'] ?? 0) ?></span>
            </button>

            <span class="comments-info">
                <i class="far fa-comment"></i>
                <?= (int)($post['comments_count'] ?? 0) ?>
            </span>

            <a href="/posts/<?= $post['id'] ?>" class="read-more-btn">
                Ler mais <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</article>
