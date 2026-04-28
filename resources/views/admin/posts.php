<?php $pageTitle = 'Posts — Admin'; ?>

<div class="admin-header">
    <h1><i class="fas fa-newspaper"></i> Posts (<?= number_format($total) ?>)</h1>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Autor</th>
            <th>Data</th>
            <th>Comentários</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($posts as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><a href="/posts/<?= $p['id'] ?>"><?= htmlspecialchars($p['title'], ENT_QUOTES) ?></a></td>
                <td><a href="/profile/<?= $p['user_id'] ?>"><?= htmlspecialchars($p['author'], ENT_QUOTES) ?></a></td>
                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                <td><?= $p['comments_count'] ?></td>
                <td>
                    <form method="POST" action="/admin/posts/<?= $p['id'] ?>/delete" style="display:inline"
                          onsubmit="return confirm('Deletar este post?')">
                        <?= $csrf ?>
                        <button class="btn-sm btn-danger"><i class="fas fa-trash"></i> Deletar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
