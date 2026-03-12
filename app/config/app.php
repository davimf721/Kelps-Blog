<?php
/**
 * Configurações da aplicação
 * 
 * Este arquivo contém todas as configurações globais da aplicação.
 */

return [
    'name' => 'Kelps Blog',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt-BR',
    
    // Configurações de segurança
    'security' => [
        'csrf_token_lifetime' => 3600, // 1 hora
        'session_lifetime' => 7200,    // 2 horas
        'remember_lifetime' => 2592000, // 30 dias
        
        // Rate limiting
        'rate_limits' => [
            'login' => [
                'max_attempts' => 5,
                'decay_minutes' => 15,
                'lockout_minutes' => 30,
            ],
            'register' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'api' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
            'comment' => [
                'max_attempts' => 10,
                'decay_minutes' => 1,
            ],
            'post' => [
                'max_attempts' => 5,
                'decay_minutes' => 1,
            ],
        ],
        
        // Política de senhas
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special' => false,
        ],
    ],
    
    // Headers de segurança
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:;",
    ],
    
    // Paginação
    'pagination' => [
        'posts_per_page' => 10,
        'comments_per_page' => 20,
        'users_per_page' => 20,
    ],
];
