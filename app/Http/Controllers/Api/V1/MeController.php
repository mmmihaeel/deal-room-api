<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'organizations' => function ($query) {
                $query->orderBy('organizations.name');
            },
            'memberships',
        ]);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'organizations' => OrganizationResource::collection($user->organizations),
            ],
        ]);
    }
}
