<?php
session_start();
require_once 'includes/db_connect.php'; // Certifique-se que o caminho está correto

$errors = [];
$login_identifier = ''; // Pode ser username ou email

// Se o usuário já estiver logado, redirecionar para a página inicial
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verificar se há mensagem de sucesso do registro
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Limpar a mensagem após exibir
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // O formulário original tinha campos para username E email para login.
    // Normalmente, o login é feito com um identificador (username OU email) e senha.
    // Vamos assumir que o usuário pode digitar username ou email no campo "login_identifier".
    // Se você quiser manter campos separados, ajuste o HTML e o PHP.
    
    $login_identifier = trim($_POST['login_identifier']); // Campo para username ou email
    $password = $_POST['password'];

    if (empty($login_identifier)) {
        $errors[] = "Nome de usuário ou email é obrigatório.";
    }
    if (empty($password)) {
        $errors[] = "Senha é obrigatória.";
    }

    if (empty($errors)) {
        // Tentar encontrar o usuário pelo username OU email
        $sql = "SELECT id, username, password_hash FROM users WHERE username = $1 OR email = $1";
        $result = pg_query_params($dbconn, $sql, array($login_identifier));

        if ($result && pg_num_rows($result) == 1) {
            $user = pg_fetch_assoc($result);
            // Verificar a senha
            if (password_verify($password, $user['password_hash'])) {
                // Senha correta, iniciar sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirecionar para a página inicial ou painel
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Nome de usuário/email ou senha inválidos.";
            }
        } else {
            $errors[] = "Nome de usuário/email ou senha inválidos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <header>
        <!-- Seu cabeçalho aqui, pode incluir navegação -->
        <h1>Login na Sua Conta</h1>
    </header>

    <main>
        <section class="auth-section">
            <?php if (!empty($success_message)): ?>
                <div class="message success">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="login-form" method="POST" action="login.php">
                <div>
                    <label for="login_identifier">Nome de Usuário ou Email:</label>
                    <input type="text" id="login_identifier" name="login_identifier" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
                </div>
                <div>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
                <div id="login-message" class="message">
                     <!-- Mensagens de JS podem ser removidas ou adaptadas -->
                </div>
            </form>
            <p>Não tem uma conta? <a href="register.php">Registre-se aqui</a>.</p>
            <p><a href="index.php" class="back-link">Voltar para Home</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. All rights reserved.</p>
    </footer>
     <!-- O script auth.js pode precisar de ajustes ou ser removido se toda a lógica for PHP -->
    <!-- <script src="js/auth.js"></script> -->
</body>
</html>