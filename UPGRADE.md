# Upgrade Guide: v1 to v2

## Requirements

| Dependency | v1 | v2 |
|---|---|---|
| PHP | ^8.2 | ^8.5 |
| Laravel | ^12.0 | ^13.0 |
| dejwcake/admin-listing | ^1.0 | ^2.0 |
| dejwcake/admin-ui | ^1.0 | ^2.0 |
| dejwcake/craftable-translatable | ^1.0 | ^2.0 |
| phpunit/phpunit | ^11.5 | ^13.0 |

Update your `composer.json`:

```json
"dejwcake/admin-translations": "^2.0"
```

## Breaking Changes

### 1. `install-stubs/` Removed — Config Moved

The `install-stubs/` directory has been removed. Config is now located directly inside the package:

| v1 path | v2 path |
|---|---|
| `install-stubs/config/admin-translations.php` | `config/admin-translations.php` |

**Action required:** If you have published config from v1, no changes needed — your published copy remains. The package now uses `mergeConfigFrom` pointing to its own `config/` directory.

### 2. New Config Key: `translation_manager`

A new `translation_manager` key has been added to `config/admin-translations.php`:

```php
'translation_manager' => TranslationLoaderManager::class,
```

**Action required:** If you have a published config, add this key. It controls which class is used as the translation loader manager.

### 3. `TranslationService` Renamed to `TranslationImportService`

The import service class has been renamed and moved:

```php
// v1
use Brackets\AdminTranslations\Service\Import\TranslationService;

// v2
use Brackets\AdminTranslations\Service\TranslationImportService;
```

**Action required:** Update any imports or type-hints referencing the old class.

### 4. `ScanAndSaveService` and `TranslationsScanner` Moved

Both scanner-related classes have been moved to a dedicated `Scanner` namespace:

```php
// v1
use Brackets\AdminTranslations\Service\ScanAndSaveService;
use Brackets\AdminTranslations\TranslationsScanner;

// v2
use Brackets\AdminTranslations\Scanner\ScanAndSaveService;
use Brackets\AdminTranslations\Scanner\TranslationsScanner;
```

**Action required:** Update any imports referencing the old namespaces.

### 5. `TranslationLoaderManager` Moved

```php
// v1
use Brackets\AdminTranslations\TranslationLoaderManager;

// v2
use Brackets\AdminTranslations\TranslationLoaders\TranslationLoaderManager;
```

**Action required:** Update any imports. If you reference this class in a published config, update the `translation_manager` value.

### 6. `AdminListingService::create()` Replaced with `AdminListingBuilder`

The controller now uses `ListingBuilder` and `ListingQueryBuilder` (injected via constructor) instead of the static `AdminListingService::create()` factory:

```php
// v1
$data = AdminListingService::create(Translation::class)
    ->processRequestAndGet(...);

// v2
$data = $this->listingBuilder->for(Translation::class)->build()->processRequestAndGet(
    $this->listingQueryBuilder->fromRequest($request),
    ...
);
```

**Action required:** If you extend `TranslationsController`, update to use `ListingBuilder` and `ListingQueryBuilder`.

### 7. Facades Replaced with Dependency Injection

All usage of `app()` helper and facades has been replaced with constructor injection or `$this->app->make()`:

| v1 | v2 |
|---|---|
| `app(Config::class)` | Constructor-injected `Config $config` |
| `app(Router::class)` | `$this->app->make(Router::class)` |
| `app(TranslationsScanner::class)` | Constructor-injected `TranslationsScanner` |
| `app('config')` in `TranslationLoaderManager` | Constructor-injected `Config` |

**Action required:** If you extend any package classes, update to use the injected dependencies.

### 8. Class Visibility Changes

Most classes are now `final` and/or `final readonly`:

| Class | v1 | v2 |
|---|---|---|
| `AdminTranslationsServiceProvider` | — | `final` |
| `TranslationServiceProvider` | — | `final` |
| `TranslationImportService` | — | `final readonly` |
| `TranslationRepository` | — | `final readonly` |
| `ScanAndSaveService` | — | `final readonly` |
| `DbTranslationLoader` | — | `final readonly` |
| `TranslationLoaderManager` | — | `final` |
| `TranslationsScanner` | — | `final` |
| `TranslationsExport` | — | `final` |
| `TranslationsImport` | — | `final` |
| `InvalidConfiguration` | — | `final` |
| `WrongImportFile` | — | `final` |
| All Form Requests | — | `final` |
| Both Controllers | — | `final` |

**Action required:** If you extend any of these classes, refactor to use composition or decoration instead.

### 9. Blade Template Rewritten — Vue Component Required

The `resources/views/admin/translation/index.blade.php` has been completely rewritten from 372 lines to 53 lines. The entire inline Vue template (modals, table, pagination, form markup) has been removed. The view now renders a single `<translation-listing>` Vue component from `@dejwcake/craftable`:

```blade
{{-- v1: Inline Vue template with all UI markup in Blade --}}
@section('content')
    <translation-listing inline-template ...>
        {{-- 300+ lines of HTML/Vue template --}}
    </translation-listing>
@endsection

{{-- v2: Single component tag with props --}}
@section('body')
    <translation-listing
        :data="{{ $data->toJson() }}"
        :url="'{{ url('admin/translations') }}'"
        :locales="{{ json_encode($locales) }}"
        :user-locale="'{{ $userLocale }}'"
        :groups="{{ json_encode($groups) }}"
        :translations="{{ json_encode([...]) }}"
    ></translation-listing>
@endsection
```

New props passed from controller:
- `:user-locale` — computed server-side (was inline `Auth` facade call in Blade)
- `:groups` — JSON array of available groups
- `:translations` — all UI translation strings as JSON object

**Action required:** If you have published/customized the translation index view:
1. Update to use the new component syntax
2. Ensure `@dejwcake/craftable` frontend package is installed with `TranslationListing` component registered
3. Or re-publish with `php artisan vendor:publish --tag=views --provider="Brackets\AdminTranslations\AdminTranslationsServiceProvider" --force`

### 10. Language File Changes

Translation keys have been renamed to fix typos:

| v1 key | v2 key |
|---|---|
| `sucesfully_notice` | `successfully_notice` |
| `sucesfully_notice_update` | `successfully_notice_update` |

Removed keys: `namespace`, `english`, `export_reference_language`, `reference_langauge`

Bug fix in Slovak translations: `language_to_export` was incorrectly using the import label text.

**Action required:** If you have published translation overrides, update the renamed keys.

### 11. Migration Updated

```php
// v1
$table->increments('id');
Schema::drop('translations');

// v2
$table->id();
Schema::dropIfExists('translations');
```

`increments()` (unsigned int) changed to `id()` (unsigned big int). `drop()` changed to `dropIfExists()` for safer rollback.

**Action required:** If you already have the translations table from v1, no action needed. For fresh installs, the table will use `bigIncrements`.

### 12. `DbTranslationLoader::getConfiguredModelClass()` Visibility Changed

```php
// v1
protected function getConfiguredModelClass(): string

// v2
private function getConfiguredModelClass(): string
```

**Action required:** If you extend `DbTranslationLoader` and override this method, refactor (the class is now `final readonly` anyway).

### 13. `Translation` Model Boot Method Fixed

```php
// v1 (incorrect)
static::bootTraits();

// v2 (correct)
parent::boot();
```

### 14. Sidebar Icon Changed

The install command now uses `fa fa-language` instead of `icon-location-pin` for the sidebar navigation icon.

**Action required:** If you have already installed v1, update the icon class in your `resources/views/admin/layout/sidebar.blade.php` manually.

## Migration Steps Summary

1. Update `composer.json` requirements (PHP ^8.5, internal packages ^2.0)
2. Run `composer update`
3. Add `translation_manager` key to published `admin-translations.php` config (if published)
4. Update any class imports for renamed/moved classes:
   - `Service\Import\TranslationService` → `Service\TranslationImportService`
   - `Service\ScanAndSaveService` → `Scanner\ScanAndSaveService`
   - `TranslationsScanner` → `Scanner\TranslationsScanner`
   - `TranslationLoaderManager` → `TranslationLoaders\TranslationLoaderManager`
5. Replace any class extensions with composition (all classes are now `final`)
6. Update published translation overrides: rename `sucesfully_notice` → `successfully_notice`, `sucesfully_notice_update` → `successfully_notice_update`
7. Update published Blade views to use Vue component syntax (or re-publish)
8. Update sidebar icon from `icon-location-pin` to `fa fa-language`
9. Ensure `@dejwcake/craftable` frontend package is installed with translation components

---

# Upgrade Guide: 2.1.x to 2.2

**Not a breaking release.** The public API is unchanged and no code changes are required. There are
three behaviour changes worth knowing about before you upgrade, and one new migration to run.

`TranslationsScanner::__construct()` gained an optional `Config` argument. The container injects it
automatically, and `new TranslationsScanner($disk)` keeps working — without a config there is no
extension list, so it scans every file exactly as 2.1.x did.

## Behaviour Changes

### 1. Only Source File Extensions Are Scanned

`scanned_directories` previously read **every** file it found, whatever the extension. It now reads
only those listed in the new `scanned_extensions` config key, defaulting to `php`, `vue`, `js`, `jsx`,
`ts`, `tsx`.

**Action required:** if you keep translation calls in any other file type — `.twig`, `.md`, `.txt`,
a templating language of your own — add its extension to `scanned_extensions`, or set the key to an
empty array to restore the old scan-everything behaviour. Otherwise those keys silently stop being
collected and their rows are soft-deleted on the next scan.

### 2. Keys Containing Escaped Quotes Change Spelling

The scanner now unescapes what it captures, so the stored key matches the string `__()` receives at
runtime.

```php
__('C:\\path')     // 2.1.x stored: C:\\path      next stores: C:\path
```

Two related changes in the same area: calls with replacement parameters, calls wrapped over several
lines, and strings containing an escaped quote are now collected at all (they were silently skipped);
and JavaScript method calls such as `i18n.__('x')` are no longer collected (they were a false
positive).

**Action required:** any existing row whose key held a backslash escape will be soft-deleted and
recreated under its new spelling, orphaning the translations attached to it. Rare, but check with
`SELECT * FROM translations WHERE \`key\` LIKE '%\\\\%'` before upgrading if you use such keys.

### 3. Translation Key Columns Are Now Case- and Accent-Sensitive

A new migration, `make_translation_keys_case_sensitive`, switches `namespace`, `group` and `key` to
`utf8mb4_bin` on MySQL and MariaDB. PostgreSQL and SQLite already compare text exactly and are left
untouched.

```sql
-- before: utf8mb4_unicode_ci (or utf8mb4_uca1400_ai_ci)
'Log in'  = 'log in'   -- true
'Uložiť'  = 'Ulozit'   -- true

-- after: utf8mb4_bin
'Log in'  = 'log in'   -- false
'Uložiť'  = 'Ulozit'   -- false
```

Under the old collation two genuinely different keys collided, and only one of them could ever have a
row: `admin-translations:scan-and-save` would report more translations than it stored.

Listing search is unaffected — `admin-listing` normalises the comparison itself rather than relying on
the column collation, so searching stays case- **and** accent-insensitive.

**Action required:** run `php artisan vendor:publish --tag=migrations` to pick up the new migration,
then `php artisan migrate`.

> **Warning — customised columns.** The migration writes the full column definition, using the
> package's own shape: `VARCHAR(255)` for `namespace` and `group` (with `namespace` defaulting to
> `'*'`), and `TEXT` for `key`. If your application has altered any of those — a wider type, a
> different default, a changed nullability — **the migration will reset them to the package's
> definition.** Check the table before migrating, and adjust the published migration if you have
> customised it. `down()` restores `utf8mb4_unicode_ci` but likewise rewrites the definitions.

## Migration Steps Summary (2.1.x to next)

1. Run `composer update`
2. Add `scanned_extensions` to your published `admin-translations.php` config if you scan
   non-standard file types (see §1)
3. Check for keys containing backslash escapes (see §2)
4. Publish and run the new migration: `php artisan vendor:publish --tag=migrations` then
   `php artisan migrate` — first reading the warning in §3 if you have customised the
   `translations` column definitions
5. Re-run `php artisan admin-translations:scan-and-save`
