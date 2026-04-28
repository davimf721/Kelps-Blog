<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <title><?= htmlspecialchars($pageTitle ?? 'Kelps Blog', ENT_QUOTES) ?></title>
    <link rel="icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:'Arial',sans-serif;line-height:1.6}
    </style>
</head>
<body class="auth-page">
    <?= $content ?>
</body>
</html>
