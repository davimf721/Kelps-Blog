<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Validações ---
    if (empty($username)) {
        $errors[] = "Nome de usuário é obrigatório.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Nome de usuário deve ter pelo menos 3 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Nome de usuário deve conter apenas letras, números e underscore.";
    }

    if (empty($email)) {
        $errors[] = "Email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Formato de email inválido.";
    }

    if (empty($password)) {
        $errors[] = "Senha é obrigatória.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Senha deve ter pelo menos 6 caracteres.";
    }

    if (empty($confirm_password)) {
        $errors[] = "Confirmação de senha é obrigatória.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "As senhas não coincidem.";
    }

    // Se não houver erros de validação básica, verificar no banco
    if (empty($errors)) {
        // Verificar se username ou email já existem
        $sql_check = "SELECT username, email FROM users WHERE username = $1 OR email = $2";
        $result_check = pg_query_params($dbconn, $sql_check, array($username, $email));

        if (!$result_check) {
            $errors[] = "Erro ao verificar usuário: " . pg_last_error($dbconn);
        } elseif (pg_num_rows($result_check) > 0) {
            $existing = pg_fetch_assoc($result_check);
            if ($existing['username'] === $username) {
                $errors[] = "Nome de usuário já está em uso.";
            }
            if ($existing['email'] === $email) {
                $errors[] = "Email já está cadastrado.";
            }
        } else {
            // Hashear a senha
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Inserir novo usuário
            $sql_insert = "INSERT INTO users (username, email, password_hash) VALUES ($1, $2, $3)";
            $result_insert = pg_query_params($dbconn, $sql_insert, array($username, $email, $password_hash));

            if ($result_insert) {
                $_SESSION['success_message'] = "Usuário cadastrado com sucesso! Faça o login para continuar.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page">
    <header>
        <h1><i class="fas fa-user-plus"></i> Criar uma Conta</h1>
    </header>

    <main class="auth-main">
        <section class="auth-section">
            <!-- Mensagens de erro -->
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de registro -->
            <form method="POST" action="">
                <div>
                    <label for="username">
                        <i class="fas fa-user"></i> Nome de Usuário:
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($username); ?>" 
                           required
                           minlength="3"
                           pattern="[a-zA-Z0-9_]+"
                           title="Apenas letras, números e underscore são permitidos">
                </div>

                <div>
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email:
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           required>
                </div>

                <div>
                    <label for="password">
                        <i class="fas fa-lock"></i> Senha:
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           minlength="6"
                           title="A senha deve ter pelo menos 6 caracteres">
                </div>

                <div>
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmar Senha:
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required
                           minlength="6"
                           title="Confirme sua senha">
                </div>

                <button type="submit">
                    <i class="fas fa-user-plus"></i> REGISTRAR
                </button>
            </form>
            
            <p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
            <a href="index.php" class="back-link">
                <i class="fas fa-home"></i> Voltar para Home
            </a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Validação em tempo real da confirmação de senha
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('As senhas não coincidem');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);
        });
    </script>
</body>
</html>