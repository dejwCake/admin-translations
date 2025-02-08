# Admin Translations

Admin Translations is a translation manager package. It scans your codebase and stores all the translations in the database. Once stored it provides nice UI to manage stored translations. It defines a custom translation loader overriding default Laravel's one, so the translations are automatically loaded from the database.

![Admin Translations ready to use](https://docs.getcraftable.com/assets/admin-translations-1.png "Admin Translations ready to use")

![Admin Translations edit form](https://docs.getcraftable.com/assets/admin-translations-2.png "Admin Translations edit form")

This packages is part of [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`) - an administration starter kit for Laravel 5. You should definitely have a look :)

You can find full documentation at https://docs.getcraftable.com/#/admin-translations


## Run tests

To run tests use this docker environment.

```shell
  docker compose run -it --rm test vendor/bin/phpunit
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:

```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
```

## Issues
Where do I report issues?
If something is not working as expected, please open an issue in the main repository https://github.com/BRACKETS-by-TRIAD/craftable.
