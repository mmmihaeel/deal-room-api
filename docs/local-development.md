# Local Development

## Prerequisites
- Docker Engine or Docker Desktop with Compose v2
- GNU Make (optional)

## First-time setup
```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose ps
```

## Sanity checks
```bash
curl -i http://localhost:8080/api/v1/health
docker compose exec app php artisan route:list --path=api/v1 --except-vendor
```

## Day-to-day commands
```bash
make up
make down
make restart
make logs
make shell
make migrate
make seed
make fresh
make test
make lint
make stan
make quality
make health
```

## Demo credentials
Seeded accounts all use `Password123!`.
- `owner@acme.test`
- `admin@acme.test`
- `member@acme.test`
- `viewer@acme.test`
- `owner@northwind.test`

## Quick manual verification flow
1. Login and capture `access_token`.
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"owner@acme.test","password":"Password123!","device_name":"local"}'
```
2. Call authenticated endpoints.
```bash
curl http://localhost:8080/api/v1/me -H "Authorization: Bearer <access_token>"
curl "http://localhost:8080/api/v1/organizations?per_page=5" -H "Authorization: Bearer <access_token>"
```
3. Verify public share-link resolution behavior with an issued token.

## Test and quality execution
```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec app composer quality
```
