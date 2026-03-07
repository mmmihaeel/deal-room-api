<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DealSpaceController;
use App\Http\Controllers\Api\V1\DealSpacePermissionController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\FolderController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MembershipController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ShareLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::get('/share-links/{token}', [ShareLinkController::class, 'resolve'])
        ->middleware('throttle:share-link-resolve');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', MeController::class);

        Route::apiResource('organizations', OrganizationController::class)->except(['create', 'edit']);
        Route::apiResource('deal-spaces', DealSpaceController::class)->except(['create', 'edit']);
        Route::apiResource('folders', FolderController::class)->except(['create', 'edit']);
        Route::apiResource('documents', DocumentController::class)->except(['create', 'edit']);
        Route::apiResource('memberships', MembershipController::class)->except(['create', 'edit', 'show']);

        Route::get('/deal-spaces/{deal_space}/permissions', [DealSpacePermissionController::class, 'index']);
        Route::put('/deal-spaces/{deal_space}/permissions', [DealSpacePermissionController::class, 'upsert']);

        Route::get('/share-links', [ShareLinkController::class, 'index']);
        Route::post('/share-links', [ShareLinkController::class, 'store']);
        Route::delete('/share-links/{shareLink}', [ShareLinkController::class, 'destroy']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
