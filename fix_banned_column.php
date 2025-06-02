<?php
require_once 'includes/db_connect.php';

// Configurar saída HTML
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Correção da Coluna is_banned</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .error { color: #a94442; background: #f2dede; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .warning { color: #8a6d3b; background: #fcf8e3; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button, .btn { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Correção da Coluna is_banned</h1>";

// Função para registrar mensagens e exibir na interface
function log_message($message, $type = 'success') {
    echo "<div class='{$type}'>{$message}</div>";
}

// Verificar se a coluna is_banned existe na tabela users
log_message("Verificando se a coluna is_banned existe na tabela users...", "warning");

$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                 WHERE table_name='users' AND column_name='is_banned'");
$column_exists = pg_num_rows($check_column) > 0;

if (!$column_exists) {
    log_message("A coluna is_banned NÃO existe na tabela users. Adicionando coluna...", "warning");
    
    $add_column = pg_query($dbconn, "ALTER TABLE users ADD COLUMN is_banned BOOLEAN DEFAULT FALSE");
    
    if ($add_column) {
        log_message("Coluna is_banned adicionada com sucesso à tabela users.");
    } else {
        log_message("ERRO ao adicionar coluna is_banned: " . pg_last_error($dbconn), "error");
    }
} else {
    log_message("A coluna is_banned já existe na tabela users.");
}

// Verificar também outras colunas importantes
$columns_to_check = [
    'is_admin' => 'BOOLEAN DEFAULT FALSE',
    'is_active' => 'BOOLEAN DEFAULT TRUE'
];

foreach ($columns_to_check as $column_name => $column_type) {
    // Verificar se a coluna existe
    $check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                     WHERE table_name='users' AND column_name='$column_name'");
    $column_exists = pg_num_rows($check_column) > 0;
    
    if (!$column_exists) {
        log_message("A coluna $column_name NÃO existe na tabela users. Adicionando coluna...", "warning");
        
        $add_column = pg_query($dbconn, "ALTER TABLE users ADD COLUMN $column_name $column_type");
        
        if ($add_column) {
            log_message("Coluna $column_name adicionada com sucesso à tabela users.");
        } else {
            log_message("ERRO ao adicionar coluna $column_name: " . pg_last_error($dbconn), "error");
        }
    } else {
        log_message("A coluna $column_name já existe na tabela users.");
    }
}

// Verificar se o arquivo auth.php tem a função is_admin()
$auth_file = 'includes/auth.php';
if (file_exists($auth_file)) {
    $auth_content = file_get_contents($auth_file);
    
    if (strpos($auth_content, 'function is_admin(') === false) {
        log_message("Função is_admin() não encontrada em includes/auth.php. Adicionando...", "warning");
        
        // Adicionar a função is_admin() ao arquivo auth.php
        $auth_content .= "\n\n// Função para verificar se o usuário é administrador\nfunction is_admin() {\n    return isset(\$_SESSION['is_admin']) && \$_SESSION['is_admin'];\n}\n";
        
        if (file_put_contents($auth_file, $auth_content)) {
            log_message("Função is_admin() adicionada ao arquivo auth.php.");
        } else {
            log_message("ERRO ao adicionar função is_admin() ao arquivo auth.php.", "error");
            log_message("Por favor, adicione manualmente a seguinte função ao arquivo includes/auth.php:", "warning");
            echo "<pre>
// Função para verificar se o usuário é administrador
function is_admin() {
    return isset(\$_SESSION['is_admin']) && \$_SESSION['is_admin'];
}
</pre>";
        }
    } else {
        log_message("Função is_admin() já existe no arquivo auth.php.");
    }
} else {
    log_message("ERRO: Arquivo auth.php não encontrado em includes/", "error");
}

// Conclusão
echo "<h2>Resumo das ações realizadas:</h2>";
echo "<ol>";
echo "<li>Verificou se a coluna <code>is_banned</code> existe na tabela users</li>";
echo "<li>Adicionou a coluna <code>is_banned</code> se não existia</li>";
echo "<li>Verificou e adicionou outras colunas importantes (is_admin, is_active)</li>";
echo "<li>Verificou e adicionou a função is_admin() ao arquivo auth.php</li>";
echo "</ol>";

echo "<h2>Próximos passos:</h2>";
echo "<ol>";
echo "<li>Acesse novamente o painel de administração para verificar se o erro foi resolvido</li>";
echo "<li>Se o erro persistir, execute o arquivo setup_db.php novamente</li>";
echo "<li>Faça logout e login novamente para atualizar sua sessão</li>";
echo "</ol>";

echo "<div style='margin-top: 30px;'>
    <a href='index.php' class='btn'>Ir para a Página Inicial</a>
    <a href='admin/users.php' class='btn' style='margin-left: 10px; background-color: #2196F3;'>Ir para o Gerenciamento de Usuários</a>
</div>
</body>
</html>";
?>