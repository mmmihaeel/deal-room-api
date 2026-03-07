<?php

declare(strict_types=1);

namespace App\Http\Requests\ShareLink;

use Illuminate\Foundation\Http\FormRequest;

class StoreShareLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'integer', 'exists:documents,id'],
            'expires_at' => ['required', 'date', 'after:now'],
            'max_downloads' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
