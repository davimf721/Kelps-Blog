<?php
require_once 'includes/db_connect.php';

// Iniciar sessão para poder atualizar status de admin se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar saída HTML
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuração do Banco de Dados - Kelps Blog</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .error { color: #a94442; background: #f2dede; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .warning { color: #8a6d3b; background: #fcf8e3; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        button, .btn { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Configuração do Banco de Dados - Kelps Blog</h1>";

// Função para registrar mensagens e exibir na interface
function log_message($message, $type = 'success') {
    echo "<div class='{$type}'>{$message}</div>";
}

// PARTE 1: CRIAÇÃO DAS TABELAS BÁSICAS

// Criar tabela users
$create_users_table = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(255),
    token_expires TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Criar tabela posts
$create_posts_table = "
CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    upvotes_count INTEGER DEFAULT 0
)";

// Criar tabela comments
$create_comments_table = "
CREATE TABLE IF NOT EXISTS comments (
    id SERIAL PRIMARY KEY,
    post_id INTEGER REFERENCES posts(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id),
    content TEXT NOT NULL,
    parent_id INTEGER NULL REFERENCES comments(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Criar tabela user_profiles
$create_user_profiles_table = "
CREATE TABLE IF NOT EXISTS user_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) UNIQUE,
    profile_image TEXT DEFAULT 'images/default-profile.png',
    banner_image TEXT DEFAULT 'images/default-banner.png',
    bio TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Executar queries de criação
$tables = [
    'users' => $create_users_table,
    'posts' => $create_posts_table,
    'comments' => $create_comments_table,
    'user_profiles' => $create_user_profiles_table
];

echo "<h2>Criação de Tabelas</h2>";
$success = true;
foreach ($tables as $table_name => $query) {
    $result = pg_query($dbconn, $query);
    if (!$result) {
        log_message("Erro ao criar tabela {$table_name}: " . pg_last_error($dbconn), 'error');
        $success = false;
    } else {
        log_message("Tabela {$table_name} criada ou já existente.");
    }
}

// PARTE 2: VERIFICAR E ADICIONAR COLUNAS EM TABELAS EXISTENTES

echo "<h2>Verificação e Atualização de Colunas</h2>";

// Definir as colunas que precisamos verificar na tabela users
$required_columns = [
    'is_admin' => 'BOOLEAN DEFAULT FALSE',
    'is_banned' => 'BOOLEAN DEFAULT FALSE',
    'is_active' => 'BOOLEAN DEFAULT TRUE',
    'remember_token' => 'VARCHAR(255)',
    'token_expires' => 'TIMESTAMP'
];

// Verificar cada coluna necessária e adicionar se não existir
foreach ($required_columns as $column_name => $column_type) {
    $check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                     WHERE table_name='users' AND column_name='$column_name'");
    if (pg_num_rows($check_column) == 0) {
        $add_column = pg_query($dbconn, "ALTER TABLE users ADD COLUMN $column_name $column_type");
        if ($add_column) {
            log_message("Coluna '$column_name' adicionada à tabela users.");
        } else {
            log_message("Erro ao adicionar coluna '$column_name': " . pg_last_error($dbconn), 'error');
        }
    } else {
        log_message("Coluna '$column_name' já existe na tabela users.");
    }
}

// Verificar colunas na tabela user_profiles
$check_profile_image = pg_query($dbconn, "SELECT data_type FROM information_schema.columns 
                                       WHERE table_name='user_profiles' AND column_name='profile_image'");

if (pg_num_rows($check_profile_image) > 0) {
    $data_type = pg_fetch_result($check_profile_image, 0, 0);
    if (strtolower($data_type) !== 'text') {
        $alter_column = pg_query($dbconn, "ALTER TABLE user_profiles ALTER COLUMN profile_image TYPE TEXT");
        if ($alter_column) {
            log_message("Coluna 'profile_image' alterada para tipo TEXT.");
        } else {
            log_message("Erro ao alterar tipo da coluna 'profile_image': " . pg_last_error($dbconn), 'error');
        }
    }
}

$check_banner_image = pg_query($dbconn, "SELECT data_type FROM information_schema.columns 
                                      WHERE table_name='user_profiles' AND column_name='banner_image'");

if (pg_num_rows($check_banner_image) > 0) {
    $data_type = pg_fetch_result($check_banner_image, 0, 0);
    if (strtolower($data_type) !== 'text') {
        $alter_column = pg_query($dbconn, "ALTER TABLE user_profiles ALTER COLUMN banner_image TYPE TEXT");
        if ($alter_column) {
            log_message("Coluna 'banner_image' alterada para tipo TEXT.");
        } else {
            log_message("Erro ao alterar tipo da coluna 'banner_image': " . pg_last_error($dbconn), 'error');
        }
    }
}

// Verificar se a tabela comments tem a coluna parent_id
$check_parent_id = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                    WHERE table_name='comments' AND column_name='parent_id'");

if (pg_num_rows($check_parent_id) == 0) {
    $add_parent_id = pg_query($dbconn, "ALTER TABLE comments ADD COLUMN parent_id INTEGER REFERENCES comments(id) ON DELETE CASCADE");
    if ($add_parent_id) {
        log_message("Coluna 'parent_id' adicionada à tabela comments.");
    } else {
        log_message("Erro ao adicionar coluna 'parent_id': " . pg_last_error($dbconn), 'error');
    }
}

// PARTE 3: CONFIGURAÇÃO DO USUÁRIO ADMINISTRADOR

echo "<h2>Configuração de Usuário Administrador</h2>";

// Verificar se existe usuário 'ghoul' ou com email 'davimoreiraf@gmail.com'
$check_user = pg_query($dbconn, "SELECT id FROM users WHERE username = 'ghoul' OR username = 'Ghoul' OR email = 'davimoreiraf@gmail.com' OR id = 1");

if (pg_num_rows($check_user) > 0) {
    $user_id = pg_fetch_result($check_user, 0, 0);
    $update_admin = pg_query($dbconn, "UPDATE users SET is_admin = TRUE WHERE id = $user_id");
    
    if ($update_admin) {
        log_message("Usuário ID $user_id configurado como administrador.");
        
        // Atualizar sessão atual se for o mesmo usuário
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            $_SESSION['is_admin'] = true;
            log_message("Sessão atual atualizada com privilégios de administrador.");
        }
    } else {
        log_message("Erro ao configurar usuário como administrador: " . pg_last_error($dbconn), 'error');
    }
} else {
    // Criar usuário administrador se não existir
    log_message("Usuário 'ghoul' não encontrado. Criando usuário administrador...", 'warning');
    
    $password_hash = password_hash('senha123', PASSWORD_DEFAULT);
    $insert_admin = pg_query($dbconn, "INSERT INTO users (username, email, password_hash, is_admin) 
                                     VALUES ('Ghoul', 'davimoreiraf@gmail.com', '$password_hash', TRUE)");
    
    if ($insert_admin) {
        log_message("Novo usuário administrador 'Ghoul' criado com sucesso.");
        log_message("Detalhes de acesso: Username: Ghoul / Senha: senha123", 'warning');
    } else {
        log_message("Erro ao criar usuário administrador: " . pg_last_error($dbconn), 'error');
    }
}

// PARTE 4: ATUALIZAÇÃO DO AUTH.PHP PARA INCLUIR FUNÇÃO IS_ADMIN

echo "<h2>Verificação do arquivo auth.php</h2>";

$auth_file = 'includes/auth.php';
if (file_exists($auth_file)) {
    $auth_content = file_get_contents($auth_file);
    
    if (strpos($auth_content, 'function is_admin(') === false) {
        if (is_writable($auth_file)) {
            $auth_content .= "\n\n// Função para verificar se o usuário é administrador\nfunction is_admin() {\n    return isset(\$_SESSION['is_admin']) && \$_SESSION['is_admin'];\n}\n";
            
            if (file_put_contents($auth_file, $auth_content)) {
                log_message("Função is_admin() adicionada ao arquivo auth.php.");
            } else {
                log_message("Erro ao adicionar função is_admin() ao arquivo auth.php. Permissões de escrita insuficientes.", 'error');
            }
        } else {
            log_message("O arquivo auth.php não pode ser modificado automaticamente.", 'warning');
            log_message("Por favor, adicione manualmente a seguinte função ao arquivo includes/auth.php:", 'warning');
            echo "<pre style='background:#f8f9fa; padding:10px; border-radius:4px;'>
// Função para verificar se o usuário é administrador
function is_admin() {
    return isset(\$_SESSION['is_admin']) && \$_SESSION['is_admin'];
}
</pre>";
        }
    } else {
        log_message("A função is_admin() já existe no arquivo auth.php.");
    }
} else {
    log_message("Arquivo auth.php não encontrado em includes/. Certifique-se de que o arquivo existe.", 'error');
}

// PARTE 5: CORREÇÃO DA CONSULTA NO POST.PHP

echo "<h2>Verificação do arquivo post.php</h2>";

$post_file = 'post.php';
if (file_exists($post_file) && is_readable($post_file)) {
    $post_content = file_get_contents($post_file);
    
    // Localizar e substituir o CASE WHEN por COALESCE para evitar erros
    $pattern = '/CASE\s+WHEN\s+u\.is_admin\s+IS\s+NULL\s+THEN\s+FALSE\s+ELSE\s+u\.is_admin\s+END\s+as\s+is_admin/i';
    
    if (preg_match($pattern, $post_content)) {
        $modified_content = preg_replace($pattern, 'COALESCE(u.is_admin, FALSE) as is_admin', $post_content);
        
        if (is_writable($post_file)) {
            if (file_put_contents($post_file, $modified_content)) {
                log_message("Arquivo post.php atualizado com sucesso para usar COALESCE.");
            } else {
                log_message("Erro ao atualizar o arquivo post.php.", 'error');
            }
        } else {
            log_message("O arquivo post.php não pode ser modificado automaticamente.", 'warning');
            log_message("Por favor, substitua manualmente o código CASE WHEN por COALESCE no arquivo post.php.", 'warning');
        }
    } else {
        log_message("Não foi encontrado o padrão CASE WHEN no arquivo post.php ou ele já foi corrigido anteriormente.");
    }
} else {
    log_message("Arquivo post.php não encontrado ou não pode ser lido.", 'error');
}

// PARTE 6: DADOS DE EXEMPLO

echo "<h2>Criação de Dados de Exemplo</h2>";

// Inserir dados de exemplo se solicitado
if ($success) {
    // Verificar se já existe um post
    $check_posts = pg_query($dbconn, "SELECT COUNT(*) FROM posts");
    $posts_count = pg_fetch_result($check_posts, 0, 0);
    
    if ($posts_count == 0) {
        // Buscar ID do usuário ghoul
        $get_ghoul = pg_query($dbconn, "SELECT id FROM users WHERE username = 'ghoul' OR username = 'Ghoul' OR email = 'davimoreiraf@gmail.com' LIMIT 1");
        
        if (pg_num_rows($get_ghoul) > 0) {
            $user_id = pg_fetch_result($get_ghoul, 0, 0);
            
            // Inserir post de exemplo
            $post_title = "Bem-vindo ao Kelps Blog!";
            $post_content = "Este é o primeiro post do blog! Aqui você encontrará uma variedade de conteúdos interessantes.";
            
            $insert_post = pg_query($dbconn, "INSERT INTO posts (user_id, title, content) 
                                           VALUES ($user_id, '$post_title', '$post_content')
                                           RETURNING id");
            
            if ($insert_post) {
                $post_id = pg_fetch_result($insert_post, 0, 0);
                log_message("Post de exemplo criado com sucesso (ID: $post_id).");
                
                // Inserir comentário de exemplo
                $comment_text = "Primeiro comentário no blog! Espero que gostem do conteúdo.";
                $insert_comment = pg_query($dbconn, "INSERT INTO comments (post_id, user_id, content) 
                                                 VALUES ($post_id, $user_id, '$comment_text')");
                
                if ($insert_comment) {
                    log_message("Comentário de exemplo criado com sucesso.");
                }
            } else {
                log_message("Erro ao criar post de exemplo: " . pg_last_error($dbconn), 'error');
            }
        }
    } else {
        log_message("Já existem posts no banco de dados. Nenhum dado de exemplo será criado.");
    }
}

// Finalização
echo "<div style='margin-top: 30px;'>
    <h2>Configuração Concluída!</h2>
    <p>O banco de dados foi configurado corretamente. Agora você pode usar todas as funcionalidades do Kelps Blog.</p>
    <div style='margin-top: 20px;'>
        <a href='index.php' class='btn'>Ir para a Página Inicial</a>
        <a href='login.php' class='btn' style='margin-left: 10px;'>Fazer Login</a>
    </div>
</div>
</body>
</html>";
?>