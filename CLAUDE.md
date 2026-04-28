# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install

# Run dev server (port 8080)
composer start
# or: php -S 0.0.0.0:8080 -t public

# Run tests
composer test
# or: ./vendor/bin/phpunit --testdox

# Run a single test file
./vendor/bin/phpunit --testdox tests/SomeTest.php

# Apply DB schema (fresh install)
psql -U postgres -f database/schema.sql

# Run pending migrations
php database/migrate.php
```

## Architecture

The project has **two coexisting layers**:

### New architecture (active — use this for new work)
Built on **Slim 4 + PHP-DI**, following MVC:

- **`public/index.php`** — Front controller. Bootstraps Slim, DI container, middlewares, and loads routes.
- **`config/routes.php`** — All route definitions. Routes map to controller methods and apply middleware groups.
- **`config/container.php`** — PHP-DI bindings (services, repositories, renderer).
- **`src/`** — All application classes (PSR-4 namespace `App\`):
  - `Controller/` — Extends `BaseController`. Use `$this->render()`, `$this->json()`, `$this->redirect()`, `$this->flash()`.
  - `Controller/Admin/` — Admin panel controllers (guarded by `AdminMiddleware`).
  - `Repository/` — Data access layer. Each entity (User, Post, Comment, etc.) has its own repository using `Connection`.
  - `Service/` — Business logic (AuthService, PostService, etc.). Injected into controllers via DI.
  - `Middleware/` — `AuthMiddleware`, `AdminMiddleware`, `CsrfMiddleware`, `BannedMiddleware`, `SessionMiddleware`, `ErrorHandler`.
  - `Database/Connection.php` — Singleton PostgreSQL connection. Always use `execute()`, `fetchAll()`, `fetchOne()`, `fetchScalar()`, `insert()`, `update()` — never raw `pg_query` directly.
  - `Security/` — `Csrf`, `SessionManager`, `InputSanitizer`, `RateLimiter`.
  - `View/Renderer.php` — Renders PHP views from `resources/views/`. Auto-selects `layout/main.php` or `layout/admin.php` based on view path prefix.
- **`resources/views/`** — View templates. Layout files in `layout/`, shared components in `components/`, pages grouped by context (`auth/`, `posts/`, `profile/`, `admin/`). Every view receives `$csrf`, `$csrfToken`, `$currentUser`, and `$flash` automatically.

### Legacy layer (do not extend — compatibility only)
- `pages/` — Old procedural PHP pages (still in use for some endpoints).
- `app/` — Old bootstrap, helpers, and security classes.
- `includes/` — Proxy shims that `require` the `app/` equivalents.
- Root-level `admin/*.php` — Old admin pages using `includes/`.

When modifying existing pages in `pages/` or `admin/`, use `pg_query_params($dbconn, ...)` with positional placeholders (`$1`, `$2`, ...). The `$dbconn` global comes from `includes/db_connect.php`.

## Database

- PostgreSQL only. Connection uses `pg_connect` (not PDO).
- Core tables: `users`, `posts`, `comments`, `post_upvotes`, `follows`, `notifications`.
- `posts.upvotes_count` is maintained by a DB trigger on `post_upvotes` — do not update it manually.
- Schema: `database/schema.sql`. Incremental migrations: `database/migrations/`.

## Environment

Configured via `.env` (development) or environment variables (Railway/production):

```
APP_ENV=development
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
```

Railway also provides `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD` — the config file checks both. SSL is required in production (`sslmode=require`).

## Key conventions

- All DB queries must use prepared statements (parameterized) — `$1`, `$2`, etc.
- CSRF protection is global via `CsrfMiddleware`. Views must include `<?= $csrf ?>` in forms or send `X-CSRF-Token` header for AJAX.
- Session is managed via `App\Security\SessionManager` (new code) or `$_SESSION` directly (legacy code).
- Markdown rendering uses `erusev/parsedown`.
- The `Renderer` automatically wraps content in the appropriate layout — pass `'layout' => 'minimal'` in view data to override.
- Logs go to `storage/logs/php_errors.log` in production.
- User uploads go to `public/uploads/`.
