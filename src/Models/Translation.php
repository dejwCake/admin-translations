<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Models;

use Brackets\AdminTranslations\Observers\TranslationObserver;
use Brackets\AdminTranslations\Service\LocaleProvider;
use Carbon\CarbonInterface;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * @property int $id
 * @property string $namespace
 * @property string $group
 * @property string $key
 * @property array $text
 * @property string $metadata
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface $deleted_at
 */
#[Guarded(['id'])]
#[ObservedBy(TranslationObserver::class)]
class Translation extends Model
{
    use SoftDeletes;

    /** @var array<string> */
    public array $translatable = ['text'];

    /**
     * @return array<string, string>
     */
    public static function getTranslationsForGroupAndNamespace(string $locale, string $group, ?string $namespace): array
    {
        if ($namespace === '' || $namespace === null) {
            $namespace = '*';
        }
        $cache = Container::getInstance()->make(Cache::class);

        return $cache->rememberForever(
            static::getCacheKey($namespace, $group, $locale),
            static fn () => static::query()
                        ->where('namespace', $namespace)
                        ->where('group', $group)
                        ->get()
                        ->reject(static fn (self $translation) => $translation->getTranslation($locale) === '')
                        ->reduce(static function ($translations, self $translation) use ($locale, $group) {
                            if ($group === '*') {
                                $translations[$translation->key] = $translation->getTranslation($locale);
                            } else {
                                Arr::set($translations, $translation->key, $translation->getTranslation($locale));
                            }

                            return $translations;
                        }, []),
        );
    }

    public static function getCacheKey(string $namespace, string $group, string $locale): string
    {
        return sprintf('brackets.admin-translations.%s.%s.%s', $namespace, $group, $locale);
    }

    /**
     * Passing `$group` is deprecated and will be removed in 3.0.
     */
    public function getTranslation(string $locale, ?string $group = null): string
    {
        if ($group === '*' && !isset($this->text[$locale])) {
            $config = Container::getInstance()->make(Config::class);
            assert($config instanceof Config);
            $fallback = $config->get('app.fallback_locale');

            return $this->text[$fallback] ?? $this->key;
        }

        return $this->text[$locale] ?? '';
    }

    public function setTranslation(string $locale, string $value): self
    {
        $this->text = [...($this->text ?? []), $locale => $value];

        return $this;
    }

    /**
     * Flush the cached translations of this row's group, for every configured locale.
     *
     * Not only the locales already present in `text`: a row saved with no text at all has
     * none, so keying the flush on them meant a scanned row never invalidated anything and
     * `rememberForever` kept serving the previous contents of the group.
     */
    public function flushGroupCache(): void
    {
        $cache = Container::getInstance()->make(Cache::class);
        assert($cache instanceof Cache);

        $localeProvider = Container::getInstance()->make(LocaleProvider::class);
        assert($localeProvider instanceof LocaleProvider);

        $locales = array_unique([...$localeProvider->all(), ...$this->getTranslatedLocales()]);

        foreach ($locales as $locale) {
            $cache->forget(static::getCacheKey($this->namespace ?? '*', $this->group, $locale));
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['text' => 'array'];
    }

    /**
     * @return array<string>
     */
    protected function getTranslatedLocales(): array
    {
        return array_keys($this->text);
    }
}
