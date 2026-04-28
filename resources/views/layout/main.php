<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= htmlspecialchars($pageTitle ?? 'Kelps Blog', ENT_QUOTES) ?></title>
    <link rel="icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/posts.css">
    <?php if (!empty($extraCss)): ?>
        <link rel="stylesheet" href="/css/<?= htmlspecialchars($extraCss) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/../components/navbar.php'; ?>

<?php include __DIR__ . '/../components/flash.php'; ?>

<main class="main-container">
    <?= $content ?>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script src="/js/app.js"></script>
<?php if (!empty($extraJs)): ?>
    <script src="/js/<?= htmlspecialchars($extraJs) ?>"></script>
<?php endif; ?>
</body>
</html>
