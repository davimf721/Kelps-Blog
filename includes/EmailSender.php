<?php
// Se você tem PHPMailer instalado via Composer
// require_once 'vendor/autoload.php';
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\SMTP;

// Versão simplificada sem PHPMailer
class EmailSender {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function sendPasswordReset($to, $username, $reset_link, $ip, $user_agent) {
        $subject = "Recuperação de Senha - Kelps Blog";
        
        $html_body = $this->getPasswordResetTemplate($username, $reset_link, $ip, $user_agent);
        
        // Headers para email HTML
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3',
            'Return-Path: ' . $this->config['from_email']
        ];
        
        $headers_string = implode("\r\n", $headers);
        
        // Tentar enviar via mail() do PHP
        if (mail($to, $subject, $html_body, $headers_string)) {
            return ['success' => true, 'message' => 'Email enviado com sucesso'];
        } else {
            return ['success' => false, 'message' => 'Erro ao enviar email'];
        }
    }
    
    private function getPasswordResetTemplate($username, $reset_link, $ip, $user_agent) {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperação de Senha</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f4f4f4; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: white; 
                    border-radius: 10px; 
                    overflow: hidden; 
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
                }
                .header { 
                    background: linear-gradient(135deg, #007bff, #0056b3); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 24px; 
                    font-weight: 600; 
                }
                .content { 
                    padding: 30px; 
                    background: #ffffff; 
                }
                .content h2 { 
                    color: #333; 
                    margin-top: 0; 
                    font-size: 20px; 
                }
                .button { 
                    display: inline-block; 
                    padding: 15px 30px; 
                    background: linear-gradient(135deg, #007bff, #0056b3); 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 8px; 
                    margin: 20px 0; 
                    font-weight: 600; 
                    font-size: 16px; 
                    transition: all 0.3s ease;
                }
                .button:hover { 
                    background: linear-gradient(135deg, #0056b3, #004085); 
                    transform: translateY(-2px); 
                }
                .warning { 
                    background: #fff3cd; 
                    border: 1px solid #ffeaa7; 
                    border-left: 4px solid #ffc107; 
                    padding: 20px; 
                    border-radius: 5px; 
                    margin: 25px 0; 
                }
                .warning strong { 
                    color: #856404; 
                }
                .info-box { 
                    background: #f8f9fa; 
                    border: 1px solid #dee2e6; 
                    border-radius: 5px; 
                    padding: 15px; 
                    margin: 20px 0; 
                    font-family: monospace; 
                    font-size: 14px; 
                    word-break: break-all; 
                    color: #495057; 
                }
                .footer { 
                    padding: 20px; 
                    text-align: center; 
                    background: #f8f9fa; 
                    border-top: 1px solid #dee2e6; 
                    color: #6c757d; 
                    font-size: 12px; 
                }
                .security-info { 
                    background: #e3f2fd; 
                    border: 1px solid #bbdefb; 
                    border-left: 4px solid #2196f3; 
                    padding: 15px; 
                    border-radius: 5px; 
                    margin: 20px 0; 
                }
                .security-info ul { 
                    margin: 10px 0; 
                    padding-left: 20px; 
                }
                .security-info li { 
                    margin: 5px 0; 
                }
                hr { 
                    border: none; 
                    height: 1px; 
                    background: #dee2e6; 
                    margin: 30px 0; 
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Recuperação de Senha</h1>
                </div>
                <div class='content'>
                    <h2>Olá, " . htmlspecialchars($username) . "!</h2>
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Kelps Blog</strong>.</p>
                    
                    <p>Para redefinir sua senha de forma segura, clique no botão abaixo:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $reset_link . "' class='button'>🔑 Redefinir Minha Senha</a>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante - Medidas de Segurança:</strong>
                        <ul>
                            <li>Este link expira em <strong>1 hora</strong> por segurança</li>
                            <li>O link só pode ser usado <strong>uma única vez</strong></li>
                            <li>Se você não solicitou esta redefinição, <strong>ignore este email</strong></li>
                            <li>Por segurança, <strong>nunca compartilhe este link</strong> com ninguém</li>
                            <li>Nossa equipe <strong>nunca pedirá sua senha</strong> por email ou telefone</li>
                        </ul>
                    </div>
                    
                    <p><strong>Se o botão não funcionar:</strong></p>
                    <p>Copie e cole este link completo no seu navegador:</p>
                    <div class='info-box'>" . $reset_link . "</div>
                    
                    <div class='security-info'>
                        <strong>📊 Informações de Segurança da Solicitação:</strong>
                        <ul>
                            <li><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s T') . "</li>
                            <li><strong>Endereço IP:</strong> " . $ip . "</li>
                            <li><strong>Navegador:</strong> " . htmlspecialchars(substr($user_agent, 0, 100)) . "</li>
                        </ul>
                        <p><em>Se estas informações não correspondem à sua solicitação, entre em contato conosco imediatamente.</em></p>
                    </div>
                    
                    <hr>
                    
                    <p><strong>🛡️ Dicas de Segurança:</strong></p>
                    <ul>
                        <li>Use senhas fortes com pelo menos 8 caracteres</li>
                        <li>Combine letras maiúsculas, minúsculas, números e símbolos</li>
                        <li>Não reutilize senhas de outras contas</li>
                        <li>Considere usar um gerenciador de senhas</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Este é um email automático do sistema. Por favor, não responda.</p>
                    <p>Em caso de dúvidas, acesse nosso site ou entre em contato pelo suporte.</p>
                    <p>&copy; " . date('Y') . " Kelps Blog - Todos os direitos reservados</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>