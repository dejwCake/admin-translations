<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

final class ImportTranslation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('admin.translation.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'importLanguage' => ['string', 'required'],
            'onlyMissing' => ['string'],
            'fileImport' => ['required', 'file'],
        ];
    }

    public function getChosenLanguage(): string
    {
        return strtolower($this->validated('importLanguage'));
    }

    public function getOnlyMissing(): bool
    {
        return $this->validated('onlyMissing') === 'true';
    }
}
