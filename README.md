# Admin Translations

Admin Translations is a Laravel translation manager package. It scans your application for translation keys, stores them in the database, and provides a clean admin UI to review and edit them. The package ships with a custom translation loader that overrides Laravel’s default loader, so translations are automatically loaded from the database at runtime.

![Admin Translations ready to use](https://docs.getcraftable.com/assets/admin-translations-1.png "Admin Translations ready to use")

![Admin Translations edit form](https://docs.getcraftable.com/assets/admin-translations-2.png "Admin Translations edit form")

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel 13, forked from [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`).

## Documentation
You can find full documentation at https://docs.getcraftable.com/#/admin-translations

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

### Run tests

Run tests with pcov:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit -d pcov.enabled=1
```

To regenerate snapshots use:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit -d pcov.enabled=1 -d --update-snapshots
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:
```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
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
