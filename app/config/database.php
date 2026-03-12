<?php
/**
 * Configurações do banco de dados
 * 
 * Suporta Railway e ambiente local.
 * Railway fornece variáveis DB_* para conexão externa e PG* para interna.
 */

return [
    'driver' => 'pgsql',
    
    // Prioridade: variáveis DB_* (Railway externo) > PG* (Railway interno) > local
    'host' => getenv('DB_HOST') ?: getenv('PGHOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: getenv('PGPORT') ?: '5432',
    'database' => getenv('DB_NAME') ?: getenv('PGDATABASE') ?: getenv('POSTGRES_DB') ?: 'kelps_blog',
    'username' => getenv('DB_USER') ?: getenv('PGUSER') ?: getenv('POSTGRES_USER') ?: 'postgres',
    'password' => getenv('DB_PASSWORD') ?: getenv('PGPASSWORD') ?: getenv('POSTGRES_PASSWORD') ?: '',
    
    // Railway requer SSL em produção (conexão externa)
    'sslmode' => (getenv('APP_ENV') === 'production' || getenv('RAILWAY_ENVIRONMENT')) ? 'require' : 'prefer',
    
    // Configurações de conexão
    'charset' => 'UTF8',
    'connect_timeout' => 10,
    
    // Pool de conexões (se suportado)
    'pool' => [
        'min' => 2,
        'max' => 10,
    ],
];
