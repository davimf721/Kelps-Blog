# Skill: DB Migration — Kelps Blog

## Quando usar
Ative esta skill quando o usuário pedir para:
- Criar uma nova migração de banco de dados
- Adicionar colunas/tabelas ao schema
- Executar migrations em produção (Railway)
- Verificar o estado atual do schema

---

## Contexto do banco

- **SGBD:** PostgreSQL (Railway gerenciado)
- **Encoding:** UTF-8
- **Migrations:** arquivos SQL em `database/migrations/` com prefixo numérico
- **Schema atual:** `database/schema.sql` (estado completo)
- **Runner:** `database/migrate.php`

## Convenção de nomenclatura
```
database/migrations/
  001_create_users_table.sql
  002_create_posts_table.sql
  003_create_comments_table.sql
  004_create_upvotes_table.sql
  005_create_followers_table.sql
  006_create_notifications_table.sql
  007_create_user_profiles.sql
  008_add_security_fields.sql
  009_add_rate_limits_table.sql
  010_nova_migration.sql   ← próxima
```

## Como criar uma migration

### Template padrão
```sql
-- database/migrations/NNN_descricao.sql
-- Criado em: YYYY-MM-DD
-- Descrição: O que esta migration faz

-- ===== UP =====

CREATE TABLE IF NOT EXISTS nome_tabela (
    id SERIAL PRIMARY KEY,
    -- colunas...
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_nome_tabela_coluna ON nome_tabela(coluna);

-- ===== ROLLBACK (manual, documentar o que desfaz) =====
-- DROP TABLE IF EXISTS nome_tabela;
```

### Adicionar coluna
```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS website VARCHAR(255);
```

### Criar index
```sql
CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at DESC);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email ON users(LOWER(email));
```

### Criar tabela com FK
```sql
CREATE TABLE IF NOT EXISTS post_tags (
    id      SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    tag     VARCHAR(50) NOT NULL,
    UNIQUE(post_id, tag)
);
```

## Executar migration

### Em desenvolvimento
```bash
# Rodar migration específica
psql $DATABASE_URL -f database/migrations/010_nova_migration.sql

# Ou via runner PHP
php database/migrate.php
```

### Em produção (Railway)
```bash
# Via Railway CLI
railway run php database/migrate.php

# Ou via Railway Dashboard → Deploy → Run Command
php database/migrate.php
```

## Tabelas existentes no projeto

### `users`
```sql
id, username, email, password_hash, is_admin, is_banned, is_active,
bio, location, website, profile_picture, banner_image,
remember_token, token_expires,
reset_token, reset_token_expires,
failed_login_attempts, locked_until,
last_login_at, last_login_ip, password_changed_at,
unread_notifications,
created_at
```

### `posts`
```sql
id, user_id (FK users), title, content,
upvotes_count, created_at, updated_at
```

### `comments`
```sql
id, post_id (FK posts), user_id (FK users), content, created_at
```

### `post_upvotes`
```sql
id, post_id (FK posts), user_id (FK users), created_at
UNIQUE(post_id, user_id)
```

### `followers`
```sql
id, follower_id (FK users), following_id (FK users), created_at
UNIQUE(follower_id, following_id)
```

### `notifications`
```sql
id, user_id (FK users), actor_id (FK users),
type VARCHAR(50), post_id (FK posts),
message TEXT, is_read BOOLEAN DEFAULT FALSE,
created_at
```

### `rate_limits`
```sql
id, key VARCHAR(255), ip VARCHAR(45), created_at
INDEX(key), INDEX(created_at)
```

## Verificar schema atual
```bash
# Listar tabelas
psql $DATABASE_URL -c "\dt"

# Descrever tabela
psql $DATABASE_URL -c "\d users"

# Ver indexes
psql $DATABASE_URL -c "\di"
```

## Boas práticas
1. Sempre usar `IF NOT EXISTS` / `IF EXISTS` para idempotência
2. Nunca alterar migrations já executadas em produção — criar uma nova
3. Documentar o rollback de cada migration (mesmo que manual)
4. Testar localmente antes de rodar em produção
5. Fazer backup antes de migrations destrutivas em produção
