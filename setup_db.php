<?php
require_once 'includes/db_connect.php';

// Criar tabela users
$create_users_table = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
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
    post_id INTEGER REFERENCES posts(id),
    user_id INTEGER REFERENCES users(id),
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Executar queries
$tables = [
    'users' => $create_users_table,
    'posts' => $create_posts_table,
    'comments' => $create_comments_table
];

$success = true;
foreach ($tables as $table_name => $query) {
    $result = pg_query($dbconn, $query);
    if (!$result) {
        echo "Erro ao criar tabela {$table_name}: " . pg_last_error($dbconn) . "<br>";
        $success = false;
    } else {
        echo "Tabela {$table_name} criada ou já existente.<br>";
    }
}

// Inserir dados de exemplo se as tabelas foram criadas com sucesso
if ($success) {
    // Verificar se já existe um usuário de exemplo
    $check_user = pg_query($dbconn, "SELECT id FROM users WHERE username = 'Ghoul' LIMIT 1");
    
    if (pg_num_rows($check_user) == 0) {
        // Inserir usuário de exemplo
        $password_hash = password_hash('senha123', PASSWORD_DEFAULT);
        $insert_user = pg_query($dbconn, "INSERT INTO users (username, email, password_hash) 
                                         VALUES ('Ghoul', 'ghoul@example.com', '$password_hash')
                                         RETURNING id");
        
        if ($insert_user) {
            $user = pg_fetch_assoc($insert_user);
            $user_id = $user['id'];
            
            // Inserir post de exemplo
            $post_title = "Hello, Kelps Blog!";
            $post_content = "Bem vindo ao meu blog! Aqui você encontrará uma variedade de tópicos interessantes.";
            
            $insert_post = pg_query($dbconn, "INSERT INTO posts (user_id, title, content, upvotes_count) 
                                             VALUES ($user_id, '$post_title', '$post_content', 20)
                                             RETURNING id");
            
            if ($insert_post) {
                echo "Dados de exemplo inseridos com sucesso!<br>";
            } else {
                echo "Erro ao inserir post de exemplo: " . pg_last_error($dbconn) . "<br>";
            }
        } else {
            echo "Erro ao inserir usuário de exemplo: " . pg_last_error($dbconn) . "<br>";
        }
    } else {
        echo "Dados de exemplo já existem.<br>";
    }
}

echo "<p>Configuração concluída. <a href='index.php'>Voltar para a página principal</a></p>";
?>