# Admin Translations

Admin Translations is a Laravel translation manager package. It scans your application for translation keys, stores them in the database, and provides a clean admin UI to review and edit them. The package ships with a custom translation loader that overrides Laravel’s default loader, so translations are automatically loaded from the database at runtime.

![Admin Translations ready to use](https://docs.getcraftable.com/assets/admin-translations-1.png "Admin Translations ready to use")

![Admin Translations edit form](https://docs.getcraftable.com/assets/admin-translations-2.png "Admin Translations edit form")

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel 13, forked from [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`).

## Documentation
You can find full documentation at https://docs.getcraftable.com/#/admin-translations

## Collecting keys: `admin-translations:scan-and-save`

The command builds the key set from two sources and stores each key exactly once:

- **scanning** the directories in `admin-translations.scanned_directories` for `trans()` and
  `__()` calls, limited to the extensions in `scanned_extensions`;
- **reading the lang files** — the groups named in `imported_groups`, plus `lang/{locale}.json`
  when `imported_json` is on.

The second source exists because some keys are unreachable by scanning. Laravel assembles
`validation.*` and `passwords.*` at runtime, and a package may declare keys that only its
published frontend consumes. `imported_groups` defaults to `['*']` — every group under
`lang/{locale}` and `lang/vendor/{namespace}/{locale}`, for the locales in
`translatable.locales`. Name groups explicitly (`'validation'`, `'brackets/admin-ui::admin'`) to
narrow it, or set an empty array to import nothing.

### Storing the translations too

By default the command stores **keys only** — the text stays in the lang files, and the loader
merges file and database at runtime with the database winning.

| Flag | Effect |
|---|---|
| *(none)* | Keys only. Existing `text` is untouched. |
| `--with-text` | Also read each key's current value from the lang files and store it, but **only into locales that are still empty**. Anything edited in the admin UI survives. |
| `--with-text --overwrite` | Replace stored translations with the file value, for every locale the files supply. Prompts for confirmation, reporting how many rows are affected. |
| `--force` | Skip that confirmation. For CI only. |

`--overwrite` without `--with-text` is refused rather than ignored.

Seeding is worth doing once the key set is final: until a translation has a stored value, the
admin list renders it through the runtime fallback, so **searching by translated text cannot
find it**.

> **Seeding reads the lang files only, never the database.** The registered
> `TranslationLoaderManager` merges file *and* database values, with the database winning — so
> resolving through it would feed stored values back into themselves. An accidental edit would
> reseed itself on every run and could never be corrected from the files. `--with-text` therefore
> resolves against a plain `Illuminate\Translation\FileLoader`. Keep it that way if you touch
> `FileTranslationResolver`.

### Where translations are authored

The two directions are deliberately **not** symmetric:

| | Where | Mechanism |
|---|---|---|
| Seed translations | development | files → database, via `--with-text` |
| Author translations | **production** | admin UI → database, which persists |

Which is why:

- **There is no database → file write-back, and no `--sync-json` command.** Such a command could
  only run in development, where the edits are throwaway by nature — losing them to a
  `migrate:fresh` is not a defect. The real work happens against the production database.
- **The lang files are frozen after release, not deleted.** They stay checked in as the seed and
  as the fallback if the `translations` table is ever empty or unreachable; they simply stop being
  edited.

## Translation key collation

A translation is identified by its `namespace`, `group` and `key`. Those three columns are stored
with a **case- and accent-sensitive** collation (`utf8mb4_bin` on MySQL and MariaDB; PostgreSQL and
SQLite compare text exactly by default), because `Log in` and `log in`, or `Uložiť` and `Ulozit`, are
different keys and each needs its own row.

This matters because the usual Laravel default, `utf8mb4_unicode_ci`, is insensitive to both. Under
it the two spellings collide, only one of them can ever be stored, and
`admin-translations:scan-and-save` reports more translations than it saved.

Searching in the admin UI is **not** affected: `admin-listing` normalises the comparison itself
instead of relying on the column collation, so search stays case- and accent-insensitive on every
driver.

> **That order is a dependency, not a coincidence.** On MySQL, case-insensitive search was
> historically a *side effect* of `utf8mb4_unicode_ci` — `LIKE '%dash%'` matched `Dashboard` only
> because the column collation said so. Switching to `utf8mb4_bin` therefore breaks key search
> unless the search normalises the comparison itself first. `admin-listing` does that from the
> version required here, applying an explicit `COLLATE` override that still wins over a
> `utf8mb4_bin` column. **Do not run `make_translation_keys_case_sensitive` against an older
> `admin-listing`** — equality becomes correct while search silently stops finding things.

> **Warning — customised columns.** The `make_translation_keys_case_sensitive` migration writes the
> full column definition, using the package's own shape: `VARCHAR(255)` for `namespace` and `group`
> (with `namespace` defaulting to `'*'`), and `TEXT` for `key`. If your application has altered any of
> those — a wider type, a different default, a changed nullability — **the migration will reset them
> to the package's definition.** Check the table first and adjust the published migration if needed.

## Issues
Where do I report issues?
If something is not working as expected, please open an issue in the main repository https://github.com/dejwCake/craftable.

## How to develop this project

### Composer

Update dependencies:
```shell
docker compose run -it --rm test composer update
```

Composer normalization:
```shell
docker compose run -it --rm php-qa composer normalize
```

### Run code analysis tools (php-qa)

PHP compatibility:
```shell
docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache
```

Code style:
```shell
docker compose run -it --rm php-qa phpcs -s --colors --extensions=php
```

Fix style issues:
```shell
docker compose run -it --rm php-qa phpcbf -s --colors --extensions=php
```

Static analysis (phpstan):
```shell
docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon
```

Mess detector (phpmd):
```shell
docker compose run -it --rm php-qa phpmd ./config,./database,./lang,./resources,./routes,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
```

### Run tests

Run tests against mariadb:
```shell
docker compose run -it --rm -e DB_CONNECTION=mysql test ./vendor/bin/phpunit
```

Run tests against postgresql:
```shell
docker compose run -it --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit
```

Run tests with coverage:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit --coverage-text
```

### Run the whole PHP suite

Run every PHP check and the test suite against both databases in sequence (stops at the first failure):
```shell
docker compose run -it --rm test composer update \
  && docker compose run -it --rm php-qa composer normalize \
  && docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache \
  && docker compose run -it --rm php-qa phpcs -s --colors --extensions=php \
  && docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon \
  && docker compose run -it --rm php-qa phpmd ./config,./database,./lang,./resources,./routes,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml \
  && docker compose run -it --rm -e DB_CONNECTION=mysql test ./vendor/bin/phpunit \
  && docker compose run -it --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit
```
