<?php
require_once 'includes/db_connect.php';

// Configurar saída HTML
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuração do Sistema de Seguidores - Kelps Blog</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .error { color: #a94442; background: #f2dede; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .warning { color: #8a6d3b; background: #fcf8e3; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        button, .btn { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Configuração do Sistema de Seguidores - Kelps Blog</h1>";

// Função para registrar mensagens e exibir na interface
function log_message($message, $type = 'success') {
    echo "<div class='{$type}'>{$message}</div>";
}

// 1. Criar tabela de seguidores
log_message("Criando tabela de seguidores...", 'warning');

$create_followers_table = "
CREATE TABLE IF NOT EXISTS followers (
    id SERIAL PRIMARY KEY,
    follower_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    following_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_follow UNIQUE(follower_id, following_id),
    CONSTRAINT no_self_follow CHECK (follower_id != following_id)
)";

$result = pg_query($dbconn, $create_followers_table);

if ($result) {
    log_message("Tabela 'followers' criada ou já existente.");
} else {
    log_message("Erro ao criar tabela 'followers': " . pg_last_error($dbconn), 'error');
}

// 2. Criar tabela de notificações
log_message("Criando tabela de notificações...", 'warning');

$create_notifications_table = "
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    sender_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    type VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    reference_id INTEGER,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$result = pg_query($dbconn, $create_notifications_table);

if ($result) {
    log_message("Tabela 'notifications' criada ou já existente.");
} else {
    log_message("Erro ao criar tabela 'notifications': " . pg_last_error($dbconn), 'error');
}

// 3. Adicionar coluna de contador de notificações na tabela users, se não existir
$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                 WHERE table_name='users' AND column_name='unread_notifications'");
if (pg_num_rows($check_column) == 0) {
    log_message("Adicionando coluna de contador de notificações à tabela users...", 'warning');
    
    $add_column = pg_query($dbconn, "ALTER TABLE users ADD COLUMN unread_notifications INTEGER DEFAULT 0");
    
    if ($add_column) {
        log_message("Coluna 'unread_notifications' adicionada com sucesso à tabela users.");
    } else {
        log_message("Erro ao adicionar coluna 'unread_notifications': " . pg_last_error($dbconn), 'error');
    }
} else {
    log_message("Coluna 'unread_notifications' já existe na tabela users.");
}

// Criar índices para melhorar a performance
log_message("Criando índices para otimização...", 'warning');

$create_indices = [
    "CREATE INDEX IF NOT EXISTS idx_followers_follower_id ON followers(follower_id)",
    "CREATE INDEX IF NOT EXISTS idx_followers_following_id ON followers(following_id)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)"
];

foreach ($create_indices as $index_query) {
    $result = pg_query($dbconn, $index_query);
    if ($result) {
        log_message("Índice criado ou já existente.");
    } else {
        log_message("Erro ao criar índice: " . pg_last_error($dbconn), 'error');
    }
}

// Finalização
echo "<div style='margin-top: 30px;'>
    <h2>Configuração Concluída!</h2>
    <p>O sistema de seguidores e notificações está pronto para uso.</p>
    <div style='margin-top: 20px;'>
        <a href='index.php' class='btn'>Ir para a Página Inicial</a>
    </div>
</div>
</body>
</html>";
?>