# Skill: Railway Deploy — Kelps Blog

## Quando usar
Ative esta skill quando o usuário pedir para:
- Fazer deploy no Railway
- Verificar variáveis de ambiente de produção
- Debugar falha de build/deploy
- Configurar banco de dados PostgreSQL no Railway
- Rodar migrations em produção

---

## Contexto do projeto
- **Stack:** PHP 8.2 + Slim 4 + PostgreSQL
- **Deploy:** Docker via `Dockerfile` (stage multi-stage: Composer + Apache)
- **Porta:** 8080 (configurada no Dockerfile)
- **DocumentRoot:** `/var/www/html/public` (onde está o `index.php`)
- **Health check:** `GET /health` → retorna `{"status":"healthy"}`
- **Config deploy:** `railway.json` na raiz

## Variáveis de ambiente obrigatórias no Railway
| Variável | Descrição |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública (ex: `https://kelps-blog.up.railway.app`) |
| `PGHOST` | Fornecida automaticamente pelo Railway |
| `PGPORT` | Fornecida automaticamente |
| `PGDATABASE` | Fornecida automaticamente |
| `PGUSER` | Fornecida automaticamente |
| `PGPASSWORD` | Fornecida automaticamente |

> O arquivo `config/database.php` lê as variáveis `PGHOST`/`PGPORT`/etc. automaticamente.

## Checklist de deploy

### 1. Verificar variáveis no Railway
```bash
# No Railway Dashboard → seu serviço → Variables
# Confirmar que APP_ENV=production está definido
# O banco PostgreSQL deve estar linkado ao serviço
```

### 2. Rodar migrations
```bash
# No Railway CLI ou via "Run Command" no dashboard:
php database/migrate.php
```

### 3. Verificar health check
```bash
curl https://SEU_APP.railway.app/health
# Esperado: {"status":"healthy","timestamp":XXXXXX}
```

### 4. Verificar logs
```bash
# No Railway Dashboard → Logs
# Ou localmente:
tail -f storage/logs/app.log
tail -f storage/logs/php_errors.log
```

## Solução de problemas comuns

### Erro 502/503 após deploy
1. Verificar se a porta está correta: `EXPOSE 8080` no Dockerfile ✓
2. Verificar se `railway.json` tem `healthcheckPath: "/health"` ✓
3. Checar logs no Railway Dashboard

### Banco de dados não conecta
1. Confirmar que o serviço PostgreSQL está linkado no Railway
2. Verificar se `sslmode=require` está ativo (necessário no Railway)
3. Testar: `curl https://APP.railway.app/health`

### Build falha no Composer
1. Verificar se `composer.lock` está no git
2. Verificar se `composer.json` está válido: `php composer validate`

### Permissões de storage
O Dockerfile já configura `chown -R www-data:www-data storage/` automaticamente.

## Comandos úteis Railway CLI
```bash
railway login
railway link            # vincular projeto local
railway up              # deploy manual
railway logs            # ver logs em tempo real
railway run php -v      # checar versão PHP no container
railway shell           # abrir shell no container
```

## Migrations em produção
```bash
# Via Railway CLI
railway run php database/migrate.php

# Ou adicionar ao startup script (docker/apache-start.sh):
php /var/www/html/database/migrate.php
```
