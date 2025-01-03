<?php

namespace Brackets\AdminTranslations;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;

/**
 * @property string $namespace
 * @property string $group
 * @property string $key
 * @property array $text
 * @property Carbon $created_at
 */
class Translation extends Model
{
    use SoftDeletes;

    /**
     * @var array<string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    public $translatable = ['text'];

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

        static::saved(static function (Translation $translation) {
            $translation->flushGroupCache();
        });

        static::deleted(static function (Translation $translation) {
            $translation->flushGroupCache();
        });
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslationsForGroupAndNamespace(string $locale, string $group, string $namespace): array
    {
        if ($namespace === '' || $namespace === null) {
            $namespace = '*';
        }
        return Cache::rememberForever(static::getCacheKey($namespace, $group, $locale),
            static function () use ($namespace, $group, $locale) {
                return static::query()
                        ->where('namespace', $namespace)
                        ->where('group', $group)
                        ->get()
                        ->reject(static function (Translation $translation) use ($locale, $group) {
                            return empty($translation->getTranslation($locale, $group));
                        })
                        ->reduce(static function ($translations, Translation $translation) use ($locale, $group) {
                            if ($group === '*') {
                                $translations[$translation->key] = $translation->getTranslation($locale, $group);
                            } else {
                                Arr::set($translations, $translation->key, $translation->getTranslation($locale));
                            }

                            return $translations;
                        }) ?? [];
            });
    }

    public static function getCacheKey(string $namespace, string $group, string $locale): string
    {
        return "brackets.admin-translations.{$namespace}.{$group}.{$locale}";
    }

    public function getTranslation(string $locale, ?string $group = null): string
    {
        if ($group === '*' && !isset($this->text[$locale])) {
            $fallback = config('app.fallback_locale');

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
        foreach ($this->getTranslatedLocales() as $locale) {
            Cache::forget(static::getCacheKey($this->namespace ?? '*', $this->group, $locale));
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
