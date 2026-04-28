<?php
/** @var array[] $notifications */
$pageTitle = 'Notificações — Kelps Blog';

$icons = [
    'comment' => 'fas fa-comment',
    'upvote'  => 'fas fa-arrow-up',
    'follow'  => 'fas fa-user-plus',
    'new_post'=> 'fas fa-newspaper',
];
?>

<div class="page-header">
    <h1><i class="fas fa-bell"></i> Notificações</h1>
</div>

<?php if (empty($notifications)): ?>
    <div class="no-posts">Nenhuma notificação.</div>
<?php else: ?>
    <div class="notifications-list">
        <?php foreach ($notifications as $n): ?>
            <div class="notification-item <?= $n['is_read'] === 'f' ? 'unread' : '' ?>">
                <i class="<?= $icons[$n['type']] ?? 'fas fa-bell' ?> notif-icon"></i>
                <div class="notif-body">
                    <?php if (!empty($n['actor_username'])): ?>
                        <a href="/profile/<?= $n['actor_id'] ?>">
                            <strong><?= htmlspecialchars($n['actor_username'], ENT_QUOTES) ?></strong>
                        </a>
                    <?php endif; ?>
                    <?= htmlspecialchars($n['message'], ENT_QUOTES) ?>
                    <?php if (!empty($n['post_id'])): ?>
                        — <a href="/posts/<?= $n['post_id'] ?>">Ver post</a>
                    <?php endif; ?>
                </div>
                <small class="notif-time"><?= date('d/m H:i', strtotime($n['created_at'])) ?></small>
                <button class="btn-icon btn-delete-notif" data-id="<?= $n['id'] ?>">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="/js/notifications.js"></script>
