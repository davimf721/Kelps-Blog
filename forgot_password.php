<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/email_config.php';
require_once 'includes/EmailSender.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Verificar se o email existe no banco
        $query = "SELECT id, username, email FROM users WHERE email = $1 AND is_active = TRUE";
        $result = pg_query_params($dbconn, $query, [$email]);
        
        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            // Gerar token único e seguro
            $reset_token = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expira em 1 hora
            
            // Salvar token no banco
            $update_query = "UPDATE users SET reset_token = $1, reset_token_expires = $2 WHERE id = $3";
            $update_result = pg_query_params($dbconn, $update_query, [$reset_token, $token_expires, $user['id']]);
            
            if ($update_result) {
                // Criar link de recuperação
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $reset_token;
                
                // Inicializar classe de email
                $emailSender = new EmailSender($email_config);
                
                // Tentar enviar email
                $result = $emailSender->sendPasswordReset(
                    $user['email'],
                    $user['username'],
                    $reset_link,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                );
                
                if ($result['success']) {
                    $message = "✅ Um email com instruções para redefinir sua senha foi enviado para: <strong>" . htmlspecialchars($email) . "</strong>";
                    $message_type = 'success';
                    
                    // Log da solicitação
                    $log_query = "INSERT INTO user_logs (user_id, action, ip_address, user_agent, created_at) 
                                 VALUES ($1, 'password_reset_requested', $2, $3, NOW())";
                    pg_query_params($dbconn, $log_query, [
                        $user['id'],
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                } else {
                    // Para desenvolvimento, mostrar o link diretamente
                    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                        strpos($_SERVER['HTTP_HOST'], 'dev') !== false) {
                        
                        $message = "⚠️ <strong>Modo Desenvolvimento:</strong> Email não configurado. Use este link para redefinir: <br><a href='" . $reset_link . "' target='_blank' style='color: #007bff; word-break: break-all;'>" . $reset_link . "</a>";
                        $message_type = 'success';
                    } else {
                        $message = "❌ Erro ao enviar email. Tente novamente mais tarde ou entre em contato com o suporte.";
                        $message_type = 'error';
                    }
                }
            } else {
                $message = "❌ Erro interno do sistema. Tente novamente mais tarde.";
                $message_type = 'error';
            }
        } else {
            // Por segurança, sempre mostrar sucesso mesmo se email não existir
            $message = "✅ Se o email informado estiver cadastrado em nossa base, você receberá instruções para redefinir sua senha.";
            $message_type = 'success';
        }
    } else {
        $message = "❌ Por favor, informe um endereço de email válido.";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page">
    <header>
        <h1><i class="fas fa-key"></i> Recuperar Senha</h1>
    </header>

    <main class="auth-main">
        <section class="auth-section">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <p>
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (empty($message) || $message_type === 'error'): ?>
                <div class="recovery-info">
                    <p><i class="fas fa-info-circle"></i> Informe seu email cadastrado para receber as instruções de recuperação de senha.</p>
                    <p><small>📧 Verifique também sua caixa de spam após enviar a solicitação.</small></p>
                </div>

                <form method="POST" action="" id="forgotForm">
                    <div>
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email:
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required 
                               autocomplete="email"
                               placeholder="seu.email@exemplo.com">
                    </div>
                    
                    <button type="submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> ENVIAR INSTRUÇÕES
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <p><a href="login.php"><i class="fas fa-arrow-left"></i> Voltar ao Login</a></p>
                <p>Não tem uma conta? <a href="register.php"><i class="fas fa-user-plus"></i> Cadastre-se aqui</a></p>
            </div>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-home"></i> Voltar para Home
            </a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ENVIANDO...';
                    submitBtn.style.opacity = '0.7';
                });
            }
        });
    </script>
</body>
</html>