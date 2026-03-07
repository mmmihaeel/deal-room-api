<?php

declare(strict_types=1);

namespace App\Http\Requests\Folder;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
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
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
            'name' => ['required', 'string', 'min:1', 'max:180'],
        ];
    }
}
