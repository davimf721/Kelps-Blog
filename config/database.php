<?php

declare(strict_types=1);

/**
 * Configuração do banco de dados PostgreSQL.
 *
 * No Railway as variáveis PGHOST/PGPORT/etc. são injetadas automaticamente
 * quando o serviço PostgreSQL está linkado ao app.
 */
return [
    'driver' => 'pgsql',

    // Railway interno: PG* | Railway externo: DB_* | local: fallback
    'host'     => getenv('PGHOST')     ?: getenv('DB_HOST')     ?: 'localhost',
    'port'     => getenv('PGPORT')     ?: getenv('DB_PORT')     ?: '5432',
    'database' => getenv('PGDATABASE') ?: getenv('DB_NAME')     ?: 'kelps_blog',
    'username' => getenv('PGUSER')     ?: getenv('DB_USER')     ?: 'postgres',
    'password' => getenv('PGPASSWORD') ?: getenv('DB_PASSWORD') ?: '',

    // SSL obrigatório em produção / Railway
    'sslmode' => (getenv('APP_ENV') === 'production' || getenv('RAILWAY_ENVIRONMENT'))
        ? 'require'
        : 'prefer',

    'charset'         => 'UTF8',
    'connect_timeout' => 10,
];
