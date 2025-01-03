<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ImportTranslation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.translation.edit');
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
}
