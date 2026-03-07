<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheVersionService
{
    public function remember(string $domain, int|string $scope, array $params, int $seconds, Closure $resolver): mixed
    {
        $version = (int) Cache::get($this->versionKey($domain, $scope), 1);
        $cacheKey = $this->buildCacheKey($domain, $scope, $version, $params);

        return Cache::remember($cacheKey, $seconds, $resolver);
    }

    public function bump(string $domain, int|string $scope): void
    {
        $versionKey = $this->versionKey($domain, $scope);
        $nextVersion = ((int) Cache::get($versionKey, 1)) + 1;

        Cache::forever($versionKey, $nextVersion);
    }

    private function versionKey(string $domain, int|string $scope): string
    {
        return sprintf('cache-version:%s:%s', $domain, $scope);
    }

    private function buildCacheKey(string $domain, int|string $scope, int $version, array $params): string
    {
        ksort($params);

        return sprintf(
            'cache:%s:%s:v%s:%s',
            $domain,
            $scope,
            $version,
            hash('sha256', json_encode($params, JSON_THROW_ON_ERROR))
        );
    }
}
