<?php

session_start();
require_once 'includes/db_connect.php';

// Este script deve ser executado apenas uma vez para configurar o admin inicial
// Por segurança, comente ou exclua este arquivo após o uso

// ID do usuário que será administrador
$admin_id = 1; // ID do usuário 'ghoul'
$admin_email = 'davimoreiraf@gmail.com'; // Email para verificação

// Verificar se o usuário existe e tem o email correto
$check_query = pg_query($dbconn, "SELECT id, username FROM users WHERE id = $admin_id AND email = '$admin_email'");

if (pg_num_rows($check_query) == 0) {
    die("Usuário não encontrado ou email não corresponde.");
}

// Atualizar o usuário para administrador
$update_query = pg_query($dbconn, "UPDATE users SET is_admin = TRUE WHERE id = $admin_id");

if ($update_query) {
    echo "Usuário ID $admin_id foi promovido a administrador com sucesso!";
    
    // Se o usuário estiver logado atualmente, atualizar a sessão
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $admin_id) {
        $_SESSION['is_admin'] = true;
    }
} else {
    echo "Erro ao promover usuário: " . pg_last_error($dbconn);
}
?>