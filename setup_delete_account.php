<?php
require_once 'includes/db_connect.php';

echo "Criando tabelas necessárias para exclusão de conta...\n";

try {
    // Criar tabela de logs se não existir
    $create_user_logs = "
    CREATE TABLE IF NOT EXISTS user_logs (
        id SERIAL PRIMARY KEY,
        user_id INTEGER,
        action VARCHAR(50) NOT NULL,
        ip_address INET,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT NOW(),
        additional_data JSONB
    )";
    
    // Criar tabela de seguidores se não existir
    $create_followers = "
    CREATE TABLE IF NOT EXISTS followers (
        id SERIAL PRIMARY KEY,
        follower_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        following_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(follower_id, following_id)
    )";
    
    // Criar tabela de upvotes se não existir
    $create_upvotes = "
    CREATE TABLE IF NOT EXISTS upvotes (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        post_id INTEGER REFERENCES posts(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT NOW(),
        UNIQUE(user_id, post_id)
    )";
    
    // Criar tabela de notificações se não existir
    $create_notifications = "
    CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        from_user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT NOW()
    )";
    
    $queries = [
        'user_logs' => $create_user_logs,
        'followers' => $create_followers,
        'upvotes' => $create_upvotes,
        'notifications' => $create_notifications
    ];
    
    foreach ($queries as $table_name => $query) {
        $result = pg_query($dbconn, $query);
        if (!$result) {
            throw new Exception("Erro ao criar tabela {$table_name}: " . pg_last_error($dbconn));
        }
        echo "✅ Tabela {$table_name} criada ou já existente.\n";
    }
    
    // Criar índices para performance
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_user_logs_action ON user_logs(action)",
        "CREATE INDEX IF NOT EXISTS idx_user_logs_created_at ON user_logs(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_followers_follower_id ON followers(follower_id)",
        "CREATE INDEX IF NOT EXISTS idx_followers_following_id ON followers(following_id)",
        "CREATE INDEX IF NOT EXISTS idx_upvotes_user_id ON upvotes(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_upvotes_post_id ON upvotes(post_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_from_user_id ON notifications(from_user_id)"
    ];
    
    foreach ($indices as $index_query) {
        $result = pg_query($dbconn, $index_query);
        if (!$result) {
            echo "⚠️ Aviso ao criar índice: " . pg_last_error($dbconn) . "\n";
        } else {
            echo "✅ Índice criado.\n";
        }
    }
    
    echo "\n🎉 Todas as tabelas necessárias foram criadas com sucesso!\n";
    echo "O sistema de exclusão de conta agora está funcionando corretamente.\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
}
?>