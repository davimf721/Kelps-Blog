<?php $pageTitle = 'Comentários — Admin'; ?>

<div class="admin-header">
    <h1><i class="fas fa-comments"></i> Comentários (<?= number_format($total) ?>)</h1>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Conteúdo</th>
            <th>Autor</th>
            <th>Post</th>
            <th>Data</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($comments as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars(mb_substr($c['content'], 0, 80), ENT_QUOTES) ?>...</td>
                <td><a href="/profile/<?= $c['user_id'] ?>"><?= htmlspecialchars($c['username'], ENT_QUOTES) ?></a></td>
                <td><a href="/posts/<?= $c['post_id'] ?>"><?= htmlspecialchars($c['post_title'], ENT_QUOTES) ?></a></td>
                <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <form method="POST" action="/admin/comments/<?= $c['id'] ?>/delete" style="display:inline"
                          onsubmit="return confirm('Deletar este comentário?')">
                        <?= $csrf ?>
                        <button class="btn-sm btn-danger"><i class="fas fa-trash"></i> Deletar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
