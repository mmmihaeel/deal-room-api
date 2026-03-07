# Deployment Notes

## Container strategy
This repository ships a local Docker Compose stack for development. For production:
- build immutable `app` image from `docker/php/Dockerfile`
- front with Apache or Nginx reverse proxy
- run MySQL and Redis as managed services where possible

## Required environment variables
- `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_*` for MySQL
- `REDIS_*` for cache
- `CACHE_DRIVER=redis`

## Production baseline checklist
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Enable HTTPS
- Use strong secrets and rotated credentials
- Run `php artisan config:cache` and `php artisan route:cache`
- Run migrations as part of release workflow

## Suggested release sequence
1. Build and publish container image.
2. Run quality gates (`lint`, `stan`, `test`) in CI.
3. Deploy image.
4. Run `php artisan migrate --force`.
5. Smoke test `/api/v1/health` and auth flow.

## Observability recommendations
- Collect structured application logs.
- Monitor HTTP 4xx/5xx rates and MySQL/Redis health.
- Alert on repeated share-link resolution failures and abnormal audit-log spikes.
