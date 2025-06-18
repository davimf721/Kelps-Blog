<?php
session_start();
require_once 'includes/db_connect.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$user_data = null;
$message = '';
$message_type = '';

// Verificar se o token é válido
if (!empty($token)) {
    $query = "SELECT id, username, email, reset_token_expires 
              FROM users 
              WHERE reset_token = $1 AND is_active = TRUE";
    $result = pg_query_params($dbconn, $query, [$token]);
    
    if ($result && pg_num_rows($result) > 0) {
        $user_data = pg_fetch_assoc($result);
        
        // Verificar se o token não expirou
        $current_time = new DateTime();
        $token_expires = new DateTime($user_data['reset_token_expires']);
        
        if ($current_time <= $token_expires) {
            $valid_token = true;
        } else {
            $message = "Este link de recuperação expirou. Solicite um novo link.";
            $message_type = 'error';
        }
    } else {
        $message = "Link de recuperação inválido ou já utilizado.";
        $message_type = 'error';
    }
} else {
    $message = "Token de recuperação não fornecido.";
    $message_type = 'error';
}

// Processar nova senha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validações
    if (empty($new_password)) {
        $errors[] = "Nova senha é obrigatória.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "A senha deve ter pelo menos 6 caracteres.";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Confirmação de senha é obrigatória.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "As senhas não coincidem.";
    }
    
    // Verificar força da senha
    if (!empty($new_password)) {
        $strength_score = 0;
        if (preg_match('/[a-z]/', $new_password)) $strength_score++;
        if (preg_match('/[A-Z]/', $new_password)) $strength_score++;
        if (preg_match('/[0-9]/', $new_password)) $strength_score++;
        if (preg_match('/[^a-zA-Z0-9]/', $new_password)) $strength_score++;
        if (strlen($new_password) >= 8) $strength_score++;
        
        if ($strength_score < 3) {
            $errors[] = "A senha deve conter pelo menos 3 dos seguintes: letras minúsculas, maiúsculas, números, símbolos, ou ter 8+ caracteres.";
        }
    }
    
    if (empty($errors)) {
        // Hash da nova senha
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Atualizar senha e limpar token
        $update_query = "UPDATE users 
                        SET password_hash = $1, 
                            reset_token = NULL, 
                            reset_token_expires = NULL,
                            updated_at = NOW()
                        WHERE id = $2";
        $update_result = pg_query_params($dbconn, $update_query, [$password_hash, $user_data['id']]);
        
        if ($update_result) {
            // Log da alteração
            $log_query = "INSERT INTO user_logs (user_id, action, ip_address, user_agent, created_at) 
                         VALUES ($1, 'password_reset', $2, $3, NOW())";
            pg_query_params($dbconn, $log_query, [
                $user_data['id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $_SESSION['success_message'] = "Senha redefinida com sucesso! Faça login com sua nova senha.";
            header("Location: login.php");
            exit();
        } else {
            $message = "Erro ao atualizar senha. Tente novamente.";
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .password-strength {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .strength-weak { background: #ffe6e6; color: #d63031; }
        .strength-medium { background: #fff3cd; color: #e17055; }
        .strength-strong { background: #d1f2eb; color: #00b894; }
        
        .password-requirements {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin: 5px 0;
            gap: 8px;
        }
        .requirement.met { color: #00b894; }
        .requirement.unmet { color: #ccc; }
    </style>
</head>
<body class="auth-page">
    <header>
        <h1><i class="fas fa-lock"></i> Redefinir Senha</h1>
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

            <?php if ($valid_token && $user_data): ?>
                <div class="user-info">
                    <p><i class="fas fa-user"></i> Redefinindo senha para: <strong><?php echo htmlspecialchars($user_data['username']); ?></strong></p>
                </div>

                <form method="POST" action="" id="resetForm">
                    <div>
                        <label for="new_password">
                            <i class="fas fa-lock"></i> Nova Senha:
                        </label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               required
                               minlength="6"
                               autocomplete="new-password">
                        
                        <div id="password-strength" class="password-strength" style="display: none;"></div>
                        
                        <div class="password-requirements">
                            <div class="requirement" id="req-length">
                                <i class="fas fa-circle"></i> Pelo menos 6 caracteres
                            </div>
                            <div class="requirement" id="req-lower">
                                <i class="fas fa-circle"></i> Letras minúsculas (a-z)
                            </div>
                            <div class="requirement" id="req-upper">
                                <i class="fas fa-circle"></i> Letras maiúsculas (A-Z)
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-circle"></i> Números (0-9)
                            </div>
                            <div class="requirement" id="req-special">
                                <i class="fas fa-circle"></i> Símbolos (!@#$%^&*)
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirmar Nova Senha:
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required
                               minlength="6"
                               autocomplete="new-password">
                        <div id="password-match" style="margin-top: 8px; font-size: 0.9rem;"></div>
                    </div>
                    
                    <button type="submit" id="submitBtn" disabled>
                        <i class="fas fa-save"></i> REDEFINIR SENHA
                    </button>
                </form>
            <?php else: ?>
                <div class="recovery-options">
                    <p>Problemas com o link de recuperação?</p>
                    <a href="forgot_password.php" class="action-button">
                        <i class="fas fa-redo"></i> Solicitar Novo Link
                    </a>
                </div>
            <?php endif; ?>
            
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Login
            </a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const strengthDiv = document.getElementById('password-strength');
            const matchDiv = document.getElementById('password-match');
            const submitBtn = document.getElementById('submitBtn');
            
            function checkRequirement(id, condition) {
                const element = document.getElementById(id);
                if (condition) {
                    element.classList.add('met');
                    element.classList.remove('unmet');
                    element.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    element.classList.remove('met');
                    element.classList.add('unmet');
                    element.querySelector('i').className = 'fas fa-circle';
                }
            }
            
            function checkPasswordStrength(password) {
                const requirements = {
                    length: password.length >= 6,
                    lower: /[a-z]/.test(password),
                    upper: /[A-Z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^a-zA-Z0-9]/.test(password)
                };
                
                checkRequirement('req-length', requirements.length);
                checkRequirement('req-lower', requirements.lower);
                checkRequirement('req-upper', requirements.upper);
                checkRequirement('req-number', requirements.number);
                checkRequirement('req-special', requirements.special);
                
                const score = Object.values(requirements).filter(Boolean).length;
                
                let strengthText = '';
                let strengthClass = '';
                
                if (password.length === 0) {
                    strengthDiv.style.display = 'none';
                    return false;
                } else {
                    strengthDiv.style.display = 'block';
                }
                
                if (score < 3) {
                    strengthText = '🔴 Senha fraca - Adicione mais elementos';
                    strengthClass = 'strength-weak';
                } else if (score < 5) {
                    strengthText = '🟡 Senha média - Boa, mas pode melhorar';
                    strengthClass = 'strength-medium';
                } else {
                    strengthText = '🟢 Senha forte - Excelente!';
                    strengthClass = 'strength-strong';
                }
                
                strengthDiv.textContent = strengthText;
                strengthDiv.className = 'password-strength ' + strengthClass;
                
                return score >= 3 && requirements.length;
            }
            
            function checkPasswordMatch() {
                const password = newPassword.value;
                const confirm = confirmPassword.value;
                
                if (confirm.length === 0) {
                    matchDiv.textContent = '';
                    return false;
                }
                
                if (password === confirm) {
                    matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #00b894;"></i> Senhas coincidem';
                    return true;
                } else {
                    matchDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #d63031;"></i> Senhas não coincidem';
                    return false;
                }
            }
            
            function updateSubmitButton() {
                const isStrong = checkPasswordStrength(newPassword.value);
                const isMatching = checkPasswordMatch();
                
                if (isStrong && isMatching && newPassword.value.length >= 6) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                }
            }
            
            newPassword.addEventListener('input', updateSubmitButton);
            confirmPassword.addEventListener('input', updateSubmitButton);
            
            // Validação em tempo real
            newPassword.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
            
            confirmPassword.addEventListener('input', function() {
                checkPasswordMatch();
            });
        });
    </script>
</body>
</html>