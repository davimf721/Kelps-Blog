<?php
session_start();
require_once 'includes/db_connect.php';

// Se não estiver logado, redirecionar para login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar se o usuário realmente está banido
$user_id = $_SESSION['user_id'];
$check_ban = pg_query($dbconn, "SELECT is_banned, username FROM users WHERE id = $user_id");

if ($check_ban && pg_num_rows($check_ban) > 0) {
    $user_data = pg_fetch_assoc($check_ban);
    
    // Se não estiver banido, redirecionar para index
    if ($user_data['is_banned'] != 't') {
        header("Location: index.php");
        exit();
    }
    
    $username = $user_data['username'];
} else {
    // Usuário não encontrado, fazer logout
    session_destroy();
    header("Location: login.php");
    exit();
}

// Definir variáveis para o header
$page_title = "Conta Suspensa - Kelps Blog";
$current_page = 'banned';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .banned-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 200px);
            padding: 20px;
        }
        
        .banned-box {
            background: #2a2a2a;
            border-radius: 15px;
            padding: 50px;
            width: 100%;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            border: 2px solid #ca0e0e;
        }
        
        .banned-icon {
            font-size: 4rem;
            color: #ca0e0e;
            margin-bottom: 20px;
        }
        
        .banned-title {
            color: #ca0e0e;
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .banned-message {
            color: #ccc;
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .banned-details {
            background: #1a1a1a;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #ca0e0e;
        }
        
        .banned-details h3 {
            color: #fff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .banned-details p {
            color: #ccc;
            margin: 10px 0;
        }
        
        .banned-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .banned-btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .banned-btn.contact {
            background: #0e86ca;
            color: white;
        }
        
        .banned-btn.contact:hover {
            background: #0a6aa8;
            transform: translateY(-2px);
        }
        
        .banned-btn.logout {
            background: #666;
            color: white;
        }
        
        .banned-btn.logout:hover {
            background: #555;
            transform: translateY(-2px);
        }
        
        .warning-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .warning-info h4 {
            color: #ffc107;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-info p {
            color: #ccc;
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .banned-box {
                padding: 30px 20px;
            }
            
            .banned-title {
                font-size: 2rem;
            }
            
            .banned-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .banned-btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Kelps Blog</h1>
        <nav>
            <ul>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="banned-container">
            <div class="banned-box">
                <div class="banned-icon">
                    <i class="fas fa-ban"></i>
                </div>
                
                <h1 class="banned-title">Conta Suspensa</h1>
                
                <p class="banned-message">
                    Olá <strong><?php echo htmlspecialchars($username); ?></strong>, sua conta foi temporariamente suspensa pelos administradores do Kelps Blog.
                </p>
                
                <div class="banned-details">
                    <h3><i class="fas fa-info-circle"></i> Informações da Suspensão</h3>
                    <p><strong>Status:</strong> Conta Suspensa</p>
                    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($username); ?></p>
                    <p><strong>Data da Verificação:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                </div>
                
                <div class="warning-info">
                    <h4><i class="fas fa-exclamation-triangle"></i> O que isso significa?</h4>
                    <p>• Você não pode acessar o conteúdo do blog</p>
                    <p>• Não é possível criar posts ou comentários</p>
                    <p>• Sua conta está temporariamente desativada</p>
                    <p>• Entre em contato conosco para esclarecimentos</p>
                </div>
                
                <div class="banned-actions">
                    <a href="mailto:admin@kelpsblog.com?subject=Recurso%20-%20Conta%20Suspensa&body=Olá,%20minha%20conta%20(<?php echo htmlspecialchars($username); ?>)%20foi%20suspensa%20e%20gostaria%20de%20solicitar%20uma%20revisão." 
                       class="banned-btn contact">
                        <i class="fas fa-envelope"></i>
                        Entrar em Contato
                    </a>
                    
                    <a href="logout.php" class="banned-btn logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Fazer Logout
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kelps Blog. Todos os direitos reservados.</p>
    </footer>
</body>
</html>