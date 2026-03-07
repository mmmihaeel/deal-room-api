<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $databaseHealthy = true;
        $redisHealthy = true;

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $databaseHealthy = false;
        }

        try {
            $redisPing = Redis::connection()->ping();
            $redisHealthy = in_array((string) $redisPing, ['1', 'PONG', '+PONG'], true);
        } catch (Throwable) {
            $redisHealthy = false;
        }

        $healthy = $databaseHealthy && $redisHealthy;

        return response()->json([
            'data' => [
                'service' => config('app.name'),
                'status' => $healthy ? 'ok' : 'degraded',
                'dependencies' => [
                    'database' => $databaseHealthy ? 'ok' : 'down',
                    'redis' => $redisHealthy ? 'ok' : 'down',
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ], $healthy ? 200 : 503);
    }
}
