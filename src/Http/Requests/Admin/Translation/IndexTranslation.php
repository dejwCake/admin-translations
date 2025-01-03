<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Requests\Admin\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexTranslation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.translation.index');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'orderBy' => [Rule::in(['id', 'group', 'key', 'text', 'created_at', 'updated_at']), 'nullable'],
            'orderDirection' => [Rule::in('asc', 'desc'), 'nullable'],
            'search' => ['string', 'nullable'],
            'page' => ['integer', 'nullable'],
            'per_page' => ['integer', 'nullable'],
            'group' => ['string', 'nullable'],
        ];
    }
}
