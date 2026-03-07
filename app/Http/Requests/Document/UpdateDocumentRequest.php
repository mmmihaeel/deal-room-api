<?php

declare(strict_types=1);

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['sometimes', 'nullable', 'integer', 'exists:folders,id'],
            'title' => ['sometimes', 'string', 'min:2', 'max:255'],
            'filename' => ['sometimes', 'string', 'max:255'],
            'mime_type' => ['sometimes', 'string', 'max:127'],
            'size_bytes' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'checksum' => ['sometimes', 'nullable', 'string', 'max:128'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'increment_version' => ['sometimes', 'boolean'],
        ];
    }
}
