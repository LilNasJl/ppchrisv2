# Repository Guidelines

## Project Structure & Module Organization
This is a Laravel 13 HRIS built with Filament 5. Application models, policies, controllers, and domain services live in `app/`. Keep payroll, DTR, leave, loan, and import calculations in `app/Services/`; Filament pages should coordinate UI state rather than duplicate business rules.

Panel-specific code is separated under `app/Filament/`: HR pages use `Pages/` and `Resources/`, while employee self-service, KPI, and SIC/RC features live in `Employee/`, `Kpi/`, and `SicRc/`. Blade templates and frontend sources are in `resources/`; HTTP routes are in `routes/web.php`. Database schema and seed data belong in `database/migrations` and `database/seeders`. Tests are grouped into `tests/Unit` and `tests/Feature`.

## Build, Test, and Development Commands
- `composer run setup`: install dependencies, create `.env`, generate the app key, migrate, and build assets.
- `composer run dev`: run Laravel and Vite together for local development.
- `npm run build`: create production Vite assets.
- `composer test`: clear configuration and run the complete test suite.
- `php artisan test --filter=DtrImport`: run a focused test group while iterating.
- `vendor/bin/pint --test`: check PHP formatting; run `vendor/bin/pint` to fix it.

Run `php artisan optimize:clear` after changing routes, Blade views, panel providers, or configuration.

## Coding Style & Naming Conventions
Follow PSR-12 and `.editorconfig`: UTF-8, LF endings, four-space indentation, and two spaces for YAML. Use StudlyCase for PHP classes, camelCase for methods and properties, snake_case for database columns, timestamped snake_case migration names, and kebab-case Blade filenames. Reuse existing Filament actions, schemas, tables, and shared concerns before introducing new UI abstractions.

## Testing Guidelines
Tests use Pest 4. Name files after the behavior under test, such as `PayrollPeriodLockValidationTest.php`. Add unit tests for calculations and validation rules; use feature tests for routes, persistence, authentication, imports, and cross-panel workflows. Test edge cases around locked payroll periods, overlapping DTR entries, incomplete punches, and role boundaries. SQLite-backed feature tests require the `pdo_sqlite` PHP extension.

## Commit & Pull Request Guidelines
Recent history follows Conventional Commit prefixes, primarily `feat:` and `fix:`. Keep subjects concise and describe one cohesive change, for example `fix: accept incomplete forgot-to-punch imports`.

Pull requests should identify affected panels, summarize behavior and schema changes, list verification commands, and include screenshots for UI work. Call out migrations, scheduler effects, authorization changes, and payroll/DTR calculation impacts explicitly.

## Security & Configuration
Never commit `.env`, credentials, database dumps, private submissions, or uploaded documents. Keep authorization enforced at the panel, page, action, and query levels. Do not edit `vendor/` or generated Vite assets directly.
