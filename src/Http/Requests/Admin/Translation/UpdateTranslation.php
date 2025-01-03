<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Brackets\AdminTranslations\Translation;
use Brackets\Translatable\TranslatableFormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * @property Translation $translation
 */
class UpdateTranslation extends TranslatableFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.translation.edit', [$this->translation]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function translatableRules(string $locale): array
    {
        return [
            'text' => ['string', 'nullable'],
            'importLanguage' => ['string', 'nullable'],
            'resolvedTranslations' => ['array', 'nullable'],
        ];
    }

    public function getChosenLanguage(): string
    {
        return strtolower($this->validated('importLanguage'));
    }

    public function getResolvedConflicts(): array
    {
        return $this->validated('resolvedTranslations');
    }
}
