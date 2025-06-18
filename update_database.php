<?php
require_once 'includes/db_connect.php';

echo "Atualizando banco de dados para recuperação de senha...\n";

try {
    // Adicionar colunas para recuperação de senha
    $queries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64)",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expires TIMESTAMP",
        
        // Criar tabela de logs
        "CREATE TABLE IF NOT EXISTS user_logs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            action VARCHAR(50) NOT NULL,
            ip_address INET,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        )",
        
        // Criar índices
        "CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users(reset_token)",
        "CREATE INDEX IF NOT EXISTS idx_user_logs_user_id ON user_logs(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_user_logs_created_at ON user_logs(created_at)"
    ];
    
    foreach ($queries as $query) {
        $result = pg_query($dbconn, $query);
        if (!$result) {
            throw new Exception("Erro ao executar: " . $query . " - " . pg_last_error($dbconn));
        }
        echo "✅ Executado: " . substr($query, 0, 50) . "...\n";
    }
    
    echo "\n🎉 Banco de dados atualizado com sucesso!\n";
    echo "Sistema de recuperação de senha está pronto para uso.\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
}
?>