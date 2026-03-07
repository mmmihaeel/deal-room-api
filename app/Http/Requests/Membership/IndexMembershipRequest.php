<?php

declare(strict_types=1);

namespace App\Http\Requests\Membership;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'role' => ['nullable', Rule::in(MembershipRole::values())],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['created_at', 'role', 'joined_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
