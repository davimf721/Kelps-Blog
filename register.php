<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php'; // Certifique-se que o caminho está correto

$errors = [];
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Não trim a senha ainda

    // --- Validações ---
    if (empty($username)) {
        $errors[] = "Nome de usuário é obrigatório.";
    }
    if (empty($email)) {
        $errors[] = "Email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Formato de email inválido.";
    }
    if (empty($password)) {
        $errors[] = "Senha é obrigatória.";
    } elseif (strlen($password) < 6) { // Exemplo de validação de força
        $errors[] = "Senha deve ter pelo menos 6 caracteres.";
    }

    // Se não houver erros de validação básica, verificar no banco
    if (empty($errors)) {
        // Verificar se username ou email já existem
        $sql_check = "SELECT id FROM users WHERE username = $1 OR email = $2";
        $result_check = pg_query_params($dbconn, $sql_check, array($username, $email));

        if (!$result_check) {
            $errors[] = "Erro ao verificar usuário: " . pg_last_error($dbconn);
        } elseif (pg_num_rows($result_check) > 0) {
            $existing_user = pg_fetch_assoc($result_check);
            // Você pode querer ser mais específico sobre qual campo já existe
            $errors[] = "Nome de usuário ou email já cadastrado.";
        } else {
            // Hashear a senha
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Inserir novo usuário
            $sql_insert = "INSERT INTO users (username, email, password_hash) VALUES ($1, $2, $3)";
            $result_insert = pg_query_params($dbconn, $sql_insert, array($username, $email, $password_hash));

            if ($result_insert) {
                $_SESSION['success_message'] = "Usuário cadastrado com sucesso! Faça o login.";
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Erro ao cadastrar usuário: " . pg_last_error($dbconn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">
    <header>
        <h1>Criar uma Conta</h1>
    </header>

    <main class="auth-main">
        <section class="auth-section">
            <!-- Mensagens de erro -->
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de registro -->
            <form id="register-form" method="POST" action="register.php">
                <div>
                    <label for="username">Nome de Usuário:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">REGISTRAR</button>
            </form>
            <p>Já tem uma conta? <a href="login.php">Faça login aqui</a>.</p>
            <a href="index.php" class="back-link">Voltar para Home</a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. All rights reserved.</p>
    </footer>
</body>
</html>