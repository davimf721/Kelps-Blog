# 🔒 Guia de Segurança - Kelps Blog

## Visão Geral

Este documento descreve as medidas de segurança implementadas e as práticas recomendadas para manter o Kelps Blog seguro.

---

## 🚨 Ações Imediatas Necessárias

### 1. Remover/Proteger `make_admin.php`

```bash
# CRÍTICO: Remova este arquivo IMEDIATAMENTE
rm make_admin.php

# Ou mova para pasta protegida
mkdir -p private
mv make_admin.php private/
```

### 2. Executar Migration de Segurança

```bash
# Conecte ao banco e execute:
psql -h $PGHOST -U $PGUSER -d $PGDATABASE -f database/migrations/008_add_security_fields.sql
```

### 3. Atualizar `includes/db_connect.php`

```php
// MUDAR de:
ini_set('display_errors', 1);

// PARA:
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');
```

---

## 🛡️ Checklist de Segurança

### Proteção contra SQL Injection

- [ ] **TODAS** as queries usam `pg_query_params()` com placeholders
- [ ] Nenhuma query usa concatenação direta de variáveis
- [ ] IDs são sempre convertidos para inteiro antes de uso

**Exemplo correto:**
```php
// ✅ SEGURO
$result = pg_query_params($dbconn, 
    "SELECT * FROM posts WHERE id = $1 AND user_id = $2", 
    [$postId, $userId]
);
```

### Proteção CSRF

- [ ] Todos os formulários incluem `<?= csrf_field() ?>`
- [ ] Todas as ações POST/PUT/DELETE validam CSRF
- [ ] Token CSRF é regenerado após ações críticas

**Exemplo:**
```php
// No formulário
<form method="POST">
    <?= csrf_field() ?>
    <input type="text" name="title">
    <button type="submit">Enviar</button>
</form>

// Na validação
if (!csrf_verify()) {
    exit; // Já retorna 403 automaticamente
}
```

### Proteção XSS

- [ ] Todo output usa `htmlspecialchars()` ou helper `e()`
- [ ] Markdown é sanitizado antes de renderizar
- [ ] Headers Content-Security-Policy estão configurados

**Exemplo:**
```php
// ✅ SEGURO
<h1><?= e($post['title']) ?></h1>

// ❌ VULNERÁVEL
<h1><?= $post['title'] ?></h1>
```

### Autenticação Segura

- [ ] Senhas usam `password_hash()` com `PASSWORD_DEFAULT`
- [ ] Session ID é regenerado após login
- [ ] Cookies usam flags `httponly`, `secure`, `samesite`
- [ ] Rate limiting implementado no login

### Rate Limiting

- [ ] Login: máximo 5 tentativas em 15 minutos
- [ ] Registro: máximo 3 tentativas por hora
- [ ] API: máximo 60 requisições por minuto
- [ ] Comentários: máximo 10 por minuto

---

## 📋 Correções por Arquivo

### Alta Prioridade

| Arquivo | Vulnerabilidades | Status |
|---------|-----------------|--------|
| auth.php | SQL Injection (linha 8) | ⬜ Pendente |
| fetch_comments.php | SQL Injection (linha 14) | ⬜ Pendente |
| profile.php | SQL Injection (linhas 54, 70) | ⬜ Pendente |
| edit_post.php | SQL Injection (linhas 18, 46) | ⬜ Pendente |
| delete_post.php | SQL Injection (linhas 27-52) | ⬜ Pendente |
| delete_comment.php | SQL Injection (linha 24) | ⬜ Pendente |
| post.php | SQL Injection (linha 19) | ⬜ Pendente |
| fetch_posts.php | SQL Injection (linha 14) | ⬜ Pendente |

### Média Prioridade

| Arquivo | Vulnerabilidades | Status |
|---------|-----------------|--------|
| login.php | CSRF ausente | ⬜ Pendente |
| register.php | CSRF ausente | ⬜ Pendente |
| create_post.php | CSRF ausente | ⬜ Pendente |
| edit_post.php | CSRF ausente | ⬜ Pendente |
| add_comment.php | CSRF ausente | ⬜ Pendente |
| follow_handler.php | CSRF ausente | ⬜ Pendente |

---

## 🔧 Como Corrigir SQL Injection

### Padrão de Correção

```php
// ❌ ANTES (Vulnerável)
$post_id = $_GET['id'];
$result = pg_query($dbconn, "SELECT * FROM posts WHERE id = $post_id");

// ✅ DEPOIS (Seguro)
$post_id = (int) $_GET['id']; // Converter para inteiro
$result = pg_query_params($dbconn, 
    "SELECT * FROM posts WHERE id = $1", 
    [$post_id]
);
```

### Múltiplos parâmetros

```php
// ❌ ANTES
$query = "UPDATE posts SET title = '$title', content = '$content' WHERE id = $id";

// ✅ DEPOIS
$query = "UPDATE posts SET title = $1, content = $2 WHERE id = $3";
$result = pg_query_params($dbconn, $query, [$title, $content, $id]);
```

---

## 🔧 Como Adicionar CSRF

### Em formulários HTML

```php
<form method="POST" action="create_post.php">
    <?= csrf_field() ?>
    <!-- resto do formulário -->
</form>
```

### Em requisições AJAX

```javascript
// Na página, adicione a meta tag
<?= csrf_meta() ?>

// No JavaScript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(data)
});
```

### Validação no backend

```php
// No início do arquivo que processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        exit; // Já retorna 403
    }
}
```

---

## 🌐 Configurações Railway

### Variáveis de ambiente obrigatórias

```
APP_ENV=production
APP_DEBUG=false
```

### Headers de segurança (já configurados em .htaccess)

Se usar Nginx no Railway, adicione:

```nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

---

## 📊 Monitoramento

### Logs importantes

- `storage/logs/php_errors.log` - Erros PHP
- `storage/logs/database.log` - Erros de banco
- Tabela `audit_logs` - Ações sensíveis

### Alertas recomendados

1. Múltiplas tentativas de login falhas do mesmo IP
2. Erros 500 frequentes
3. Tentativas de acesso a arquivos bloqueados
4. Padrões suspeitos de requisições

---

## 🔗 Recursos

- [OWASP Top 10](https://owasp.org/Top10/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [PostgreSQL Security](https://www.postgresql.org/docs/current/security.html)
