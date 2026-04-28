<?php $pageTitle = 'Dashboard — Admin'; ?>

<div class="admin-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <div>
            <strong><?= number_format($totalUsers) ?></strong>
            <span>Usuários</span>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-newspaper"></i>
        <div>
            <strong><?= number_format($totalPosts) ?></strong>
            <span>Posts</span>
        </div>
    </div>
    <div class="stat-card">
        <i class="fas fa-comments"></i>
        <div>
            <strong><?= number_format($totalComments) ?></strong>
            <span>Comentários</span>
        </div>
    </div>
</div>

<div class="admin-quick-links" style="margin-top:2rem">
    <a href="/admin/users" class="btn-secondary"><i class="fas fa-users"></i> Gerenciar usuários</a>
    <a href="/admin/posts" class="btn-secondary"><i class="fas fa-newspaper"></i> Gerenciar posts</a>
    <a href="/admin/comments" class="btn-secondary"><i class="fas fa-comments"></i> Gerenciar comentários</a>
</div>
