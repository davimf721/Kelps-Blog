<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
if (!is_logged_in()) {
    $_SESSION['error'] = "Você precisa estar logado para acessar esta página.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$message = '';
$message_type = '';
$show_confirmation = false;

// Buscar estatísticas do usuário
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = $user_id) as posts_count,
        (SELECT COUNT(*) FROM comments WHERE user_id = $user_id) as comments_count,
        (SELECT COUNT(*) FROM followers WHERE follower_id = $user_id) as following_count,
        (SELECT COUNT(*) FROM followers WHERE following_id = $user_id) as followers_count,
        (SELECT SUM(upvotes_count) FROM posts WHERE user_id = $user_id) as total_upvotes
";

// Executar query com verificação de erro
$stats_result = pg_query($dbconn, $stats_query);
if (!$stats_result) {
    // Se a query falhar, usar valores padrão
    $stats = [
        'posts_count' => 0,
        'comments_count' => 0,
        'following_count' => 0,
        'followers_count' => 0,
        'total_upvotes' => 0
    ];
} else {
    $stats = pg_fetch_assoc($stats_result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_deletion') {
        // Primeira etapa: verificar senha e mostrar confirmação
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $message = "Por favor, digite sua senha para continuar.";
            $message_type = 'error';
        } else {
            // Verificar senha
            $password_query = "SELECT password_hash FROM users WHERE id = $user_id";
            $password_result = pg_query($dbconn, $password_query);
            $user_data = pg_fetch_assoc($password_result);
            
            if (password_verify($password, $user_data['password_hash'])) {
                $show_confirmation = true;
                $message = "Senha verificada. Por favor, confirme a exclusão da sua conta.";
                $message_type = 'warning';
            } else {
                $message = "Senha incorreta. Tente novamente.";
                $message_type = 'error';
            }
        }
    } elseif ($action === 'confirm_deletion') {
        // Segunda etapa: confirmar exclusão
        $confirmation = $_POST['confirmation'] ?? '';
        $final_password = $_POST['final_password'] ?? '';
        
        if ($confirmation !== 'EXCLUIR MINHA CONTA') {
            $message = "Por favor, digite exatamente 'EXCLUIR MINHA CONTA' para confirmar.";
            $message_type = 'error';
            $show_confirmation = true;
        } elseif (empty($final_password)) {
            $message = "Por favor, digite sua senha novamente para confirmar.";
            $message_type = 'error';
            $show_confirmation = true;
        } else {
            // Verificar senha novamente
            $password_query = "SELECT password_hash FROM users WHERE id = $user_id";
            $password_result = pg_query($dbconn, $password_query);
            $user_data = pg_fetch_assoc($password_result);
            
            if (password_verify($final_password, $user_data['password_hash'])) {
                // EXECUTAR EXCLUSÃO DA CONTA
                $deletion_result = delete_user_account($dbconn, $user_id, $username);
                
                if ($deletion_result['success']) {
                    // Salvar informações para a página de confirmação
                    $_SESSION['deleted_username'] = $username;
                    $_SESSION['deletion_time'] = date('d/m/Y H:i:s');
                    
                    // Destruir sessão
                    session_destroy();
                    
                    // Redirecionar para página de confirmação
                    header("Location: delete_account_confirmation.php");
                    exit();
                } else {
                    $message = "Erro ao excluir conta: " . $deletion_result['message'];
                    $message_type = 'error';
                }
            } else {
                $message = "Senha incorreta. Exclusão cancelada.";
                $message_type = 'error';
                $show_confirmation = true;
            }
        }
    }
}

// Função melhorada para excluir conta do usuário
function delete_user_account($dbconn, $user_id, $username) {
    try {
        // Iniciar transação
        pg_query($dbconn, "BEGIN");
        
        // 1. Primeiro, verificar quais tabelas existem
        $existing_tables = [];
        $tables_to_check = ['user_logs', 'followers', 'upvotes', 'notifications', 'comments', 'posts', 'user_profiles'];
        
        foreach ($tables_to_check as $table) {
            $check_table = pg_query($dbconn, "SELECT to_regclass('public.$table')");
            if ($check_table && pg_fetch_result($check_table, 0, 0) !== null) {
                $existing_tables[] = $table;
            }
        }
        
        // 2. Log da exclusão (se a tabela user_logs existir)
        if (in_array('user_logs', $existing_tables)) {
            $log_query = "INSERT INTO user_logs (user_id, action, ip_address, user_agent, created_at) 
                         VALUES ($user_id, 'account_deleted', $1, $2, NOW())";
            $log_result = pg_query_params($dbconn, $log_query, [
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            if (!$log_result) {
                error_log("Erro ao inserir log: " . pg_last_error($dbconn));
            }
        }
        
        // 3. Deletar relacionamentos em ordem segura
        $deletion_queries = [];
        
        // Deletar seguidores/seguindo (se a tabela existir)
        if (in_array('followers', $existing_tables)) {
            $deletion_queries[] = "DELETE FROM followers WHERE follower_id = $user_id OR following_id = $user_id";
        }
        
        // Deletar upvotes do usuário (se a tabela existir)
        if (in_array('upvotes', $existing_tables)) {
            $deletion_queries[] = "DELETE FROM upvotes WHERE user_id = $user_id";
        }
        
        // Deletar notificações (se a tabela existir)
        if (in_array('notifications', $existing_tables)) {
            // Verificar se a coluna from_user_id existe
            $check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                             WHERE table_name='notifications' AND column_name='from_user_id'");
            if ($check_column && pg_num_rows($check_column) > 0) {
                $deletion_queries[] = "DELETE FROM notifications WHERE user_id = $user_id OR from_user_id = $user_id";
            } else {
                $deletion_queries[] = "DELETE FROM notifications WHERE user_id = $user_id";
            }
        }
        
        // Deletar comentários (se a tabela existir)
        if (in_array('comments', $existing_tables)) {
            $deletion_queries[] = "DELETE FROM comments WHERE user_id = $user_id";
        }
        
        // Deletar posts (se a tabela existir)
        if (in_array('posts', $existing_tables)) {
            $deletion_queries[] = "DELETE FROM posts WHERE user_id = $user_id";
        }
        
        // Deletar perfil (se a tabela existir)
        if (in_array('user_profiles', $existing_tables)) {
            $deletion_queries[] = "DELETE FROM user_profiles WHERE user_id = $user_id";
        }
        
        // Executar todas as queries de exclusão
        foreach ($deletion_queries as $query) {
            $result = pg_query($dbconn, $query);
            if (!$result) {
                throw new Exception("Erro ao executar: $query - " . pg_last_error($dbconn));
            }
        }
        
        // 4. Deletar usuário (deve ser por último)
        $delete_user = pg_query($dbconn, "DELETE FROM users WHERE id = $user_id");
        
        if (!$delete_user) {
            throw new Exception("Erro ao deletar usuário: " . pg_last_error($dbconn));
        }
        
        // Confirmar transação
        pg_query($dbconn, "COMMIT");
        
        return ['success' => true, 'message' => 'Conta excluída com sucesso'];
        
    } catch (Exception $e) {
        // Reverter transação
        pg_query($dbconn, "ROLLBACK");
        
        // Log do erro
        error_log("Erro na exclusão de conta: " . $e->getMessage());
        
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Definir variáveis para o header
$page_title = "Excluir Conta - Kelps Blog";
$current_page = 'delete_account';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .delete-account-container {
            max-width: 700px;
            margin: 30px auto;
            padding: 30px;
            background-color: #2a2a2a;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .warning-box {
            background: rgba(255, 193, 7, 0.15);
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .warning-box h3 {
            color: #ffc107;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0e86ca;
            display: block;
        }
        
        .stat-label {
            font-size: 14px;
            color: #ccc;
            margin-top: 5px;
        }
        
        .deletion-form {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .confirmation-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #dc3545;
            border-radius: 6px;
            background-color: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 16px;
            margin: 15px 0;
            box-sizing: border-box;
        }
        
        .confirmation-input:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
        }
        
        .delete-button {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
        }
        
        .delete-button:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        
        .delete-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .cancel-button {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 10px 10px 0 0;
            transition: all 0.3s ease;
        }
        
        .cancel-button:hover {
            background: #5a6268;
            text-decoration: none;
        }
        
        .consequences-list {
            list-style: none;
            padding: 0;
        }
        
        .consequences-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 12px 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
        }
        
        .consequences-list i {
            color: #dc3545;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .delete-account-container {
                margin: 15px;
                padding: 20px;
                width: calc(100% - 30px);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-number {
                font-size: 20px;
            }
        }
    </style>
</head>
<body class="auth-page">
    <header>
        <h1><i class="fas fa-user-times"></i> Excluir Conta</h1>
    </header>

    <main class="auth-main">
        <div class="delete-account-container">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <p>
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : ($message_type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?>"></i> 
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="warning-box">
                <h3><i class="fas fa-exclamation-triangle"></i> ATENÇÃO: Ação Irreversível</h3>
                <p>A exclusão da sua conta é <strong>permanente e irreversível</strong>. Todos os seus dados serão perdidos para sempre.</p>
            </div>

            <h2>Suas Estatísticas no Kelps Blog</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['posts_count'] ?? 0; ?></span>
                    <div class="stat-label">Posts Criados</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['comments_count'] ?? 0; ?></span>
                    <div class="stat-label">Comentários</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['followers_count'] ?? 0; ?></span>
                    <div class="stat-label">Seguidores</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['following_count'] ?? 0; ?></span>
                    <div class="stat-label">Seguindo</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_upvotes'] ?? 0; ?></span>
                    <div class="stat-label">Upvotes Recebidos</div>
                </div>
            </div>

            <div class="danger-zone">
                <h3><i class="fas fa-skull-crossbones"></i> O que será excluído permanentemente:</h3>
                <ul class="consequences-list">
                    <li>
                        <i class="fas fa-user-slash"></i>
                        <div>
                            <strong>Sua conta e perfil</strong><br>
                            <small>Username, email, senha, bio, fotos de perfil e banner</small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <strong>Todos os seus posts (<?php echo $stats['posts_count'] ?? 0; ?>)</strong><br>
                            <small>Títulos, conteúdo, upvotes e todos os comentários relacionados</small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-comments"></i>
                        <div>
                            <strong>Todos os seus comentários (<?php echo $stats['comments_count'] ?? 0; ?>)</strong><br>
                            <small>Incluindo respostas a outros posts</small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-heart-broken"></i>
                        <div>
                            <strong>Conexões sociais</strong><br>
                            <small>Lista de seguidores e pessoas que você segue</small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-bell-slash"></i>
                        <div>
                            <strong>Notificações e interações</strong><br>
                            <small>Histórico de atividades, upvotes dados e recebidos</small>
                        </div>
                    </li>
                </ul>
            </div>

            <?php if (!$show_confirmation): ?>
                <!-- Primeira etapa: Verificação de senha -->
                <form method="POST" class="deletion-form" id="deleteForm">
                    <h3><i class="fas fa-key"></i> Para continuar, confirme sua senha:</h3>
                    <input type="hidden" name="action" value="request_deletion">
                    
                    <label for="password">Senha atual:</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="confirmation-input"
                           required 
                           autocomplete="current-password"
                           placeholder="Digite sua senha atual">
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="delete-button">
                            <i class="fas fa-arrow-right"></i> CONTINUAR COM A EXCLUSÃO
                        </button>
                        <a href="profile.php" class="cancel-button">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <!-- Segunda etapa: Confirmação final -->
                <form method="POST" class="deletion-form" id="confirmDeleteForm">
                    <h3><i class="fas fa-exclamation-triangle"></i> Confirmação Final</h3>
                    <p>Para confirmar que você realmente deseja excluir sua conta <strong><?php echo htmlspecialchars($username); ?></strong>, digite exatamente:</p>
                    
                    <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 6px; margin: 15px 0; text-align: center; font-family: monospace; font-size: 18px; color: #dc3545; font-weight: bold;">
                        EXCLUIR MINHA CONTA
                    </div>
                    
                    <input type="hidden" name="action" value="confirm_deletion">
                    
                    <label for="confirmation">Digite a frase acima:</label>
                    <input type="text" 
                           id="confirmation" 
                           name="confirmation" 
                           class="confirmation-input"
                           required 
                           placeholder="EXCLUIR MINHA CONTA"
                           autocomplete="off">
                    
                    <label for="final_password">Confirme sua senha novamente:</label>
                    <input type="password" 
                           id="final_password" 
                           name="final_password" 
                           class="confirmation-input"
                           required 
                           autocomplete="current-password"
                           placeholder="Digite sua senha novamente">
                    
                    <div style="margin-top: 25px;">
                        <button type="submit" class="delete-button" id="finalDeleteBtn" disabled>
                            <i class="fas fa-trash-alt"></i> EXCLUIR MINHA CONTA PERMANENTEMENTE
                        </button>
                        <a href="profile.php" class="cancel-button">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #444;">
                <p style="color: #888; font-size: 14px;">
                    <i class="fas fa-shield-alt"></i> 
                    Sua privacidade é importante. Seus dados serão completamente removidos de nossos servidores.
                </p>
                <a href="profile.php" style="color: #0e86ca; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Perfil
                </a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script para a confirmação final
            const confirmForm = document.getElementById('confirmDeleteForm');
            if (confirmForm) {
                const confirmationInput = document.getElementById('confirmation');
                const passwordInput = document.getElementById('final_password');
                const deleteBtn = document.getElementById('finalDeleteBtn');
                
                function checkForm() {
                    const confirmationCorrect = confirmationInput.value === 'EXCLUIR MINHA CONTA';
                    const passwordFilled = passwordInput.value.length > 0;
                    
                    deleteBtn.disabled = !(confirmationCorrect && passwordFilled);
                    
                    if (confirmationCorrect) {
                        confirmationInput.style.borderColor = '#28a745';
                    } else {
                        confirmationInput.style.borderColor = '#dc3545';
                    }
                }
                
                confirmationInput.addEventListener('input', checkForm);
                passwordInput.addEventListener('input', checkForm);
                
                // Confirmação adicional antes do envio
                confirmForm.addEventListener('submit', function(e) {
                    const confirmed = confirm(
                        'ÚLTIMA CONFIRMAÇÃO!\n\n' +
                        'Você tem certeza ABSOLUTA de que deseja excluir sua conta?\n\n' +
                        'Esta ação NÃO PODE ser desfeita!\n\n' +
                        'Clique em "OK" para excluir permanentemente ou "Cancelar" para voltar.'
                    );
                    
                    if (!confirmed) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Desabilitar botão para evitar cliques duplos
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> EXCLUINDO CONTA...';
                });
            }
            
            // Script para o formulário de verificação de senha
            const deleteForm = document.getElementById('deleteForm');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    const confirmed = confirm(
                        'Você está prestes a iniciar o processo de exclusão da sua conta.\n\n' +
                        'Tem certeza de que deseja continuar?'
                    );
                    
                    if (!confirmed) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>