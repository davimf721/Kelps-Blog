<?php
require_once 'includes/db_connect.php';

// Configurar saída HTML
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuração Sistema de Notificações - Kelps Blog</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #2b2b2b; color: white; }
        .success { color: #4CAF50; background: #1b5e20; padding: 10px; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #4CAF50; }
        .error { color: #f44336; background: #b71c1c; padding: 10px; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #f44336; }
        .warning { color: #ff9800; background: #e65100; padding: 10px; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #ff9800; }
        .info { color: #2196F3; background: #0d47a1; padding: 10px; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #2196F3; }
        .btn { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #45a049; }
        pre { background: #1e1e1e; padding: 15px; border-radius: 4px; overflow-x: auto; }
        h1, h2 { color: #4CAF50; }
    </style>
</head>
<body>
    <h1>🔔 Configuração do Sistema de Notificações</h1>";

function log_message($message, $type = 'success') {
    echo "<div class='{$type}'>{$message}</div>";
}

// ETAPA 1: Criar tabela user_follows
echo "<h2>📋 Etapa 1: Criando tabela de seguidores</h2>";

$create_user_follows = "
CREATE TABLE IF NOT EXISTS user_follows (
    id SERIAL PRIMARY KEY,
    follower_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    followed_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(follower_id, followed_id),
    CHECK (follower_id != followed_id)
)";

$result = pg_query($dbconn, $create_user_follows);
if ($result) {
    log_message("✅ Tabela user_follows criada com sucesso!");
} else {
    log_message("❌ Erro ao criar tabela user_follows: " . pg_last_error($dbconn), 'error');
}

// Criar índices
$create_indexes = [
    "CREATE INDEX IF NOT EXISTS idx_user_follows_follower ON user_follows(follower_id)",
    "CREATE INDEX IF NOT EXISTS idx_user_follows_followed ON user_follows(followed_id)"
];

foreach ($create_indexes as $index_sql) {
    $result = pg_query($dbconn, $index_sql);
    if ($result) {
        log_message("✅ Índice criado com sucesso.");
    } else {
        log_message("❌ Erro ao criar índice: " . pg_last_error($dbconn), 'error');
    }
}

// ETAPA 2: Criar tabela notifications
echo "<h2>🔔 Etapa 2: Criando tabela de notificações</h2>";

$create_notifications = "
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    sender_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    reference_id INTEGER,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$result = pg_query($dbconn, $create_notifications);
if ($result) {
    log_message("✅ Tabela notifications criada com sucesso!");
} else {
    log_message("❌ Erro ao criar tabela notifications: " . pg_last_error($dbconn), 'error');
}

// Criar índices para notifications
$notification_indexes = [
    "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type)"
];

foreach ($notification_indexes as $index_sql) {
    $result = pg_query($dbconn, $index_sql);
    if ($result) {
        log_message("✅ Índice de notificações criado com sucesso.");
    } else {
        log_message("❌ Erro ao criar índice: " . pg_last_error($dbconn), 'error');
    }
}

// ETAPA 3: Criar tabela post_upvotes
echo "<h2>👍 Etapa 3: Criando tabela de upvotes</h2>";

$create_post_upvotes = "
CREATE TABLE IF NOT EXISTS post_upvotes (
    id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(post_id, user_id)
)";

$result = pg_query($dbconn, $create_post_upvotes);
if ($result) {
    log_message("✅ Tabela post_upvotes criada com sucesso!");
} else {
    log_message("❌ Erro ao criar tabela post_upvotes: " . pg_last_error($dbconn), 'error');
}

// Criar índices para upvotes
$upvote_indexes = [
    "CREATE INDEX IF NOT EXISTS idx_post_upvotes_post_id ON post_upvotes(post_id)",
    "CREATE INDEX IF NOT EXISTS idx_post_upvotes_user_id ON post_upvotes(user_id)"
];

foreach ($upvote_indexes as $index_sql) {
    $result = pg_query($dbconn, $index_sql);
    if ($result) {
        log_message("✅ Índice de upvotes criado com sucesso.");
    } else {
        log_message("❌ Erro ao criar índice: " . pg_last_error($dbconn), 'error');
    }
}

// ETAPA 4: Adicionar colunas necessárias
echo "<h2>📊 Etapa 4: Adicionando colunas necessárias</h2>";

// Verificar e adicionar coluna unread_notifications na tabela users
$check_unread_column = pg_query($dbconn, "
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name='users' AND column_name='unread_notifications'
");

if (pg_num_rows($check_unread_column) == 0) {
    $add_unread_column = pg_query($dbconn, "ALTER TABLE users ADD COLUMN unread_notifications INTEGER DEFAULT 0");
    if ($add_unread_column) {
        log_message("✅ Coluna unread_notifications adicionada à tabela users.");
    } else {
        log_message("❌ Erro ao adicionar coluna unread_notifications: " . pg_last_error($dbconn), 'error');
    }
} else {
    log_message("ℹ️ Coluna unread_notifications já existe na tabela users.", 'info');
}

// Verificar e adicionar coluna upvotes_count na tabela posts
$check_upvotes_column = pg_query($dbconn, "
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name='posts' AND column_name='upvotes_count'
");

if (pg_num_rows($check_upvotes_column) == 0) {
    $add_upvotes_column = pg_query($dbconn, "ALTER TABLE posts ADD COLUMN upvotes_count INTEGER DEFAULT 0");
    if ($add_upvotes_column) {
        log_message("✅ Coluna upvotes_count adicionada à tabela posts.");
    } else {
        log_message("❌ Erro ao adicionar coluna upvotes_count: " . pg_last_error($dbconn), 'error');
    }
} else {
    log_message("ℹ️ Coluna upvotes_count já existe na tabela posts.", 'info');
}

// ETAPA 5: Criar funções e triggers para atualizar contadores automaticamente
echo "<h2>⚙️ Etapa 5: Criando funções e triggers</h2>";

// Função para atualizar contador de upvotes
$create_upvote_function = "
CREATE OR REPLACE FUNCTION update_post_upvotes_count()
RETURNS TRIGGER AS \$\$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE posts SET upvotes_count = upvotes_count + 1 WHERE id = NEW.post_id;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE posts SET upvotes_count = upvotes_count - 1 WHERE id = OLD.post_id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
\$\$ LANGUAGE plpgsql;
";

$result = pg_query($dbconn, $create_upvote_function);
if ($result) {
    log_message("✅ Função update_post_upvotes_count() criada com sucesso!");
} else {
    log_message("❌ Erro ao criar função: " . pg_last_error($dbconn), 'error');
}

// Criar trigger para upvotes
$create_upvote_trigger = "
DROP TRIGGER IF EXISTS trigger_update_upvotes_count ON post_upvotes;
CREATE TRIGGER trigger_update_upvotes_count
    AFTER INSERT OR DELETE ON post_upvotes
    FOR EACH ROW
    EXECUTE FUNCTION update_post_upvotes_count();
";

$result = pg_query($dbconn, $create_upvote_trigger);
if ($result) {
    log_message("✅ Trigger para upvotes criado com sucesso!");
} else {
    log_message("❌ Erro ao criar trigger: " . pg_last_error($dbconn), 'error');
}

// ETAPA 6: Atualizar contadores existentes
echo "<h2>🔄 Etapa 6: Atualizando contadores existentes</h2>";

// Atualizar contador de upvotes para posts existentes
$update_upvotes = pg_query($dbconn, "
    UPDATE posts SET upvotes_count = (
        SELECT COUNT(*) FROM post_upvotes WHERE post_id = posts.id
    )
");

if ($update_upvotes) {
    $affected_rows = pg_affected_rows($update_upvotes);
    log_message("✅ Contadores de upvotes atualizados para {$affected_rows} posts.");
} else {
    log_message("❌ Erro ao atualizar contadores de upvotes: " . pg_last_error($dbconn), 'error');
}

// Atualizar contador de notificações para usuários existentes
$update_notifications = pg_query($dbconn, "
    UPDATE users SET unread_notifications = (
        SELECT COUNT(*) FROM notifications 
        WHERE user_id = users.id AND is_read = FALSE
    )
");

if ($update_notifications) {
    $affected_rows = pg_affected_rows($update_notifications);
    log_message("✅ Contadores de notificações atualizados para {$affected_rows} usuários.");
} else {
    log_message("❌ Erro ao atualizar contadores de notificações: " . pg_last_error($dbconn), 'error');
}

// ETAPA 7: Criar alguns dados de exemplo (opcional)
echo "<h2>🎯 Etapa 7: Criando dados de exemplo</h2>";

// Verificar se existem usuários para criar relacionamentos
$check_users = pg_query($dbconn, "SELECT COUNT(*) FROM users");
$user_count = pg_fetch_result($check_users, 0, 0);

if ($user_count >= 2) {
    // Buscar IDs dos primeiros usuários
    $get_users = pg_query($dbconn, "SELECT id FROM users ORDER BY id LIMIT 3");
    $user_ids = [];
    
    while ($row = pg_fetch_assoc($get_users)) {
        $user_ids[] = $row['id'];
    }
    
    if (count($user_ids) >= 2) {
        // Criar alguns relacionamentos de follow (apenas como exemplo)
        $follow_examples = [
            [$user_ids[0], $user_ids[1]], // usuário 1 segue usuário 2
        ];
        
        foreach ($follow_examples as $follow) {
            $follower_id = $follow[0];
            $followed_id = $follow[1];
            
            $check_follow = pg_query($dbconn, "
                SELECT id FROM user_follows 
                WHERE follower_id = $follower_id AND followed_id = $followed_id
            ");
            
            if (pg_num_rows($check_follow) == 0) {
                $insert_follow = pg_query($dbconn, "
                    INSERT INTO user_follows (follower_id, followed_id) 
                    VALUES ($follower_id, $followed_id)
                ");
                
                if ($insert_follow) {
                    log_message("✅ Relacionamento de exemplo criado: usuário {$follower_id} segue usuário {$followed_id}.");
                } else {
                    log_message("❌ Erro ao criar relacionamento: " . pg_last_error($dbconn), 'error');
                }
            }
        }
        
        // Criar notificação de exemplo
        $create_sample_notification = pg_query($dbconn, "
            INSERT INTO notifications (user_id, type, content, sender_id, created_at) 
            VALUES ({$user_ids[1]}, 'follow', 'Usuário começou a seguir você', {$user_ids[0]}, NOW())
            ON CONFLICT DO NOTHING
        ");
        
        if ($create_sample_notification) {
            log_message("✅ Notificação de exemplo criada.");
        }
    }
} else {
    log_message("ℹ️ Poucos usuários cadastrados. Pulando criação de dados de exemplo.", 'info');
}

// ETAPA 8: Verificar status final
echo "<h2>📋 Etapa 8: Status Final das Tabelas</h2>";

$tables_to_check = [
    'user_follows' => 'SELECT COUNT(*) FROM user_follows',
    'notifications' => 'SELECT COUNT(*) FROM notifications',
    'post_upvotes' => 'SELECT COUNT(*) FROM post_upvotes',
    'users (com unread_notifications)' => "SELECT COUNT(*) FROM users WHERE unread_notifications IS NOT NULL",
    'posts (com upvotes_count)' => "SELECT COUNT(*) FROM posts WHERE upvotes_count IS NOT NULL"
];

foreach ($tables_to_check as $table => $query) {
    $result = pg_query($dbconn, $query);
    if ($result) {
        $count = pg_fetch_result($result, 0, 0);
        log_message("📊 {$table}: {$count} registros", 'info');
    } else {
        log_message("❌ Erro ao verificar {$table}: " . pg_last_error($dbconn), 'error');
    }
}

// ETAPA 9: Criar arquivos necessários se não existirem
echo "<h2>📁 Etapa 9: Verificando arquivos necessários</h2>";

$required_files = [
    'upvote_post.php' => '<?php
session_start();
require_once "includes/db_connect.php";
require_once "includes/auth.php";
require_once "includes/notification_helper.php";

header("Content-Type: application/json");

if (!is_logged_in()) {
    echo json_encode(["success" => false, "message" => "Você precisa estar logado"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$post_id = intval($input["post_id"]);
$action = $input["action"]; // "add" ou "remove"
$user_id = $_SESSION["user_id"];

if ($action === "add") {
    $result = pg_query($dbconn, "INSERT INTO post_upvotes (post_id, user_id) VALUES ($post_id, $user_id) ON CONFLICT DO NOTHING");
} else {
    $result = pg_query($dbconn, "DELETE FROM post_upvotes WHERE post_id = $post_id AND user_id = $user_id");
}

if ($result) {
    $count_result = pg_query($dbconn, "SELECT upvotes_count FROM posts WHERE id = $post_id");
    $upvotes_count = pg_fetch_result($count_result, 0, 0);
    
    echo json_encode(["success" => true, "upvotes_count" => $upvotes_count]);
} else {
    echo json_encode(["success" => false, "message" => "Erro ao processar upvote"]);
}
?>',

    'delete_notification.php' => '<?php
session_start();
require_once "includes/db_connect.php";
require_once "includes/auth.php";
require_once "includes/notification_helper.php";

header("Content-Type: application/json");

if (!is_logged_in()) {
    echo json_encode(["success" => false, "message" => "Você precisa estar logado"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$notification_id = intval($input["notification_id"]);
$user_id = $_SESSION["user_id"];

if (deleteNotification($dbconn, $notification_id, $user_id)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Erro ao excluir notificação"]);
}
?>'
];

foreach ($required_files as $filename => $content) {
    if (!file_exists($filename)) {
        if (file_put_contents($filename, $content)) {
            log_message("✅ Arquivo {$filename} criado com sucesso!");
        } else {
            log_message("❌ Erro ao criar arquivo {$filename}", 'error');
        }
    } else {
        log_message("ℹ️ Arquivo {$filename} já existe.", 'info');
    }
}

// Finalização
echo "<div style='margin-top: 30px; padding: 20px; background: #1b5e20; border-radius: 8px; border-left: 4px solid #4CAF50;'>
    <h2 style='color: #4CAF50; margin-top: 0;'>🎉 Configuração Concluída!</h2>
    <p>O sistema de notificações foi configurado com sucesso! Agora você pode:</p>
    <ul>
        <li>✅ Ver notificações no menu de navegação</li>
        <li>✅ Receber notificações de novos seguidores</li>
        <li>✅ Receber notificações de upvotes em posts</li>
        <li>✅ Receber notificações de comentários</li>
        <li>✅ Receber notificações de novos posts de usuários seguidos</li>
    </ul>
    
    <div style='margin-top: 20px;'>
        <a href='index.php' class='btn'>🏠 Ir para a Página Inicial</a>
        <a href='notifications.php' class='btn'>🔔 Ver Notificações</a>
        <a href='create_post.php' class='btn'>✍️ Criar Post</a>
    </div>
</div>

</body>
</html>";
?>