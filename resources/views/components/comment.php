<?php
/** @var array $comment */
$canDelete = !empty($currentUser['id']) &&
             ((int)$currentUser['id'] === (int)$comment['user_id'] || !empty($currentUser['is_admin']));
?>
<div class="comment" id="comment-<?= $comment['id'] ?>">
    <div class="comment-header">
        <a href="/profile/<?= $comment['user_id'] ?>" class="author-link">
            <i class="fas fa-user-circle"></i>
            <?= htmlspecialchars($comment['username'], ENT_QUOTES) ?>
            <?php if (!empty($comment['is_admin']) && $comment['is_admin'] !== 'f'): ?>
                <span class="admin-badge"><i class="fas fa-shield-alt"></i></span>
            <?php endif; ?>
        </a>
        <small><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></small>

        <?php if ($canDelete): ?>
            <button class="btn-delete-comment btn-icon"
                    data-comment-id="<?= $comment['id'] ?>"
                    title="Deletar comentário">
                <i class="fas fa-trash"></i>
            </button>
        <?php endif; ?>
    </div>
    <p class="comment-body"><?= htmlspecialchars($comment['content'], ENT_QUOTES) ?></p>
</div>
