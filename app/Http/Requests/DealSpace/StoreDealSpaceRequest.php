<?php

declare(strict_types=1);

namespace App\Http\Requests\DealSpace;

use App\Enums\DealSpaceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', Rule::in(DealSpaceStatus::values())],
        ];
    }
}
