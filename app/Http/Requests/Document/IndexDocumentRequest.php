<?php

declare(strict_types=1);

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'deal_space_id' => ['required', 'integer', 'exists:deal_spaces,id'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'search' => ['nullable', 'string', 'max:160'],
            'mime_type' => ['nullable', 'string', 'max:127'],
            'sort' => ['nullable', Rule::in(['title', 'version', 'uploaded_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
