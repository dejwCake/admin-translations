<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class ExportTranslation extends FormRequest
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
            'exportLanguages' => ['required', 'array'],
        ];
    }

    /**
     * @return Collection<string>
     */
    public function getExportLanguages(): Collection
    {
        return (new Collection($this->validated('exportLanguages')))
            ->map(static fn (string $language): string => strtolower($language));
    }
}
