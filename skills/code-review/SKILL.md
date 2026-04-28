# Skill: Code Review — Kelps Blog

## Quando usar
Ative esta skill quando o usuário pedir para:
- Revisar código PHP do projeto
- Verificar se código segue os padrões da arquitetura
- Auditar segurança de uma função/arquivo
- Checar se prepared statements estão sendo usados corretamente

---

## Padrões arquiteturais do projeto

### Estrutura de camadas (respeitar obrigatoriamente)
```
Request → Middleware → Controller → Service → Repository → Database
```

- **Controllers** (`src/Controller/`) — apenas recebem request, chamam service, retornam response. Não fazem queries diretas.
- **Services** (`src/Service/`) — contêm a lógica de negócio. Validam dados, orquestram repositórios.
- **Repositories** (`src/Repository/`) — único lugar com queries SQL. Sempre via `Connection::execute()`.
- **Middlewares** (`src/Middleware/`) — Auth, CSRF, Admin, Banned. Interceptam requests antes dos controllers.

### Regras de segurança obrigatórias

#### ✅ CORRETO — Prepared statements
```php
// Via Connection::execute()
$this->db->fetchOne('SELECT * FROM users WHERE id = $1', [$id]);
$this->db->execute('INSERT INTO posts (title) VALUES ($1)', [$title]);
```

#### ❌ ERRADO — SQL Injection
```php
// NUNCA fazer isso:
pg_query($conn, "SELECT * FROM users WHERE id = $id");
pg_query($conn, "SELECT * FROM users WHERE email = '$email'");
```

#### ✅ CORRETO — Sanitização
```php
$title = InputSanitizer::string($data['title'], 200);
$email = InputSanitizer::email($data['email']);
```

#### ✅ CORRETO — CSRF em formulários
```php
// View: sempre incluir campo CSRF
<?= $csrf ?>

// Controller: validado automaticamente pelo CsrfMiddleware
// Mas se precisar verificar manualmente:
if (!Csrf::verify(Csrf::fromRequest())) { ... }
```

#### ✅ CORRETO — Escape de output
```php
// Nas views, sempre usar htmlspecialchars ou e():
<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>
// Ou simplesmente:
<?= e($value) ?>
```

### Checklist de code review

**Segurança**
- [ ] Todas as queries usam prepared statements (parâmetros `$1`, `$2`, etc.)
- [ ] Inputs do usuário são sanitizados antes de usar (`InputSanitizer::*`)
- [ ] Output em HTML usa `htmlspecialchars()` ou `e()`
- [ ] Formulários POST têm campo CSRF (`<?= $csrf ?>`)
- [ ] Endpoints que modificam dados verificam autenticação
- [ ] Operações administrativas verificam `is_admin`

**Arquitetura**
- [ ] Controller não acessa `Connection` diretamente
- [ ] Service não acessa `$_SESSION` ou `$_POST` diretamente
- [ ] Repository não tem lógica de negócio
- [ ] Exceptions usam `RuntimeException` com mensagem clara
- [ ] Métodos têm responsabilidade única

**Qualidade**
- [ ] `declare(strict_types=1)` no topo do arquivo
- [ ] Type hints em todos os parâmetros e retornos
- [ ] Sem `var_dump`, `print_r` ou `die()` no código
- [ ] Sem credenciais hardcoded

### Padrão de resposta do Controller
```php
// View
return $this->render($response, 'posts/show', ['post' => $post]);

// Redirect
return $this->redirect($response, '/posts/' . $postId);

// JSON
return $this->json($response, ['success' => true]);

// Erro
return $this->json($response, ['error' => 'msg'], 422);
```

### Padrão de repository
```php
// Fetch
public function findById(int $id): ?array
{
    return $this->db->fetchOne('SELECT * FROM table WHERE id = $1', [$id]);
}

// Insert
public function create(array $data): int
{
    return $this->db->insert('table', $data); // retorna o ID
}

// Update
public function update(int $id, array $data): void
{
    $this->db->update('table', $data, ['id' => $id]);
}
```

## Como revisar

1. Leia o arquivo apontado pelo usuário
2. Verifique cada item do checklist acima
3. Identifique problemas críticos (segurança) vs. melhorias (qualidade)
4. Sugira correções com código concreto, não apenas descrição
5. Priorize: Segurança > Arquitetura > Qualidade
