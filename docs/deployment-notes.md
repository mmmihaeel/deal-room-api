# Deployment Notes

This repository ships a strong local runtime and CI baseline, but it does not claim to be a full production deployment package. Treat these notes as the translation layer from the current codebase to a production-ready environment.

## What the Repository Ships Today
- `docker/php/Dockerfile` for the Laravel application runtime
- Apache configuration for the local HTTP entrypoint
- `docker-compose.yml` for local app, web, MySQL, and Redis services
- GitHub Actions CI that installs dependencies, runs migrations, checks code quality, runs tests, and verifies Docker builds

## Production Translation
| Concern | In-repo baseline | Production expectation |
| --- | --- | --- |
| Web entrypoint | Apache container on port `8080` | Reverse proxy or ingress with TLS termination |
| App runtime | PHP-FPM Laravel container | Immutable application image with controlled config and secret injection |
| Database | Local MySQL container | Managed or separately operated MySQL instance with backups |
| Cache | Local Redis container | Private Redis instance used for cache only |
| Secrets | `.env`-driven local config | Secret manager or platform-native secret delivery |
| Migrations | Manual or CI-assisted in local flow | Release-stage migration step using `php artisan migrate --force` |
| Observability | Container logs and health endpoint | Structured logs, metrics, alerts, and request tracing where available |

## Required Environment Baseline
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` set and managed securely
- Correct `APP_URL`
- MySQL connection variables (`DB_*`)
- Redis connection variables (`REDIS_*`)
- `CACHE_DRIVER=redis`

## Suggested Release Sequence
1. Build the application image from `docker/php/Dockerfile`.
2. Run CI quality gates before deployment.
3. Deploy the new application image behind a TLS-terminating edge.
4. Run `php artisan migrate --force`.
5. Warm config and route caches if your runtime strategy uses them.
6. Smoke test `/api/v1/health`, login, and a representative authenticated endpoint.

## Runtime Validation After Deploy
- Confirm the health endpoint returns `200` with both dependencies marked `ok`.
- Verify login and logout with a non-admin account.
- Verify a privileged audit-log query still succeeds for `owner` or `admin`.
- Confirm Redis-backed caching is configured and MySQL plus Redis are not exposed publicly.

## Deliberate Non-Goals
- No Kubernetes manifests, Terraform, or cloud-specific infrastructure code are included.
- No production queue worker, webhook delivery service, or object-storage adapter is implemented.
- No centralized log shipping or alerting integration is bundled with the repository.

## Related Docs
- [Architecture](architecture.md)
- [Security](security.md)
- [Local Development](local-development.md)
- [Roadmap](roadmap.md)
