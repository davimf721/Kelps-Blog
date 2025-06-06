<?php
session_start();
require_once 'includes/db_connect.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Verificar mensagem de sucesso do registro
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    if (!empty($username_or_email) && !empty($password)) {
        // Buscar usuário por username ou email, incluindo status de banimento
        $query = "SELECT id, username, email, password_hash, is_admin, is_banned 
                  FROM users 
                  WHERE (username = $1 OR email = $1) AND is_active = TRUE";
        $result = pg_query_params($dbconn, $query, [$username_or_email]);

        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            // Verificar se o usuário está banido
            if ($user['is_banned'] == 't') {
                $error_message = "Sua conta foi suspensa. Entre em contato com a administração para mais informações.";
            } else {
                // Verificar senha
                if (password_verify($password, $user['password_hash'])) {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = ($user['is_admin'] == 't');

                    // Se "Lembrar de mim" foi marcado
                    if ($remember_me) {
                        $remember_token = bin2hex(random_bytes(32));
                        $token_expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Salvar token no banco
                        $update_token = pg_query_params($dbconn, 
                            "UPDATE users SET remember_token = $1, token_expires = $2 WHERE id = $3",
                            [$remember_token, $token_expires, $user['id']]
                        );
                        
                        if ($update_token) {
                            // Criar cookie seguro (válido por 30 dias)
                            setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                        }
                    }

                    // Redirecionar
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: $redirect");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error_message = "Usuário ou senha incorretos.";
                }
            }
        } else {
            $error_message = "Usuário ou senha incorretos.";
        }
    } else {
        $error_message = "Por favor, preencha todos os campos.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page">
    <header>
        <h1><i class="fas fa-sign-in-alt"></i> Entrar</h1>
    </header>

    <main class="auth-main">
        <section class="auth-section">
            <!-- Mensagens de erro -->
            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Mensagens de sucesso -->
            <?php if (!empty($success_message)): ?>
                <div class="message success">
                    <p><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Formulário de login -->
            <form method="POST" action="">
                <div>
                    <label for="username_or_email">
                        <i class="fas fa-user"></i> Username ou Email:
                    </label>
                    <input type="text" 
                           id="username_or_email" 
                           name="username_or_email" 
                           value="<?php echo isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email']) : ''; ?>"
                           required 
                           autocomplete="username">
                </div>
                
                <div>
                    <label for="password">
                        <i class="fas fa-lock"></i> Senha:
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <label for="remember_me">Lembrar de mim</label>
                </div>
                
                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i> ENTRAR
                </button>
            </form>
            
            <p>Não tem uma conta? <a href="register.php">Cadastre-se aqui</a></p>
            <p><a href="forgot_password.php">Esqueceu sua senha?</a></p>
            <a href="index.php" class="back-link">
                <i class="fas fa-home"></i> Voltar para Home
            </a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>
</body>
</html>