<?php

namespace Brackets\AdminTranslations\Test;

use Brackets\AdminAuth\AdminAuthServiceProvider;
use Brackets\AdminTranslations\AdminTranslationsServiceProvider;
use Brackets\AdminTranslations\Test\Exceptions\Handler;
use Brackets\AdminTranslations\Translation;
use Brackets\Translatable\TranslatableServiceProvider;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @var Translation */
    protected Translation $languageLine;

    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate');

        Schema::create('translations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('namespace')->default('*');
            $table->index('namespace');
            $table->string('group');
            $table->index('group');
            $table->text('key');
            $table->jsonb('text');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->languageLine = $this->createTranslation('*', 'group', 'key', ['en' => 'english', 'nl' => 'nederlands']);

        File::copyDirectory(__DIR__.'/fixtures/resources/views', resource_path('views'));
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            TranslatableServiceProvider::class,
            AdminTranslationsServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['path.lang'] = $this->getFixturesDirectory('lang');

        $app['config']->set('translatable.locales', ['en', 'sk']);

        if (env('DB_CONNECTION') === 'pgsql') {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => 'pgsql',
                'port' => '5432',
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'bestsecret'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else if (env('DB_CONNECTION') === 'mysql') {
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => '3306',
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'bestsecret'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        $app['config']->set('admin-translations.model', Translation::class);

        $app['config']->set('admin-translations.scanned_directories', [__DIR__.'/fixtures/views']);

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    public function getFixturesDirectory(string $path): string
    {
        return __DIR__."/fixtures/{$path}";
    }

    //TODO reorder
    protected function createTranslation(string $namespace, string $group, string $key, array $text): Translation
    {
        return Translation::create(compact('group', 'key', 'namespace', 'text'));
    }
}
