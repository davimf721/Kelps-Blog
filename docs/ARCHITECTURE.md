# 🏗️ Kelps Blog - Nova Arquitetura Clean Code

## 📋 Visão Geral

Este documento descreve a nova arquitetura proposta para o Kelps Blog, seguindo princípios de **Clean Architecture**, **Clean Code** e **Segurança** adequados para uma rede social.

---

## 🚨 Resumo de Vulnerabilidades Críticas (Ação Imediata)

| Severidade | Quantidade | Descrição |
|------------|------------|-----------|
| 🔴 CRÍTICO | 20+ | SQL Injection em quase todos os arquivos |
| 🔴 CRÍTICO | 10+ | Ausência total de proteção CSRF |
| 🔴 CRÍTICO | 1 | `make_admin.php` permite promocão de admin sem autenticação |
| 🟠 ALTO | 5+ | Vulnerabilidades XSS (Cross-Site Scripting) |
| 🟠 ALTO | 3+ | Problemas de gerenciamento de sessão |
| 🟡 MÉDIO | 5+ | Rate limiting ausente, falta de validação |

**Score de Segurança Atual: 3.5/10** ⚠️

---

## 📁 Nova Estrutura de Diretórios

```
Kelps-Blog/
├── config/                          # 🔧 Configurações
│   ├── app.php                      # Configurações da aplicação
│   ├── database.php                 # Configurações do banco
│   ├── security.php                 # Configurações de segurança
│   └── railway.php                  # Configurações específicas Railway
│
├── src/                             # 📦 Código-fonte principal
│   ├── Core/                        # 🎯 Núcleo da aplicação
│   │   ├── Application.php          # Bootstrap da aplicação
│   │   ├── Router.php               # Roteador de requisições
│   │   ├── Container.php            # Dependency Injection Container
│   │   └── Exception/               # Exceções customizadas
│   │       ├── ValidationException.php
│   │       ├── AuthorizationException.php
│   │       └── NotFoundException.php
│   │
│   ├── Database/                    # 🗄️ Camada de banco de dados
│   │   ├── Connection.php           # Conexão segura PostgreSQL
│   │   ├── QueryBuilder.php         # Query builder com prepared statements
│   │   ├── Transaction.php          # Gerenciamento de transações
│   │   └── Migration/               # Sistema de migrações
│   │       ├── MigrationRunner.php
│   │       └── migrations/          # Arquivos de migração
│   │
│   ├── Security/                    # 🔒 Camada de segurança
│   │   ├── Csrf.php                 # Proteção CSRF
│   │   ├── RateLimiter.php          # Rate limiting
│   │   ├── InputSanitizer.php       # Sanitização de inputs
│   │   ├── XssProtection.php        # Proteção XSS
│   │   ├── SessionManager.php       # Gerenciamento seguro de sessões
│   │   └── PasswordPolicy.php       # Política de senhas
│   │
│   ├── Auth/                        # 🔑 Autenticação e Autorização
│   │   ├── AuthService.php          # Serviço de autenticação
│   │   ├── AuthMiddleware.php       # Middleware de autenticação
│   │   ├── AdminMiddleware.php      # Middleware de admin
│   │   ├── BannedMiddleware.php     # Middleware de usuários banidos
│   │   └── RememberToken.php        # Token de "lembrar-me"
│   │
│   ├── Domain/                      # 💼 Camada de Domínio (Entities)
│   │   ├── User/
│   │   │   ├── User.php             # Entity User
│   │   │   ├── UserProfile.php      # Entity Profile
│   │   │   └── UserRepository.php   # Interface do repositório
│   │   ├── Post/
│   │   │   ├── Post.php             # Entity Post
│   │   │   └── PostRepository.php   # Interface do repositório
│   │   ├── Comment/
│   │   │   ├── Comment.php          # Entity Comment
│   │   │   └── CommentRepository.php
│   │   ├── Notification/
│   │   │   ├── Notification.php     # Entity Notification
│   │   │   └── NotificationRepository.php
│   │   └── Follow/
│   │       ├── Follow.php           # Entity Follow
│   │       └── FollowRepository.php
│   │
│   ├── Repository/                  # 🗃️ Implementação dos Repositórios
│   │   ├── PostgresUserRepository.php
│   │   ├── PostgresPostRepository.php
│   │   ├── PostgresCommentRepository.php
│   │   ├── PostgresNotificationRepository.php
│   │   └── PostgresFollowRepository.php
│   │
│   ├── Service/                     # ⚙️ Camada de Serviços (Business Logic)
│   │   ├── UserService.php          # Regras de negócio de usuários
│   │   ├── PostService.php          # Regras de negócio de posts
│   │   ├── CommentService.php       # Regras de negócio de comentários
│   │   ├── UpvoteService.php        # Regras de negócio de upvotes
│   │   ├── FollowService.php        # Regras de negócio de seguidores
│   │   ├── NotificationService.php  # Regras de negócio de notificações
│   │   └── MarkdownService.php      # Processamento Markdown
│   │
│   ├── Http/                        # 🌐 Camada HTTP
│   │   ├── Request.php              # Wrapper de requisição
│   │   ├── Response.php             # Wrapper de resposta
│   │   ├── JsonResponse.php         # Respostas JSON
│   │   └── Middleware/              # Middlewares
│   │       ├── MiddlewareInterface.php
│   │       ├── CsrfMiddleware.php
│   │       ├── RateLimitMiddleware.php
│   │       └── JsonMiddleware.php
│   │
│   ├── Controller/                  # 🎮 Controladores
│   │   ├── BaseController.php       # Controller base
│   │   ├── AuthController.php       # Login, registro, logout
│   │   ├── PostController.php       # CRUD de posts
│   │   ├── CommentController.php    # CRUD de comentários
│   │   ├── ProfileController.php    # Perfil de usuário
│   │   ├── UpvoteController.php     # Upvotes
│   │   ├── FollowController.php     # Seguidores
│   │   ├── NotificationController.php
│   │   └── Admin/                   # Controllers admin
│   │       ├── DashboardController.php
│   │       ├── UserAdminController.php
│   │       ├── PostAdminController.php
│   │       └── CommentAdminController.php
│   │
│   └── Validator/                   # ✅ Validadores
│       ├── ValidatorInterface.php
│       ├── UserValidator.php
│       ├── PostValidator.php
│       └── CommentValidator.php
│
├── public/                          # 🌍 Arquivos públicos (único ponto de entrada)
│   ├── index.php                    # Front controller
│   ├── css/
│   │   ├── app.css                  # CSS compilado
│   │   └── auth.css
│   ├── js/
│   │   ├── app.js                   # JS compilado
│   │   └── modules/
│   │       ├── posts.js
│   │       ├── comments.js
│   │       ├── upvotes.js
│   │       └── notifications.js
│   └── images/
│       └── avatars/
│
├── resources/                       # 📄 Recursos e Views
│   ├── views/                       # Templates
│   │   ├── layouts/
│   │   │   ├── main.php             # Layout principal
│   │   │   └── admin.php            # Layout admin
│   │   ├── components/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   ├── navbar.php
│   │   │   ├── post-card.php
│   │   │   └── comment.php
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── forgot-password.php
│   │   │   └── reset-password.php
│   │   ├── posts/
│   │   │   ├── index.php
│   │   │   ├── show.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   ├── profile/
│   │   │   ├── show.php
│   │   │   └── edit.php
│   │   ├── admin/
│   │   │   ├── dashboard.php
│   │   │   ├── users/
│   │   │   ├── posts/
│   │   │   └── comments/
│   │   └── errors/
│   │       ├── 404.php
│   │       ├── 403.php
│   │       └── 500.php
│   └── lang/                        # Internacionalização
│       └── pt-BR/
│           └── messages.php
│
├── storage/                         # 📂 Armazenamento
│   ├── logs/                        # Logs da aplicação
│   │   └── .gitignore
│   ├── cache/                       # Cache
│   │   └── .gitignore
│   └── uploads/                     # Uploads de usuários
│       └── .gitignore
│
├── database/                        # 🗄️ Database
│   ├── migrations/                  # Migrações
│   │   ├── 001_create_users_table.sql
│   │   ├── 002_create_posts_table.sql
│   │   ├── 003_create_comments_table.sql
│   │   ├── 004_create_upvotes_table.sql
│   │   ├── 005_create_followers_table.sql
│   │   ├── 006_create_notifications_table.sql
│   │   ├── 007_create_user_profiles_table.sql
│   │   ├── 008_add_security_fields.sql
│   │   └── 009_add_rate_limit_table.sql
│   └── seeds/                       # Seeds para desenvolvimento
│       └── demo_data.sql
│
├── tests/                           # 🧪 Testes
│   ├── Unit/
│   │   ├── Service/
│   │   └── Validator/
│   ├── Integration/
│   │   └── Repository/
│   └── Feature/
│       └── Http/
│
├── bin/                             # 🔧 CLI Scripts
│   ├── migrate.php                  # Rodar migrações
│   ├── seed.php                     # Rodar seeds
│   └── console.php                  # CLI principal
│
├── docs/                            # 📚 Documentação
│   ├── ARCHITECTURE.md              # Este arquivo
│   ├── SECURITY.md                  # Guia de segurança
│   ├── API.md                       # Documentação da API
│   └── DEPLOYMENT.md                # Guia de deploy Railway
│
├── .env.example                     # Exemplo de variáveis de ambiente
├── .gitignore
├── composer.json                    # Dependências
├── phpunit.xml                      # Configuração de testes
└── README.md
```

---

## 🔒 Camada de Segurança (Detalhes)

### 1. Proteção CSRF

```php
// src/Security/Csrf.php
<?php
namespace App\Security;

class Csrf
{
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_LENGTH = 32;
    
    public static function generateToken(): string
    {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION[self::TOKEN_NAME];
    }
    
    public static function validateToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }
    
    public static function regenerateToken(): string
    {
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        return $_SESSION[self::TOKEN_NAME];
    }
    
    public static function getInputField(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8')
        );
    }
}
```

### 2. Rate Limiter

```php
// src/Security/RateLimiter.php
<?php
namespace App\Security;

use App\Database\Connection;

class RateLimiter
{
    private Connection $db;
    private int $maxAttempts;
    private int $decayMinutes;
    
    public function __construct(Connection $db, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->db = $db;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }
    
    public function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->maxAttempts;
    }
    
    public function hit(string $key): int
    {
        $ip = $this->getClientIp();
        $now = time();
        $decayTime = $now - ($this->decayMinutes * 60);
        
        // Limpa tentativas antigas
        $this->db->execute(
            "DELETE FROM rate_limits WHERE key = $1 AND created_at < to_timestamp($2)",
            [$key . ':' . $ip, $decayTime]
        );
        
        // Insere nova tentativa
        $this->db->execute(
            "INSERT INTO rate_limits (key, ip, created_at) VALUES ($1, $2, NOW())",
            [$key . ':' . $ip, $ip]
        );
        
        return $this->attempts($key);
    }
    
    private function attempts(string $key): int
    {
        $ip = $this->getClientIp();
        $decayTime = time() - ($this->decayMinutes * 60);
        
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) FROM rate_limits WHERE key = $1 AND created_at > to_timestamp($2)",
            [$key . ':' . $ip, $decayTime]
        );
        
        return (int) $result['count'];
    }
    
    private function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        
        return '127.0.0.1';
    }
}
```

### 3. Input Sanitizer

```php
// src/Security/InputSanitizer.php
<?php
namespace App\Security;

class InputSanitizer
{
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeEmail(string $email): ?string
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
    
    public static function sanitizeInt($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    public static function sanitizeUsername(string $username): ?string
    {
        $username = trim($username);
        
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            return null;
        }
        
        return $username;
    }
    
    public static function sanitizeMarkdown(string $content): string
    {
        // Remove scripts e tags perigosas
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/on\w+="[^"]*"/i', '', $content);
        $content = preg_replace('/javascript:/i', '', $content);
        
        return $content;
    }
}
```

---

## 🗄️ Conexão Segura com PostgreSQL

```php
// src/Database/Connection.php
<?php
namespace App\Database;

use App\Core\Exception\DatabaseException;

class Connection
{
    private static ?Connection $instance = null;
    private $connection;
    
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        
        $connString = sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s sslmode=%s",
            $config['host'],
            $config['port'],
            $config['database'],
            $config['username'],
            $config['password'],
            $config['sslmode'] ?? 'require'
        );
        
        $this->connection = pg_connect($connString);
        
        if (!$this->connection) {
            throw new DatabaseException('Falha na conexão com o banco de dados');
        }
        
        pg_set_client_encoding($this->connection, 'UTF8');
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Executa uma query com prepared statements (SEMPRE usar este método)
     */
    public function execute(string $query, array $params = []): mixed
    {
        $result = pg_query_params($this->connection, $query, $params);
        
        if ($result === false) {
            throw new DatabaseException(pg_last_error($this->connection));
        }
        
        return $result;
    }
    
    public function fetchAll(string $query, array $params = []): array
    {
        $result = $this->execute($query, $params);
        return pg_fetch_all($result) ?: [];
    }
    
    public function fetchOne(string $query, array $params = []): ?array
    {
        $result = $this->execute($query, $params);
        $row = pg_fetch_assoc($result);
        return $row ?: null;
    }
    
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(
            fn($i) => '$' . ($i + 1),
            array_keys(array_values($data))
        ));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";
        
        $result = $this->execute($query, array_values($data));
        $row = pg_fetch_assoc($result);
        
        return (int) $row['id'];
    }
    
    public function beginTransaction(): void
    {
        pg_query($this->connection, 'BEGIN');
    }
    
    public function commit(): void
    {
        pg_query($this->connection, 'COMMIT');
    }
    
    public function rollback(): void
    {
        pg_query($this->connection, 'ROLLBACK');
    }
    
    // Impedir clonagem
    private function __clone() {}
    
    // Impedir desserialização
    public function __wakeup()
    {
        throw new DatabaseException('Cannot unserialize singleton');
    }
}
```

---

## 🎮 Exemplo de Controller

```php
// src/Controller/PostController.php
<?php
namespace App\Controller;

use App\Service\PostService;
use App\Security\Csrf;
use App\Security\InputSanitizer;
use App\Http\JsonResponse;
use App\Validator\PostValidator;
use App\Auth\AuthMiddleware;

class PostController extends BaseController
{
    private PostService $postService;
    private PostValidator $validator;
    
    public function __construct(PostService $postService, PostValidator $validator)
    {
        $this->postService = $postService;
        $this->validator = $validator;
    }
    
    public function index(): void
    {
        $page = InputSanitizer::sanitizeInt($_GET['page'] ?? 1);
        $posts = $this->postService->getPaginated($page, 10);
        
        $this->render('posts/index', ['posts' => $posts]);
    }
    
    public function show(int $id): void
    {
        $post = $this->postService->findById($id);
        
        if (!$post) {
            $this->notFound();
            return;
        }
        
        $this->render('posts/show', ['post' => $post]);
    }
    
    #[AuthMiddleware]
    public function create(): void
    {
        if ($this->isPost()) {
            $this->store();
            return;
        }
        
        $this->render('posts/create', ['csrf' => Csrf::getInputField()]);
    }
    
    #[AuthMiddleware]
    private function store(): void
    {
        // Validar CSRF
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            $this->forbidden('Token CSRF inválido');
            return;
        }
        
        $data = [
            'title' => InputSanitizer::sanitizeString($_POST['title'] ?? ''),
            'content' => InputSanitizer::sanitizeMarkdown($_POST['content'] ?? ''),
            'user_id' => $this->getCurrentUserId(),
        ];
        
        // Validar dados
        $errors = $this->validator->validate($data);
        
        if (!empty($errors)) {
            $this->render('posts/create', [
                'errors' => $errors,
                'old' => $data,
                'csrf' => Csrf::getInputField()
            ]);
            return;
        }
        
        // Criar post
        $postId = $this->postService->create($data);
        
        // Regenerar CSRF token após ação bem sucedida
        Csrf::regenerateToken();
        
        $this->redirect("/post/{$postId}");
    }
    
    #[AuthMiddleware]
    public function delete(int $id): void
    {
        // Validar CSRF
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            JsonResponse::error('Token CSRF inválido', 403);
            return;
        }
        
        $post = $this->postService->findById($id);
        
        if (!$post) {
            JsonResponse::error('Post não encontrado', 404);
            return;
        }
        
        // Verificar autorização
        if ($post['user_id'] !== $this->getCurrentUserId() && !$this->isAdmin()) {
            JsonResponse::error('Não autorizado', 403);
            return;
        }
        
        $this->postService->delete($id);
        
        JsonResponse::success(['message' => 'Post deletado com sucesso']);
    }
}
```

---

## ⚙️ Configuração para Railway

```php
// config/railway.php
<?php
return [
    'database' => [
        'host' => getenv('PGHOST') ?: getenv('DB_HOST'),
        'port' => getenv('PGPORT') ?: getenv('DB_PORT') ?: '5432',
        'database' => getenv('PGDATABASE') ?: getenv('DB_NAME'),
        'username' => getenv('PGUSER') ?: getenv('DB_USER'),
        'password' => getenv('PGPASSWORD') ?: getenv('DB_PASSWORD'),
        'sslmode' => 'require', // Railway requer SSL
    ],
    
    'app' => [
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => getenv('APP_DEBUG') === 'true',
        'url' => getenv('RAILWAY_PUBLIC_DOMAIN') 
            ? 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN')
            : getenv('APP_URL'),
    ],
    
    'security' => [
        'session_lifetime' => 120, // minutos
        'remember_lifetime' => 43200, // 30 dias em minutos
        'rate_limit' => [
            'login' => ['attempts' => 5, 'decay' => 15], // 5 tentativas a cada 15 min
            'api' => ['attempts' => 60, 'decay' => 1],   // 60 req/min
            'comment' => ['attempts' => 10, 'decay' => 1], // 10 comments/min
        ],
    ],
];
```

---

## 📊 Migração do Banco de Dados

```sql
-- database/migrations/008_add_security_fields.sql
-- Campos de segurança adicionais

-- Tabela de rate limits
CREATE TABLE IF NOT EXISTS rate_limits (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_rate_limits_key ON rate_limits(key);
CREATE INDEX idx_rate_limits_created ON rate_limits(created_at);

-- Campos de segurança na tabela users
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    failed_login_attempts INTEGER DEFAULT 0;
    
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    locked_until TIMESTAMP NULL;
    
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    last_login_at TIMESTAMP NULL;
    
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    last_login_ip VARCHAR(45) NULL;

ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tabela de sessões (para invalidação)
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_user_sessions_user ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_session ON user_sessions(session_id);

-- Tabela de logs de auditoria
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
```

---

## 🚀 Próximos Passos

Ver **MIGRATION_PLAN.md** para o plano detalhado de migração fase por fase.
