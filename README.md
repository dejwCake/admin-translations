# Admin Translations

Admin Translations is a Laravel translation manager package. It scans your application for translation keys, stores them in the database, and provides a clean admin UI to review and edit them. The package ships with a custom translation loader that overrides Laravel’s default loader, so translations are automatically loaded from the database at runtime.

![Admin Translations ready to use](https://docs.getcraftable.com/assets/admin-translations-1.png "Admin Translations ready to use")

![Admin Translations edit form](https://docs.getcraftable.com/assets/admin-translations-2.png "Admin Translations edit form")

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel 12, forked from [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`).

## Documentation
You can find full documentation at https://docs.getcraftable.com/#/admin-translations

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
docker compose run -it --rm php-qa phpmd ./src,./config,./database,./install-stubs,./lang,./resources,./routes,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
```