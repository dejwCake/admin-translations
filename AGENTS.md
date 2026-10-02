# AGENTS.md — dejwcake/admin-translations

DB-backed translation manager for Craftable: DB translations override file translations, source
scanning for keys, Excel import/export and an admin UI. Composer `dejwcake/admin-translations`,
namespace `Brackets\AdminTranslations` (fork of `brackets/admin-translations`). Part of the
Craftable ecosystem — see README.md (it also documents scanning and key collation in detail).

## Layout

- `src/TranslationServiceProvider.php` — replaces Laravel's translation loader with
  `TranslationLoaderManager`; consumers swap it in for Laravel's provider.
- `src/TranslationLoaders/` — `TranslationLoaderManager` (file + DB merged with
  `array_replace_recursive`, DB wins), `DbTranslationLoader`.
- `src/Scanner/` — regex `TranslationsScanner` + `ScanAndSaveService`
  (`admin-translations:scan-and-save`).
- `src/Services/TranslationImportService.php`, `src/Exports/`, `src/Imports/` — Excel via
  maatwebsite/excel.
- `src/Http/Controllers/Admin/`, `src/Http/Requests/Admin/Translation/`, `routes/admin.php`
  (`/admin/translations`, gates `admin.translation.{index,edit,rescan}`).

## Commands

Everything runs in Docker from the package root — never against a host PHP. The full,
copy-pasteable list (composer, every QA tool, both databases and
the "whole PHP suite" one-liner) is in **README.md → "How to develop this project"**.
The ones you need most:

```shell
docker compose run --rm test composer update
docker compose run --rm test ./vendor/bin/phpunit                         # MariaDB (default)
docker compose run --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit   # PostgreSQL
docker compose run --rm php-qa phpcs -s --colors --extensions=php
docker compose run --rm php-qa phpcbf -s --colors --extensions=php       # auto-fix style
docker compose run --rm php-qa phpstan analyse --configuration=phpstan.neon
docker compose run --rm php-qa phpmd ./config,./database,./lang,./resources,./routes,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
docker compose run --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache
docker compose run --rm php-qa composer normalize
```

A change is done when phpcs, phpstan, phpmd and the test suite are green.

## Code conventions

- PHP `^8.5`, Laravel 13. Every file starts with `declare(strict_types=1);`.
- **No Facades** — inject contracts through the constructor.
- **No helpers**, with these exceptions: `trans()` / `__()` are allowed everywhere; `app()` only in
  models, traits and places where DI is genuinely hard to provide.
- Constructor property promotion. `final` classes and `readonly` wherever possible — prefer a
  `final readonly class`, otherwise readonly properties. A readonly property is public rather than
  hidden behind a getter.
- Always import with `use`; never inline `\Fully\Qualified\Names`.
- Alias the colliding `Repository` contracts:
  `use Illuminate\Contracts\Config\Repository as Config;`,
  `use Illuminate\Contracts\Cache\Repository as Cache;`.
- Name a property after its type: `TranslationImportService $translationImportService`, not `$service`.
- Build strings with `sprintf()` — no `"{$var}"` interpolation and no `.` concatenation.
- Mark overrides with `#[Override]` — **except** a method that overrides a *trait* method
  (e.g. `HasFactory::newFactory()`): PHP 8.5.3 segfaults on that.
- Before adding a native type to an overriding property/parameter, check the parent. If the parent
  is untyped (Laravel's `$fillable`, `$hidden`, a command's `$description`, …) the child must stay
  untyped too.
- Fix new phpstan/phpmd findings in code. Baselines are for accepted, existing debt only — inspect
  the baseline diff before committing it.

## Testing conventions

- PHPUnit 13 + Orchestra Testbench 11. Test namespaces mirror `src/`.
- Several tested methods of one class → a directory named after the class with one
  `<Method>Test.php` per method.
- Feature tests when several real classes collaborate; Unit tests for isolated logic (mock the
  rest). Don't write tests for service providers or install commands.
- PHPUnit assertions are static: `self::assert*()`. Laravel's instance assertions
  (`$this->assertDatabaseHas()`, response asserts) stay on `$this`.
- Resolve services with `$this->app->make()`, never `app()`.
- Test-only models and stubs live in the `tests/` root.

## Package notes

- `Translation::getTranslationsForGroupAndNamespace()` caches forever; the cache is cleared on the
  model's `saved`/`deleted` events. Any new write path must go through the model (or clear the
  cache) or stale translations stick.
- `ScanAndSaveService` soft-deletes all translations and then restores/creates the ones it finds,
  inside a transaction — keep it transactional.
- `text` and `metadata` are `jsonb` — test changes on both MariaDB and PostgreSQL.
- Excel fixtures for import tests live in `tests/fixtures/import/`.

## Versioning

The package is on **2.x** and stays there through the Laravel 13 / PHP 8.5 upgrade — don't add
v3 upgrade sections or bump the `branch-alias`. User-facing changes go to `UPGRADE.md` when
consumers have to act.
