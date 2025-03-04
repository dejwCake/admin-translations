<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * @property string $namespace
 * @property string $group
 * @property string $key
 * @property array $text
 * @property CarbonInterface $created_at
 */
class Translation extends Model
{
    use SoftDeletes;

    /** @var array<string> */
    public array $translatable = ['text'];

    /**
     * @var array<string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    public $guarded = ['id'];

    /**
     * @var array<string, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $casts = ['text' => 'array'];

    /**
     * Boot method to declare event handlers
     */
    public static function boot(): void
    {
        static::bootTraits();

        static::saved(static function (self $translation): void {
            $translation->flushGroupCache();
        });

        static::deleted(static function (self $translation): void {
            $translation->flushGroupCache();
        });
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslationsForGroupAndNamespace(string $locale, string $group, ?string $namespace): array
    {
        if ($namespace === '' || $namespace === null) {
            $namespace = '*';
        }
        $cache = app(Cache::class);

        return $cache->rememberForever(
            static::getCacheKey($namespace, $group, $locale),
            static fn () => static::query()
                        ->where('namespace', $namespace)
                        ->where('group', $group)
                        ->get()
                        ->reject(static fn (self $translation) => $translation->getTranslation($locale, $group) === '')
                        ->reduce(static function ($translations, self $translation) use ($locale, $group) {
                            if ($group === '*') {
                                $translations[$translation->key] = $translation->getTranslation($locale, $group);
                            } else {
                                Arr::set($translations, $translation->key, $translation->getTranslation($locale));
                            }

                            return $translations;
                        }, []),
        );
    }

    public static function getCacheKey(string $namespace, string $group, string $locale): string
    {
        return "brackets.admin-translations.{$namespace}.{$group}.{$locale}";
    }

    public function getTranslation(string $locale, ?string $group = null): string
    {
        $config = app(Config::class);
        assert($config instanceof Config);

        if ($group === '*' && !isset($this->text[$locale])) {
            $fallback = $config->get('app.fallback_locale');

            return $this->text[$fallback] ?? $this->key;
        }

        return $this->text[$locale] ?? '';
    }

    public function setTranslation(string $locale, string $value): self
    {
        $this->text = array_merge($this->text ?? [], [$locale => $value]);

        return $this;
    }

    /**
     * Flush cache
     */
    protected function flushGroupCache(): void
    {
        $cache = app(Cache::class);
        assert($cache instanceof Cache);

        foreach ($this->getTranslatedLocales() as $locale) {
            $cache->forget(static::getCacheKey($this->namespace ?? '*', $this->group, $locale));
        }
    }

    /**
     * @return array<string>
     */
    protected function getTranslatedLocales(): array
    {
        return array_keys($this->text);
    }
}
