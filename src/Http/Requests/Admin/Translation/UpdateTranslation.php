<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Brackets\AdminTranslations\Models\Translation;
use Brackets\Translatable\Http\Requests\TranslatableFormRequest;
use Illuminate\Contracts\Auth\Access\Gate;

/**
 * @property Translation $translation
 */
class UpdateTranslation extends TranslatableFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('admin.translation.edit', [$this->translation]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function untranslatableRules(): array
    {
        return [
            'importLanguage' => ['string', 'nullable'],
            'resolvedTranslations' => ['array', 'nullable'],
        ];
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
