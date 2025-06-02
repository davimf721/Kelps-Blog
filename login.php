<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 require_once 'includes/db_connect.php';

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

// Processar o formulário de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_identifier = trim($_POST['login_identifier']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;

    if (empty($login_identifier)) {
        $errors[] = "Nome de usuário ou email é obrigatório.";
    }
    if (empty($password)) {
        $errors[] = "Senha é obrigatória.";
    }

    if (empty($errors)) {
        // Verificar qual coluna de senha está sendo usada (password_hash ou password)
        $check_col = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                      WHERE table_name='users' AND column_name='password_hash'");
        $password_column = pg_num_rows($check_col) > 0 ? 'password_hash' : 'password';
        
        // Verificar se a coluna is_admin existe
        $check_admin_col = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                           WHERE table_name='users' AND column_name='is_admin'");
        $has_admin_col = pg_num_rows($check_admin_col) > 0;
        
        // Construir a consulta SQL
        $sql = "SELECT id, username, {$password_column} as password_field";
        
        // Adicionar campos condicionais à consulta
        if ($has_admin_col) {
            $sql .= ", is_admin";
        }
        
        // Verificar se a coluna is_active existe
        $check_active_col = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                            WHERE table_name='users' AND column_name='is_active'");
        if (pg_num_rows($check_active_col) > 0) {
            $sql .= ", is_active";
        }
        
        $sql .= " FROM users WHERE username = $1 OR email = $1";
        
        $result = pg_query_params($dbconn, $sql, array($login_identifier));

        if ($result && pg_num_rows($result) == 1) {
            $user = pg_fetch_assoc($result);
            
            // Verificar se a conta está ativa
            if (isset($user['is_active']) && $user['is_active'] == 'f') {
                $errors[] = "Esta conta foi desativada. Entre em contato com o administrador.";
            }
            // Verificar a senha
            else if (password_verify($password, $user['password_field'])) {
                // Senha correta, iniciar sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Definir status de admin se a coluna existir
                if (isset($user['is_admin'])) {
                    $_SESSION['is_admin'] = ($user['is_admin'] == 't');
                }
                
                // Se o usuário marcou "lembrar-me", criar um cookie
                if ($remember_me) {
                    // Verificar se a tabela tem as colunas necessárias
                    $check_remember_cols = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                                         WHERE table_name='users' AND column_name='remember_token'");
                    if (pg_num_rows($check_remember_cols) > 0) {
                        // Gerar um token único
                        $token = bin2hex(random_bytes(32));
                        
                        // Armazenar o token no banco de dados
                        $sql = "UPDATE users SET remember_token = $1, token_expires = NOW() + INTERVAL '30 days' WHERE id = $2";
                        pg_query_params($dbconn, $sql, array($token, $user['id']));
                        
                        // Criar um cookie que dura 30 dias
                        setcookie(
                            'remember_token',
                            $token,
                            [
                                'expires' => time() + 60 * 60 * 24 * 30,
                                'path' => '/',
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]
                        );
                    }
                }
                
                // Redirecionar para a página inicial
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
<body class="auth-page">
    <header>
        <h1>Login na Sua Conta</h1>
    </header>

    <main class="auth-main">
        <section class="auth-section">
            <!-- Mensagens de sucesso ou erro -->
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

            <!-- Formulário de login -->
            <form id="login-form" method="POST" action="login.php">
                <div>
                    <label for="login_identifier">Nome de Usuário ou Email:</label>
                    <input type="text" id="login_identifier" name="login_identifier" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
                </div>
                <div>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Lembrar de mim</label>
                </div>
                <button type="submit">LOGIN</button>
            </form>
            <p>Não tem uma conta? <a href="register.php">Registre-se aqui</a>.</p>
            <a href="index.php" class="back-link">Voltar para Home</a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. All rights reserved.</p>
    </footer>
</body>
</html>