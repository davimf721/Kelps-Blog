<?php
/** @var array[] $users */
$pageTitle = 'Usuários — Admin';
?>

<div class="admin-header">
    <h1><i class="fas fa-users"></i> Usuários (<?= number_format($total) ?>)</h1>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Usuário</th>
            <th>E-mail</th>
            <th>Status</th>
            <th>Último login</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td>
                    <a href="/profile/<?= $u['id'] ?>"><?= htmlspecialchars($u['username'], ENT_QUOTES) ?></a>
                    <?php if ($u['is_admin'] !== 'f'): ?>
                        <span class="badge badge-admin">Admin</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['email'], ENT_QUOTES) ?></td>
                <td>
                    <?php if ($u['is_banned'] !== 'f'): ?>
                        <span class="badge badge-danger">Banido</span>
                    <?php else: ?>
                        <span class="badge badge-success">Ativo</span>
                    <?php endif; ?>
                </td>
                <td><?= $u['last_login_at'] ? date('d/m/Y', strtotime($u['last_login_at'])) : '—' ?></td>
                <td class="action-buttons">
                    <?php if ($u['is_banned'] !== 'f'): ?>
                        <form method="POST" action="/admin/users/<?= $u['id'] ?>/unban" style="display:inline">
                            <?= $csrf ?><button class="btn-sm btn-success">Desbanir</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/admin/users/<?= $u['id'] ?>/ban" style="display:inline">
                            <?= $csrf ?><button class="btn-sm btn-warning">Banir</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($u['is_admin'] !== 'f'): ?>
                        <form method="POST" action="/admin/users/<?= $u['id'] ?>/remove-admin" style="display:inline">
                            <?= $csrf ?><button class="btn-sm btn-secondary">Remover admin</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/admin/users/<?= $u['id'] ?>/make-admin" style="display:inline">
                            <?= $csrf ?><button class="btn-sm btn-primary">Tornar admin</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" action="/admin/users/<?= $u['id'] ?>/delete" style="display:inline"
                          onsubmit="return confirm('Excluir usuário <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?')">
                        <?= $csrf ?><button class="btn-sm btn-danger">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
