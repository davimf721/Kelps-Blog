<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= htmlspecialchars($pageTitle ?? 'Painel', ENT_QUOTES) ?></title>
    <link rel="icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 220px; background: var(--bg-secondary, #1a1a2e); padding: 1.5rem 1rem; flex-shrink: 0; }
        .admin-sidebar a { display: flex; align-items: center; gap: .6rem; padding: .6rem .8rem; border-radius: 8px; color: var(--text-secondary, #aaa); text-decoration: none; margin-bottom: .3rem; transition: background .2s, color .2s; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--accent, #6c63ff22); color: var(--accent, #6c63ff); }
        .admin-sidebar .logo { font-size: 1.2rem; font-weight: 700; color: var(--text-primary, #fff); padding: .8rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border, #333); }
        .admin-main { flex: 1; padding: 2rem; overflow-y: auto; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-header h1 { font-size: 1.5rem; }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <div class="logo"><i class="fas fa-shield-alt"></i> Admin</div>
    <nav>
        <a href="/admin" <?= (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false && !(strpos($_SERVER['REQUEST_URI'] ?? '', '/users') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/posts') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/comments') !== false)) ? 'class="active"' : '' ?>>
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="/admin/users" <?= (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/users') !== false) ? 'class="active"' : '' ?>>
            <i class="fas fa-users"></i> Usuários
        </a>
        <a href="/admin/posts" <?= (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/posts') !== false) ? 'class="active"' : '' ?>>
            <i class="fas fa-newspaper"></i> Posts
        </a>
        <a href="/admin/comments" <?= (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/comments') !== false) ? 'class="active"' : '' ?>>
            <i class="fas fa-comments"></i> Comentários
        </a>
        <hr style="border-color: var(--border, #333); margin: 1rem 0;">
        <a href="/"><i class="fas fa-home"></i> Ver site</a>
        <a href="/logout" onclick="return confirm('Sair?')"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</aside>

<main class="admin-main">
    <?php include __DIR__ . '/../components/flash.php'; ?>
    <?= $content ?>
</main>

<script src="/js/app.js"></script>
</body>
</html>
