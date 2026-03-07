<?php

declare(strict_types=1);

namespace App\Http\Requests\DealSpace;

use App\Enums\DealPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertDealSpacePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grants' => ['required', 'array', 'min:1'],
            'grants.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'grants.*.permissions' => ['required', 'array', 'min:1'],
            'grants.*.permissions.*' => ['required', Rule::in(DealPermission::values())],
        ];
    }
}
