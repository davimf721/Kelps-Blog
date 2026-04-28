# Troubleshooting - Posts não aparecem na home

## Verificação Rápida

### 1. Teste a API de Posts
Acesse: `https://seu-site.com/test_posts_api.php`

Você deve ver algo estruturadinho:
```json
{
    "timestamp": "2026-03-13 18:30:45",
    "database_connected": "true",
    "total_posts": 6,
    "total_users": 3,
    "total_comments": 12,
    "recent_posts": [
        {
            "id": 5,
            "title": "Meu primeiro post",
            "author": "ghoul",
            "created_at": "2026-03-13 18:20:00",
            "upvotes": "0"
        }
    ]
}
```

**O que verificar:**
- ✅ `database_connected` = true (banco conectado)
- ✅ `total_posts` > 0 (há posts no banco)
- ✅ `recent_posts` não está vazio (posts retornando)

---

### 2. Se `total_posts = 0`
**Problema:** Não há posts no banco de dados

**Solução:**
- Crie um post manualmente na interface
- Ou adicione via SQL: 
```sql
INSERT INTO posts (user_id, title, content, created_at) 
VALUES (1, 'Teste', 'Conteúdo de teste', NOW());
```

---

### 3. Se `database_connected = false`
**Problema:** Conexão com banco não funcionando

**Solução:**
- Verifique variáveis de ambiente (PORT, DATABASE_URL)
- Teste conexão PostgreSQL manualmente
- Verifique se Railway está online

---

### 4. Se tudo está OK no teste mas posts não aparecem na home
**Problema:** Problema no JavaScript do index.php

**Solução:**
- Abra DevTools (F12) → Console
- Procure por erros (cors, network, etc)
- Verifique se fetch('/fetch_posts.php') está retornando JSON válido

---

## Checklists Finais

✅ **TODOS os posts aparecem agora:**
- Removido LIMIT 50, agora busca todos os posts
- Paginação controlada por JavaScript (10 posts/página)
- Sem limite de banco de dados

✅ **Segurança corrigida:**
- Falsos positivos de permissões removidos
- Apenas alerta se permissões >= 0666 (write para others)

✅ **Error handling melhorado:**
- Test script para diagnosticar problemas
- Logs de erro em caso de falha
- JSON sempre válido (mesmo se vazio)

---

## Próximos Passos

1. **Deploy na Railway**
   ```bash
   git push
   ```
   (Railway fará deploy automaticamente)

2. **Testar na produção:**
   - Acesse `/test_posts_api.php`
   - Faça login e vá para home
   - Verifique se todos os posts aparecem

3. **Se problema persistir:**
   - Compartilhe output de `/test_posts_api.php`
   - Verifique logs do Railway
   - Rode: `php test_posts_api.php` localmente
