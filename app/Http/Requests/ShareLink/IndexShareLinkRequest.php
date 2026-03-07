<?php

declare(strict_types=1);

namespace App\Http\Requests\ShareLink;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexShareLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'deal_space_id' => ['nullable', 'integer', 'exists:deal_spaces,id'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'revoked'])],
            'sort' => ['nullable', Rule::in(['expires_at', 'created_at', 'download_count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
