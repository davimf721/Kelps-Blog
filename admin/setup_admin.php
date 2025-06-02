<?php
session_start();
require_once 'includes/db_connect.php';

// Verificar se a coluna is_admin já existe na tabela users
$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                  WHERE table_name='users' AND column_name='is_admin'");
                                  
if (pg_num_rows($check_column) == 0) {
    // Adicionar a coluna is_admin se não existir
    $alter_users = "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE";
    $result = pg_query($dbconn, $alter_users);
    if ($result) {
        echo "Coluna is_admin adicionada à tabela users.<br>";
    } else {
        echo "Erro ao adicionar coluna is_admin: " . pg_last_error($dbconn) . "<br>";
    }
}

// Atualizar seu usuário para ser administrador
$update_user = "UPDATE users SET is_admin = TRUE WHERE id = 1 OR username = 'ghoul' OR email = 'davimoreiraf@gmail.com'";
$result = pg_query($dbconn, $update_user);

if ($result) {
    echo "Usuário Ghoul atualizado como administrador com sucesso!<br>";
    
    // Atualizar a sessão se o usuário estiver logado
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1) {
        $_SESSION['is_admin'] = true;
        echo "Sessão de administrador atualizada.<br>";
    }
} else {
    echo "Erro ao atualizar usuário: " . pg_last_error($dbconn) . "<br>";
}

echo "Setup concluído! <a href='index.php'>Voltar para a página inicial</a>";
?>