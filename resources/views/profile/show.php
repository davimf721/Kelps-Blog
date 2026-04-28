<?php
/** @var array $user */
/** @var array[] $posts */
/** @var bool $isFollowing */
/** @var bool $isOwner */
$pageTitle  = htmlspecialchars($user['username'], ENT_QUOTES) . ' — Kelps Blog';
$isLoggedIn = !empty($currentUser['id']);
?>

<div class="profile-container">
    <!-- Banner -->
    <div class="profile-banner"
         style="background-image: url('<?= !empty($user['banner_image'])
             ? '/uploads/banners/' . htmlspecialchars($user['banner_image'], ENT_QUOTES)
             : '/images/default-banner.png' ?>')">
    </div>

    <div class="profile-header">
        <img src="<?= !empty($user['profile_picture'])
            ? '/uploads/avatars/' . htmlspecialchars($user['profile_picture'], ENT_QUOTES)
            : '/images/default-profile.png' ?>"
             alt="Avatar de <?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
             class="profile-avatar">

        <div class="profile-info">
            <h1>
                <?= htmlspecialchars($user['username'], ENT_QUOTES) ?>
                <?php if (!empty($user['is_admin']) && $user['is_admin'] !== 'f'): ?>
                    <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                <?php endif; ?>
            </h1>

            <?php if (!empty($user['bio'])): ?>
                <p class="profile-bio"><?= htmlspecialchars($user['bio'], ENT_QUOTES) ?></p>
            <?php endif; ?>

            <div class="profile-meta">
                <?php if (!empty($user['location'])): ?>
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['location'], ENT_QUOTES) ?></span>
                <?php endif; ?>
                <?php if (!empty($user['website'])): ?>
                    <a href="<?= htmlspecialchars($user['website'], ENT_QUOTES) ?>" target="_blank" rel="noopener">
                        <i class="fas fa-link"></i> Website
                    </a>
                <?php endif; ?>
                <span><i class="fas fa-calendar"></i> Desde <?= date('M/Y', strtotime($user['created_at'])) ?></span>
            </div>

            <div class="profile-stats">
                <div class="stat">
                    <strong><?= number_format($user['posts_count']) ?></strong>
                    <span>posts</span>
                </div>
                <div class="stat">
                    <strong><?= number_format($user['followers_count']) ?></strong>
                    <span>seguidores</span>
                </div>
                <div class="stat">
                    <strong><?= number_format($user['following_count']) ?></strong>
                    <span>seguindo</span>
                </div>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($isOwner): ?>
                <a href="/profile/edit" class="btn-secondary">
                    <i class="fas fa-edit"></i> Editar perfil
                </a>
            <?php elseif ($isLoggedIn): ?>
                <button class="btn-follow <?= $isFollowing ? 'following' : '' ?>"
                        data-user-id="<?= $user['id'] ?>"
                        data-csrf="<?= $csrfToken ?>">
                    <i class="fas <?= $isFollowing ? 'fa-user-check' : 'fa-user-plus' ?>"></i>
                    <span><?= $isFollowing ? 'Seguindo' : 'Seguir' ?></span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Posts do usuário -->
    <section class="profile-posts">
        <h2><i class="fas fa-newspaper"></i> Posts</h2>

        <?php if (empty($posts)): ?>
            <p class="no-posts">Nenhum post publicado ainda.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/../components/post-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<script src="/js/profile.js"></script>
