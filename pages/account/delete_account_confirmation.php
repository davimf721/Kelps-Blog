<?php
// Verificar se alguém está tentando acessar esta página estando logado
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Capturar informações do usuário excluído da sessão (se disponível)
$deleted_username = $_SESSION['deleted_username'] ?? 'Usuário';
$deletion_time = $_SESSION['deletion_time'] ?? date('d/m/Y H:i:s');

// Limpar dados da sessão relacionados à exclusão
unset($_SESSION['deleted_username'], $_SESSION['deletion_time']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta Excluída - Kelps Blog</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animação de partículas de fundo */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0; }
            50% { transform: translateY(-100px) rotate(180deg); opacity: 1; }
        }

        .main-container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .deletion-success {
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideIn 0.8s ease-out;
            color: #333;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.03) 10px,
                rgba(255, 255, 255, 0.03) 20px
            );
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%); }
            100% { transform: translateX(100%) translateY(100%); }
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .success-header h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }

        .success-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-top: 15px;
            position: relative;
            z-index: 1;
        }

        .content-section {
            padding: 40px 30px;
        }

        .farewell-message {
            text-align: center;
            margin-bottom: 40px;
        }

        .farewell-message h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .farewell-text {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto;
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(40, 167, 69, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .info-card:hover::before {
            left: 100%;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .card-description {
            color: #666;
            line-height: 1.5;
        }

        .deletion-details {
            background: linear-gradient(135deg, #e3f2fd, #f1f8e9);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .deletion-details h3 {
            color: #1976d2;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
        }

        .detail-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 0 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            animation: fadeInLeft 0.6s ease-out forwards;
            opacity: 0;
        }

        .detail-item:nth-child(1) { animation-delay: 0.1s; }
        .detail-item:nth-child(2) { animation-delay: 0.2s; }
        .detail-item:nth-child(3) { animation-delay: 0.3s; }
        .detail-item:nth-child(4) { animation-delay: 0.4s; }
        .detail-item:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .check-icon {
            color: #28a745;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .privacy-guarantee {
            background: linear-gradient(135deg, #fff3e0, #e8f5e8);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
            border: 2px solid rgba(255, 193, 7, 0.3);
            position: relative;
        }

        .privacy-guarantee::before {
            content: '🛡️';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 15px;
            font-size: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 40px 0 20px 0;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transition: all 0.3s ease;
            transform: translate(-50%, -50%);
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        .btn span {
            position: relative;
            z-index: 1;
        }

        .final-message {
            text-align: center;
            padding: 30px 20px 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            margin-top: 40px;
            border-radius: 12px;
            border-top: 3px solid #28a745;
        }

        .final-message h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .final-message p {
            color: #6c757d;
            font-size: 0.95rem;
            margin: 0;
        }

        .heart-icon {
            color: #e74c3c;
            animation: heartbeat 2s infinite;
        }

        @keyframes heartbeat {
            0%, 50%, 100% { transform: scale(1); }
            25%, 75% { transform: scale(1.1); }
        }

        .stats-highlight {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }

        .stats-highlight strong {
            font-size: 1.2rem;
            display: block;
            margin-bottom: 5px;
        }

        /* Responsividade melhorada */
        @media (max-width: 768px) {
            .deletion-success {
                margin: 10px;
                border-radius: 15px;
            }
            
            .success-header {
                padding: 30px 20px;
            }
            
            .success-header h1 {
                font-size: 2rem;
            }
            
            .success-icon {
                font-size: 3rem;
            }
            
            .content-section {
                padding: 30px 20px;
            }
            
            .info-cards {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
                padding: 12px 25px;
            }
        }

        @media (max-width: 480px) {
            .success-header h1 {
                font-size: 1.6rem;
            }
            
            .farewell-message h2 {
                font-size: 1.4rem;
            }
            
            .info-card, .deletion-details, .privacy-guarantee {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Partículas animadas de fundo -->
    <div class="particles" id="particles"></div>
    
    <div class="main-container">
        <div class="deletion-success">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Conta Excluída com Sucesso</h1>
                <p class="success-subtitle">
                    <?php echo htmlspecialchars($deleted_username); ?>, sua jornada no Kelps Blog chegou ao fim
                </p>
            </div>

            <div class="content-section">
                <div class="farewell-message">
                    <h2>É com pesar que nos despedimos</h2>
                    <p class="farewell-text">
                        Obrigado por ter feito parte da nossa comunidade. Sua presença e contribuições 
                        fizeram a diferença. Embora sua conta tenha sido excluída, as memórias das 
                        interações que você teve aqui permanecerão conosco.
                    </p>
                </div>

                <div class="stats-highlight">
                    <strong>🕒 Exclusão realizada em: <?php echo $deletion_time; ?></strong>
                    <small>Todos os dados foram permanentemente removidos de nossos servidores</small>
                </div>

                <div class="info-cards">
                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="card-title">Perfil Removido</div>
                        <div class="card-description">
                            Sua conta, informações pessoais e preferências foram completamente excluídas
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="card-title">Conteúdo Apagado</div>
                        <div class="card-description">
                            Todos os posts, comentários e interações foram permanentemente removidos
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-users-slash"></i>
                        </div>
                        <div class="card-title">Conexões Desfeitas</div>
                        <div class="card-description">
                            Relacionamentos de seguidores e pessoas seguidas foram removidos
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="card-title">Dados Protegidos</div>
                        <div class="card-description">
                            Todas as informações foram deletadas seguindo as melhores práticas de privacidade
                        </div>
                    </div>
                </div>

                <div class="deletion-details">
                    <h3>
                        <i class="fas fa-clipboard-check"></i>
                        Detalhes da Exclusão Realizada
                    </h3>
                    <ul class="detail-list">
                        <li class="detail-item">
                            <i class="fas fa-check-circle check-icon"></i>
                            <div>
                                <strong>Conta de usuário:</strong> Completamente removida do sistema
                            </div>
                        </li>
                        <li class="detail-item">
                            <i class="fas fa-check-circle check-icon"></i>
                            <div>
                                <strong>Posts e comentários:</strong> Todos excluídos permanentemente
                            </div>
                        </li>
                        <li class="detail-item">
                            <i class="fas fa-check-circle check-icon"></i>
                            <div>
                                <strong>Rede social:</strong> Conexões de seguidores removidas
                            </div>
                        </li>
                        <li class="detail-item">
                            <i class="fas fa-check-circle check-icon"></i>
                            <div>
                                <strong>Notificações:</strong> Histórico de atividades limpo
                            </div>
                        </li>
                        <li class="detail-item">
                            <i class="fas fa-check-circle check-icon"></i>
                            <div>
                                <strong>Dados pessoais:</strong> Informações privadas apagadas
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="privacy-guarantee">
                    <h3 style="color: #f57c00; margin-bottom: 15px;">
                        <i class="fas fa-award"></i> Garantia de Privacidade
                    </h3>
                    <p style="color: #555; margin: 0; line-height: 1.6;">
                        <strong>Seus dados foram tratados com o máximo cuidado.</strong><br>
                        Seguimos rigorosamente as leis de proteção de dados (LGPD/GDPR) e 
                        garantimos que nenhuma informação pessoal permaneceu em nossos sistemas. 
                        Sua privacidade sempre foi e continuará sendo nossa prioridade.
                    </p>
                </div>

                <div class="action-buttons">
                    <a href="index.php" class="btn btn-primary">
                        <span>
                            <i class="fas fa-home"></i>
                            Explorar o Site
                        </span>
                    </a>
                    <a href="register.php" class="btn btn-secondary">
                        <span>
                            <i class="fas fa-user-plus"></i>
                            Criar Nova Conta
                        </span>
                    </a>
                </div>

                <div class="final-message">
                    <h3>
                        <i class="fas fa-heart heart-icon"></i>
                        Sempre Bem-Vindo de Volta!
                    </h3>
                    <p>
                        Se algum dia decidir retornar, estaremos aqui de braços abertos. 
                        A comunidade Kelps Blog estará sempre pronta para recebê-lo novamente.
                        <br><br>
                        <strong>Desejamos tudo de melhor em sua jornada! 🌟</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Criar partículas animadas de fundo
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Posição aleatória
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Delay aleatório para animação
                particle.style.animationDelay = Math.random() * 6 + 's';
                
                // Duração aleatória
                particle.style.animationDuration = (Math.random() * 3 + 4) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Efeito de confete na tela
        function createConfetti() {
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3'];
            const confettiCount = 30;

            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.zIndex = '1000';
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';

                document.body.appendChild(confetti);

                const fallAnimation = confetti.animate([
                    { transform: 'translateY(-10px) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(100vh) rotate(720deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 3000 + 2000,
                    easing: 'ease-in'
                });

                fallAnimation.addEventListener('finish', () => {
                    confetti.remove();
                });
            }
        }

        // Scroll suave para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Inicializar efeitos quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Criar confete após um pequeno delay
            setTimeout(createConfetti, 500);
            
            // Adicionar efeito de typing na mensagem principal
            const subtitle = document.querySelector('.success-subtitle');
            const text = subtitle.textContent;
            subtitle.textContent = '';
            
            let i = 0;
            const typeWriter = () => {
                if (i < text.length) {
                    subtitle.textContent += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 50);
                }
            };
            
            setTimeout(typeWriter, 1000);
        });

        // Efeito de parallax suave no scroll
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.particles');
            const speed = scrolled * 0.5;
            parallax.style.transform = `translateY(${speed}px)`;
        });

        // Contador de tempo desde a exclusão
        function updateTimeCounter() {
            const deletionTime = new Date('<?php echo date('c'); ?>');
            const now = new Date();
            const diff = Math.floor((now - deletionTime) / 1000);
            
            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            
            // Adicionar um pequeno contador se necessário
            // console.log(`Tempo desde exclusão: ${minutes}m ${seconds}s`);
        }

        // Atualizar contador a cada segundo
        setInterval(updateTimeCounter, 1000);
    </script>
</body>
</html>