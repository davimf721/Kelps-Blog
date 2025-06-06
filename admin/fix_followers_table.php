<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

// Verificar se o usuário é admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['error'] = "Acesso negado.";
    header('Location: ../index.php');
    exit;
}

echo "<h2>Verificando e corrigindo estrutura da tabela followers...</h2>";

// Verificar se a tabela existe
$check_table = pg_query($dbconn, "SELECT to_regclass('public.followers')");
$table_exists = (pg_fetch_result($check_table, 0, 0) !== NULL);

if (!$table_exists) {
    echo "<p>Criando tabela followers...</p>";
    $create_table = "
    CREATE TABLE followers (
        id SERIAL PRIMARY KEY,
        follower_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        following_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(follower_id, following_id)
    )";
    
    if (pg_query($dbconn, $create_table)) {
        echo "<p style='color: green;'>✓ Tabela followers criada com sucesso!</p>";
        
        // Criar índices
        pg_query($dbconn, "CREATE INDEX idx_followers_follower_id ON followers(follower_id)");
        pg_query($dbconn, "CREATE INDEX idx_followers_following_id ON followers(following_id)");
        echo "<p style='color: green;'>✓ Índices criados com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>✗ Erro ao criar tabela: " . pg_last_error($dbconn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Tabela followers já existe!</p>";
    
    // Verificar estrutura
    $check_structure = pg_query($dbconn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'followers' ORDER BY ordinal_position");
    
    echo "<h3>Estrutura atual da tabela:</h3>";
    echo "<ul>";
    while ($column = pg_fetch_assoc($check_structure)) {
        echo "<li>{$column['column_name']} - {$column['data_type']}</li>";
    }
    echo "</ul>";
    
    // Contar registros
    $count_query = pg_query($dbconn, "SELECT COUNT(*) FROM followers");
    $count = pg_fetch_result($count_query, 0, 0);
    echo "<p>Total de relacionamentos de follow: <strong>$count</strong></p>";
}

echo "<h3>Teste de contadores para alguns usuários:</h3>";

// Listar alguns usuários e seus contadores
$users_query = pg_query($dbconn, "SELECT id, username FROM users ORDER BY id LIMIT 10");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Username</th><th>Seguidores</th><th>Seguindo</th></tr>";

while ($user = pg_fetch_assoc($users_query)) {
    $user_id = $user['id'];
    
    // Contar seguidores
    $followers_query = pg_query($dbconn, "SELECT COUNT(*) FROM followers WHERE following_id = $user_id");
    $followers_count = pg_fetch_result($followers_query, 0, 0);
    
    // Contar seguindo
    $following_query = pg_query($dbconn, "SELECT COUNT(*) FROM followers WHERE follower_id = $user_id");
    $following_count = pg_fetch_result($following_query, 0, 0);
    
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>$followers_count</td>";
    echo "<td>$following_count</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><a href='../admin/index.php'>← Voltar ao painel admin</a>";
?>