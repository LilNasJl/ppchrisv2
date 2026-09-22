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

## Database Data Protection Requirement (Mandatory)

For all future **changes, updates, new features, database migrations, and system improvements**, existing database data must be preserved.

* **Do not use destructive migration commands** such as:

```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:reset
```

* Do not:
  * drop all tables
  * truncate existing tables
  * clear the entire database
  * delete all existing records
  * recreate the database from scratch
  * remove production or existing development data just to support a new feature

* Before making any database-related change, first **review the existing schema, relationships, records, foreign keys, and dependencies**.

* New features must use **safe and non-destructive migrations** whenever possible.

For example, prefer:

```php
Schema::table('employees', function (Blueprint $table) {
    $table->string('new_field')->nullable();
});
```

instead of recreating the entire table or database.

* When modifying an existing column, relationship, or table, verify that the change will not:
  * remove existing records
  * overwrite stored values
  * break foreign-key relationships
  * reset IDs
  * destroy historical records
  * affect existing transactions or reports

* If existing data needs to be transformed for a new feature, use a controlled migration or data-update process that preserves the original records whenever possible.

### Mandatory Protocol Before Any Destructive Operations

If a requested feature appears to require deleting, resetting, or recreating database data:

```text
STOP
   ↓
Review the existing database
   ↓
Find a non-destructive solution first
   ↓
If destruction is truly unavoidable
   ↓
Explain exactly what data would be affected
   ↓
Request explicit approval before proceeding
```

Do **not** automatically clear the database even if doing so would make implementation easier.

### Core Rule

```text
Existing Database Data = Must Be Preserved

New Feature
     ↓
Review Existing Database
     ↓
Create Safe Migration
     ↓
Preserve Existing Records
     ↓
Test Compatibility
     ↓
Apply Update
```

This restriction applies even during major feature additions or structural database changes. Existing data should always be treated as persistent and important unless explicitly authorized by the user.

