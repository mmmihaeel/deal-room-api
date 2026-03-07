<?php

declare(strict_types=1);

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'deal_space_id' => ['required', 'integer', 'exists:deal_spaces,id'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:127'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'checksum' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
