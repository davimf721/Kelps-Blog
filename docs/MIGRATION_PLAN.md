# 🚀 Plano de Migração - Kelps Blog

## Visão Geral

Este documento detalha o plano de migração do Kelps Blog para a nova arquitetura Clean Code, com foco em segurança para uma rede social.

**Tempo estimado total: 4-6 semanas**

---

## 📋 Fases de Migração

### Fase 0: Ações Críticas Imediatas (Dia 1)
**⏰ Duração: 2-4 horas**

#### 0.1. Remover/Proteger arquivos perigosos
```bash
# Deletar make_admin.php (CRÍTICO!)
rm make_admin.php

# Ou mover para pasta protegida
mkdir -p private/setup
mv make_admin.php private/setup/
mv setup_*.php private/setup/
```

#### 0.2. Desativar display_errors em produção
```php
// includes/db_connect.php - ALTERAR IMEDIATAMENTE
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // ← MUDAR DE 1 PARA 0
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');
```

#### 0.3. Criar arquivo .htaccess básico de proteção
```apache
# .htaccess na raiz
<FilesMatch "^(setup_|make_admin|database\.sql)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Bloquear acesso a arquivos sensíveis
<FilesMatch "\.(env|sql|md|json|lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

### Fase 1: Infraestrutura de Segurança (Semana 1)
**⏰ Duração: 5-7 dias**

#### 1.1. Criar estrutura de diretórios base
```bash
mkdir -p src/{Core,Security,Database,Auth}
mkdir -p config
mkdir -p storage/{logs,cache}
mkdir -p public/{css,js,images}
mkdir -p resources/views/{layouts,components,auth,posts,profile,admin,errors}
mkdir -p database/migrations
mkdir -p tests/{Unit,Integration,Feature}
mkdir -p docs
```

#### 1.2. Implementar classe de conexão segura
```php
// Criar: src/Database/Connection.php
// (código no ARCHITECTURE.md)
```

#### 1.3. Implementar proteção CSRF
```php
// Criar: src/Security/Csrf.php
// (código no ARCHITECTURE.md)
```

#### 1.4. Implementar sanitização de inputs
```php
// Criar: src/Security/InputSanitizer.php
// (código no ARCHITECTURE.md)
```

#### 1.5. Implementar Rate Limiter
```php
// Criar: src/Security/RateLimiter.php
// Criar migration para tabela rate_limits
```

#### 1.6. Atualizar todas as queries para prepared statements
**Esta é a tarefa mais crítica e trabalhosa**

Lista de arquivos para corrigir (ordem de prioridade):

| Arquivo | Queries | Prioridade |
|---------|---------|------------|
| login.php | 3 | 🔴 CRÍTICA |
| register.php | 2 | 🔴 CRÍTICA |
| includes/auth.php | 5 | 🔴 CRÍTICA |
| fetch_posts.php | 1 | 🔴 CRÍTICA |
| fetch_comments.php | 1 | 🔴 CRÍTICA |
| post.php | 2 | 🟠 ALTA |
| profile.php | 5 | 🟠 ALTA |
| edit_post.php | 3 | 🟠 ALTA |
| delete_post.php | 4 | 🟠 ALTA |
| create_post.php | 1 | 🟠 ALTA |
| add_comment.php | 2 | 🟠 ALTA |
| delete_comment.php | 2 | 🟠 ALTA |
| upvote_post.php | 3 | 🟡 MÉDIA |
| follow_handler.php | 5 | 🟡 MÉDIA |
| notifications.php | 3 | 🟡 MÉDIA |
| edit_profile.php | 4 | 🟡 MÉDIA |
| admin/*.php | 10+ | 🟡 MÉDIA |
| includes/header.php | 1 | 🟢 BAIXA |

**Exemplo de correção:**
```php
// ❌ ANTES (SQL Injection)
$query = pg_query($dbconn, "SELECT * FROM posts WHERE id = $post_id");

// ✅ DEPOIS (Seguro)
$query = pg_query_params($dbconn, "SELECT * FROM posts WHERE id = $1", [$post_id]);
```

---

### Fase 2: Refatorar Autenticação (Semana 2)
**⏰ Duração: 3-4 dias**

#### 2.1. Criar gerenciador de sessões seguro
```php
// src/Security/SessionManager.php
<?php
namespace App\Security;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurações seguras de sessão
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1); // HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            
            session_start();
        }
    }
    
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
    
    public static function destroy(): void
    {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
}
```

#### 2.2. Implementar serviço de autenticação
```php
// src/Auth/AuthService.php
<?php
namespace App\Auth;

use App\Database\Connection;
use App\Security\{SessionManager, RateLimiter, InputSanitizer};

class AuthService
{
    private Connection $db;
    private RateLimiter $rateLimiter;
    
    public function __construct(Connection $db, RateLimiter $rateLimiter)
    {
        $this->db = $db;
        $this->rateLimiter = $rateLimiter;
    }
    
    public function attempt(string $email, string $password, bool $remember = false): array
    {
        // Rate limiting
        $key = 'login:' . $email;
        if ($this->rateLimiter->tooManyAttempts($key)) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas. Tente novamente em alguns minutos.'
            ];
        }
        
        $email = InputSanitizer::sanitizeEmail($email);
        
        if (!$email) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, username, password_hash, is_admin, is_banned, locked_until 
             FROM users WHERE email = $1",
            [$email]
        );
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->rateLimiter->hit($key);
            
            if ($user) {
                $this->incrementFailedAttempts($user['id']);
            }
            
            return ['success' => false, 'message' => 'Credenciais inválidas'];
        }
        
        // Verificar se conta está bloqueada
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return [
                'success' => false,
                'message' => 'Conta temporariamente bloqueada'
            ];
        }
        
        // Verificar se está banido
        if ($user['is_banned'] === 't') {
            return ['success' => false, 'message' => 'Conta banida'];
        }
        
        // Login bem sucedido
        $this->resetFailedAttempts($user['id']);
        $this->createSession($user);
        
        if ($remember) {
            $this->createRememberToken($user['id']);
        }
        
        // Regenerar session ID após login
        SessionManager::regenerate();
        
        return ['success' => true, 'user' => $user];
    }
    
    private function createSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'] === 't';
        $_SESSION['last_activity'] = time();
    }
    
    private function incrementFailedAttempts(int $userId): void
    {
        $this->db->execute(
            "UPDATE users SET 
                failed_login_attempts = failed_login_attempts + 1,
                locked_until = CASE 
                    WHEN failed_login_attempts >= 4 THEN NOW() + INTERVAL '15 minutes'
                    ELSE NULL 
                END
             WHERE id = $1",
            [$userId]
        );
    }
    
    private function resetFailedAttempts(int $userId): void
    {
        $this->db->execute(
            "UPDATE users SET 
                failed_login_attempts = 0, 
                locked_until = NULL,
                last_login_at = NOW(),
                last_login_ip = $2
             WHERE id = $1",
            [$userId, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    }
    
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $this->db->execute(
            "UPDATE users SET remember_token = $1, token_expires = $2 WHERE id = $3",
            [$hashedToken, $expires, $userId]
        );
        
        // Cookie seguro
        setcookie(
            'remember_token',
            $token,
            [
                'expires' => strtotime('+30 days'),
                'path' => '/',
                'secure' => true,     // HTTPS only
                'httponly' => true,   // Não acessível via JS
                'samesite' => 'Strict'
            ]
        );
    }
}
```

#### 2.3. Adicionar CSRF em todos os formulários

**Arquivos a modificar:**
- login.php
- register.php
- create_post.php
- edit_post.php
- edit_profile.php
- forgot_password.php
- reset_password.php
- delete_account_confirmation.php
- Todos os forms em admin/

---

### Fase 3: Separação de Camadas (Semana 2-3)
**⏰ Duração: 5-7 dias**

#### 3.1. Criar Entities
```php
// src/Domain/User/User.php
<?php
namespace App\Domain\User;

class User
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private bool $isAdmin;
    private bool $isBanned;
    private ?\DateTime $createdAt;
    
    // Getters e setters...
    
    public function ban(): void
    {
        $this->isBanned = true;
    }
    
    public function unban(): void
    {
        $this->isBanned = false;
    }
    
    public function promoteToAdmin(): void
    {
        $this->isAdmin = true;
    }
}
```

#### 3.2. Criar Repositories
```php
// src/Repository/PostgresUserRepository.php
<?php
namespace App\Repository;

use App\Domain\User\{User, UserRepository};
use App\Database\Connection;

class PostgresUserRepository implements UserRepository
{
    private Connection $db;
    
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    
    public function findById(int $id): ?User
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = $1",
            [$id]
        );
        
        return $data ? $this->hydrate($data) : null;
    }
    
    public function findByEmail(string $email): ?User
    {
        $data = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = $1",
            [$email]
        );
        
        return $data ? $this->hydrate($data) : null;
    }
    
    public function save(User $user): int
    {
        if ($user->getId()) {
            return $this->update($user);
        }
        return $this->insert($user);
    }
    
    private function hydrate(array $data): User
    {
        // Mapear array para objeto User
    }
}
```

#### 3.3. Criar Services
```php
// src/Service/PostService.php
<?php
namespace App\Service;

use App\Domain\Post\PostRepository;
use App\Domain\Notification\NotificationRepository;
use App\Security\InputSanitizer;

class PostService
{
    private PostRepository $postRepo;
    private NotificationRepository $notificationRepo;
    
    public function __construct(
        PostRepository $postRepo,
        NotificationRepository $notificationRepo
    ) {
        $this->postRepo = $postRepo;
        $this->notificationRepo = $notificationRepo;
    }
    
    public function create(array $data): int
    {
        $post = new Post();
        $post->setTitle(InputSanitizer::sanitizeString($data['title']));
        $post->setContent(InputSanitizer::sanitizeMarkdown($data['content']));
        $post->setUserId($data['user_id']);
        
        $postId = $this->postRepo->save($post);
        
        // Notificar seguidores
        $this->notifyFollowers($data['user_id'], $postId);
        
        return $postId;
    }
    
    public function delete(int $postId, int $userId, bool $isAdmin = false): bool
    {
        $post = $this->postRepo->findById($postId);
        
        if (!$post) {
            return false;
        }
        
        // Verificar autorização
        if ($post->getUserId() !== $userId && !$isAdmin) {
            throw new UnauthorizedException('Não autorizado');
        }
        
        return $this->postRepo->delete($postId);
    }
}
```

---

### Fase 4: Separar Views (Semana 3)
**⏰ Duração: 3-4 dias**

#### 4.1. Criar layout base
```php
// resources/views/layouts/main.php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <title><?= htmlspecialchars($title ?? 'Kelps Blog') ?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <main class="container">
        <?= $content ?>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>
    
    <script src="/js/app.js"></script>
</body>
</html>
```

#### 4.2. Migrar views existentes
- Extrair HTML dos arquivos PHP
- Criar templates em resources/views/
- Usar componentes reusáveis

---

### Fase 5: Front Controller e Rotas (Semana 3-4)
**⏰ Duração: 3-4 dias**

#### 5.1. Criar Front Controller
```php
// public/index.php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Security\SessionManager;

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Iniciar sessão segura
SessionManager::start();

// Iniciar aplicação
$app = new Application();
$app->run();
```

#### 5.2. Criar Router
```php
// src/Core/Router.php
<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function dispatch(string $method, string $uri): void
    {
        // Implementar matching de rotas
    }
}
```

#### 5.3. Definir rotas
```php
// config/routes.php
<?php
use App\Controller\{
    AuthController,
    PostController,
    CommentController,
    ProfileController,
    UpvoteController,
    FollowController
};

return function($router) {
    // Auth
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/register', [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/logout', [AuthController::class, 'logout']);
    
    // Posts
    $router->get('/', [PostController::class, 'index']);
    $router->get('/post/{id}', [PostController::class, 'show']);
    $router->get('/create-post', [PostController::class, 'create']);
    $router->post('/create-post', [PostController::class, 'store']);
    $router->get('/edit-post/{id}', [PostController::class, 'edit']);
    $router->post('/edit-post/{id}', [PostController::class, 'update']);
    $router->post('/delete-post/{id}', [PostController::class, 'delete']);
    
    // API (JSON)
    $router->post('/api/posts/{id}/upvote', [UpvoteController::class, 'toggle']);
    $router->post('/api/posts/{id}/comments', [CommentController::class, 'store']);
    $router->get('/api/posts/{id}/comments', [CommentController::class, 'index']);
    
    // Profile
    $router->get('/profile/{id}', [ProfileController::class, 'show']);
    $router->get('/edit-profile', [ProfileController::class, 'edit']);
    $router->post('/edit-profile', [ProfileController::class, 'update']);
    
    // Follow
    $router->post('/api/users/{id}/follow', [FollowController::class, 'toggle']);
    
    // Admin (prefix /admin)
    $router->group('/admin', function($router) {
        $router->get('/', [DashboardController::class, 'index']);
        // ... outras rotas admin
    });
};
```

---

### Fase 6: Implementar API Segura (Semana 4)
**⏰ Duração: 2-3 dias**

#### 6.1. Middleware de API
```php
// src/Http/Middleware/JsonMiddleware.php
<?php
namespace App\Http\Middleware;

class JsonMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Verificar Content-Type
        if ($request->isJson()) {
            $data = json_decode($request->getBody(), true);
            $request->setJsonData($data ?? []);
        }
        
        $response = $next($request);
        
        // Adicionar headers de segurança
        $response->addHeader('Content-Type', 'application/json; charset=utf-8');
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        
        return $response;
    }
}
```

#### 6.2. Endpoints API
```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/logout

GET  /api/posts
GET  /api/posts/{id}
POST /api/posts
PUT  /api/posts/{id}
DELETE /api/posts/{id}

GET  /api/posts/{id}/comments
POST /api/posts/{id}/comments
DELETE /api/comments/{id}

POST /api/posts/{id}/upvote
DELETE /api/posts/{id}/upvote

GET  /api/users/{id}
PUT  /api/users/{id}
POST /api/users/{id}/follow
DELETE /api/users/{id}/follow

GET  /api/notifications
PUT  /api/notifications/{id}/read
```

---

### Fase 7: Testes e Deploy (Semana 4-5)
**⏰ Duração: 4-5 dias**

#### 7.1. Configurar PHPUnit
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_NAME" value="kelps_test"/>
    </php>
</phpunit>
```

#### 7.2. Testes de segurança
```php
// tests/Feature/Security/CsrfTest.php
<?php
namespace Tests\Feature\Security;

use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    public function test_post_without_csrf_token_is_rejected(): void
    {
        $response = $this->post('/create-post', [
            'title' => 'Test',
            'content' => 'Content'
        ]);
        
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    public function test_post_with_invalid_csrf_token_is_rejected(): void
    {
        $response = $this->post('/create-post', [
            'title' => 'Test',
            'content' => 'Content',
            'csrf_token' => 'invalid'
        ]);
        
        $this->assertEquals(403, $response->getStatusCode());
    }
}
```

#### 7.3. Configuração Railway
```yaml
# railway.json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t public",
    "healthcheckPath": "/health",
    "healthcheckTimeout": 300
  }
}
```

```php
// public/health.php (endpoint de health check)
<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../src/Database/Connection.php';
    $db = App\Database\Connection::getInstance();
    
    echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['status' => 'unhealthy', 'error' => 'Database connection failed']);
}
```

---

## ✅ Checklist de Migração

### Fase 0 - Ações Imediatas
- [ ] Remover/proteger make_admin.php
- [ ] Desabilitar display_errors
- [ ] Criar .htaccess de proteção
- [ ] Criar pasta storage/logs

### Fase 1 - Segurança
- [ ] Criar estrutura de pastas
- [ ] Implementar Connection.php
- [ ] Implementar Csrf.php
- [ ] Implementar InputSanitizer.php
- [ ] Implementar RateLimiter.php
- [ ] Corrigir SQL Injection em TODOS os arquivos (20+)
- [ ] Criar migration de segurança

### Fase 2 - Autenticação
- [ ] Implementar SessionManager.php
- [ ] Implementar AuthService.php
- [ ] Adicionar CSRF em todos os forms
- [ ] Implementar bloqueio de conta
- [ ] Implementar remember token seguro

### Fase 3 - Camadas
- [ ] Criar entities
- [ ] Criar repositories
- [ ] Criar services
- [ ] Implementar validators

### Fase 4 - Views
- [ ] Criar layouts
- [ ] Criar componentes
- [ ] Migrar views existentes
- [ ] Implementar escaping consistente

### Fase 5 - Rotas
- [ ] Implementar Router
- [ ] Criar front controller
- [ ] Migrar todas as rotas
- [ ] Implementar middlewares

### Fase 6 - API
- [ ] Criar endpoints REST
- [ ] Implementar autenticação API
- [ ] Documentar API

### Fase 7 - Deploy
- [ ] Configurar testes
- [ ] Rodar testes de segurança
- [ ] Configurar Railway
- [ ] Deploy em staging
- [ ] Deploy em produção

---

## 📌 Prioridades de Implementação

1. **URGENTE (Fazer HOJE):** Fase 0 completa
2. **CRÍTICO (Semana 1):** Correção de SQL Injection + CSRF
3. **ALTO (Semana 2):** Autenticação segura + Rate Limiting
4. **MÉDIO (Semana 3-4):** Separação de camadas + Views
5. **NORMAL (Semana 4-5):** Router + API + Testes

---

## 🔗 Recursos

- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PostgreSQL Prepared Statements](https://www.php.net/manual/en/function.pg-query-params.php)
- [Railway PHP Deployment](https://docs.railway.app/guides/php)
