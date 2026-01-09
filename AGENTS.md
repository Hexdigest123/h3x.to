# Repository Guidelines

## Project Structure & Module Organization
- `src/public/`: Front controller (`index.php`) and static assets served by Nginx.
- `src/app/`: Custom PHP 8.2 MVC stack; `Core/` holds router, base controller, database layer; `Controllers/` handle routes; `Models/` house data logic; `Views/` contains layouts and components.
- `src/config/config.php`: Environment toggles (`APP_ENV`), base URL, timezone, and database credentials (expects `DB_*` env vars; falls back to `db`/`mvc_user`/`mvc_password`/`mvc_db`).
- `docker/`: Runtime configuration (e.g., Nginx `default.conf`); `Dockerfile` and `docker-compose.yml` define PHP-FPM, Nginx, and Postgres services.
- `db/`: Database assets placeholder; add migrations/seed files here.

## Build, Test, and Development Commands
- `make init`: Build and start the stack; app on `http://localhost:2001`, phpPgAdmin on `http://localhost:8081` (if enabled).
- `make up` / `make down` / `make restart`: Control containers; use `make clean` to remove volumes when resetting the DB.
- `make logs`, `make logs-app`, `make logs-nginx`: Tail service logs for debugging.
- `make shell`: Enter the PHP container (run Composer, lint, or ad-hoc scripts inside).
- `make db-shell`: Open `psql` against the Postgres service.
- Composer is available in the app container; run `docker compose exec app composer install` or `composer require vendor/package` and commit `composer.json`/`composer.lock`.

## Coding Style & Naming Conventions
- Target PSR-12: 4-space indentation, descriptive camelCase for methods/variables, PascalCase for classes. Keep files under the `App\` namespace and mirror folders (`App\Controllers\HomeController` -> `src/app/Controllers/HomeController.php`).
- Views stay in `src/app/Views`; prefer reusable components under `Views/components`.
- Keep configuration out of version control; use `.env` for overrides and ensure DB credentials match those in `docker-compose.yml`.

## Testing Guidelines
- No automated suite yet; add regression coverage as you touch code. Prefer PHPUnit with files under `tests/` named `*Test.php`, run inside the app container.
- At minimum, lint PHP files (`docker compose exec app php -l path/to/file.php`) and verify key routes load from `http://localhost:2001`.

## Commit & Pull Request Guidelines
- Follow the existing `<TYPE>: <subject>` pattern (`feat: ...`, `FIX: ...`, `ADD: ...`), using concise, imperative subjects.
- For PRs, include: summary of changes, screenshots/GIFs for UI updates, manual test steps (commands and URLs), impacted routes/components, and linked issue/Task ID if available.
- Keep PRs small and focused; note any config changes (`DB_*`, ports, Nginx) so reviewers can re-create the environment quickly.
