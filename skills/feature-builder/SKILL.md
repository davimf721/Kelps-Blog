# Skill: Feature Builder — Kelps Blog

## Quando usar
Ative esta skill quando o usuário pedir para:
- Adicionar uma nova feature ao projeto
- Criar um novo endpoint/página
- Implementar uma nova funcionalidade seguindo a arquitetura

---

## Fluxo obrigatório de implementação

```
1. Migration (se precisar de nova tabela/coluna)
2. Repository   → src/Repository/NomeRepository.php
3. Service      → src/Service/NomeService.php
4. Controller   → src/Controller/NomeController.php
5. Rota         → config/routes.php
6. View         → resources/views/categoria/nome.php
7. (Opcional) Middleware, Validator, JS
```

---

## Templates de cada camada

### Repository
```php
<?php
declare(strict_types=1);
namespace App\Repository;
use App\Database\Connection;

class NomeRepository
{
    public function __construct(private Connection $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM tabela WHERE id = $1',
            [$id]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('tabela', $data);
    }

    public function update(int $id, array $data): void
    {
        $this->db->update('tabela', $data, ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM tabela WHERE id = $1', [$id]);
    }
}
```

### Service
```php
<?php
declare(strict_types=1);
namespace App\Service;
use App\Repository\NomeRepository;
use App\Security\InputSanitizer;
use RuntimeException;

class NomeService
{
    public function __construct(private NomeRepository $repo) {}

    public function create(int $userId, array $data): int
    {
        // 1. Sanitizar inputs
        $campo = InputSanitizer::string($data['campo'] ?? '', 100);

        // 2. Validar regras de negócio
        if (strlen($campo) < 2) {
            throw new RuntimeException('Campo muito curto.');
        }

        // 3. Persistir
        return $this->repo->create([
            'user_id' => $userId,
            'campo'   => $campo,
        ]);
    }
}
```

### Controller (página)
```php
<?php
declare(strict_types=1);
namespace App\Controller;
use App\Service\NomeService;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NomeController extends BaseController
{
    public function __construct(
        Renderer $view,
        private NomeService $service,
    ) {
        parent::__construct($view);
    }

    // GET /rota
    public function index(Request $request, Response $response): Response
    {
        $items = $this->service->getAll();
        return $this->render($response, 'categoria/index', ['items' => $items]);
    }

    // POST /rota
    public function store(Request $request, Response $response): Response
    {
        try {
            $id = $this->service->create($this->userId(), $this->body($request));
            $this->flash('success', 'Criado com sucesso!');
            return $this->redirect($response, "/rota/$id");
        } catch (\RuntimeException $e) {
            return $this->render($response, 'categoria/create', [
                'error' => $e->getMessage(),
                'old'   => $this->body($request),
            ]);
        }
    }
}
```

### Controller (API JSON)
```php
// GET /api/recurso
public function index(Request $request, Response $response): Response
{
    $items = $this->service->getAll();
    return $this->json($response, $items);
}

// POST /api/recurso
public function store(Request $request, Response $response): Response
{
    if (!$this->isLoggedIn()) {
        return $this->json($response, ['error' => 'Login necessário.'], 401);
    }

    try {
        $id = $this->service->create($this->userId(), (array) $request->getParsedBody());
        return $this->json($response, ['id' => $id], 201);
    } catch (\RuntimeException $e) {
        return $this->json($response, ['error' => $e->getMessage()], 422);
    }
}
```

### Rota no routes.php
```php
// Pública
$app->get('/rota',       [NomeController::class, 'index']);
$app->get('/rota/{id}',  [NomeController::class, 'show']);

// Autenticada
$app->group('', function ($group) {
    $group->get('/rota/create',  [NomeController::class, 'create']);
    $group->post('/rota',        [NomeController::class, 'store']);
    $group->post('/rota/{id}/delete', [NomeController::class, 'destroy']);
})->add(AuthMiddleware::class);

// API
$app->group('/api', function ($group) {
    $group->get('/recurso',      [NomeController::class, 'apiIndex']);
    $group->post('/recurso',     [NomeController::class, 'apiStore']);
});
```

### Registrar no container (config/container.php)
```php
// Adicionar:
NomeRepository::class => autowire(),
NomeService::class    => autowire(),
```

### View básica
```php
<?php $pageTitle = 'Título da Página — Kelps Blog'; ?>

<div class="page-header">
    <h1><i class="fas fa-icon"></i> Título</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
<?php endif; ?>

<form method="POST" action="/rota">
    <?= $csrf ?>
    <!-- campos -->
    <button type="submit" class="btn-primary">Salvar</button>
</form>
```

---

## Checklist antes de finalizar a feature

- [ ] Migration criada e testada (`IF NOT EXISTS`)
- [ ] Repository só faz queries, sem lógica de negócio
- [ ] Service valida inputs antes de persistir
- [ ] Controller usa `$this->render()`, `$this->redirect()` ou `$this->json()`
- [ ] Rota adicionada em `config/routes.php`
- [ ] Componente registrado em `config/container.php`
- [ ] View usa `htmlspecialchars()` em todo output dinâmico
- [ ] Formulários têm `<?= $csrf ?>`
- [ ] Rotas que requerem login têm `->add(AuthMiddleware::class)`
- [ ] Rotas admin têm ambos: `AdminMiddleware` + `AuthMiddleware`

---

## Exemplos de features existentes para referência

| Feature | Controller | Service | Repository |
|---|---|---|---|
| Posts | `PostController` | `PostService` | `PostRepository` |
| Autenticação | `AuthController` | `AuthService` | `UserRepository` |
| Perfil | `ProfileController` | `ProfileService` | `UserRepository` |
| Follow | `ProfileController` | `FollowService` | `FollowRepository` |
| Notificações | `NotificationController` | — | `NotificationRepository` |
