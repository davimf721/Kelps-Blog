<?php
// Configurações de email
$email_config = [
    'smtp_host' => 'smtp.gmail.com', // ou seu provedor SMTP
    'smtp_port' => 587, // 587 para TLS, 465 para SSL
    'smtp_secure' => 'tls', // 'tls' ou 'ssl'
    'smtp_username' => 'seu.email@gmail.com', // seu email
    'smtp_password' => 'sua_senha_de_app', // senha de app do Gmail
    'from_email' => 'noreply@kelpsblog.com',
    'from_name' => 'Kelps Blog'
];

// Para Gmail, você precisa:
// 1. Ativar autenticação de 2 fatores
// 2. Gerar uma "senha de app" específica
// 3. Usar essa senha aqui ao invés da senha normal

// Para outros provedores:
// - Hostinger: smtp.hostinger.com
// - UOL Host: smtp.uol.com.br
// - Locaweb: smtp.locaweb.com.br
// - GoDaddy: smtpout.secureserver.net
?>