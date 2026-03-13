# Local Development

The repository ships a complete local stack for review and day-to-day development. You do not need a host PHP, MySQL, or Redis installation if Docker Compose is available.

## Prerequisites
- Docker Engine or Docker Desktop with Compose v2
- GNU Make if you want the convenience targets from `Makefile`

## Local Stack
| Service | Port | Purpose |
| --- | --- | --- |
| `apache` | `8080` | Public HTTP entrypoint for the API |
| `app` | Internal | Laravel runtime, Artisan, Composer, and quality tooling |
| `mysql` | `33061` | Local relational database |
| `redis` | `63791` | Local cache and share-link lookup acceleration |

## First-Time Setup
```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose ps
```

## Daily Commands
```bash
make up
make down
make restart
make logs
make ps
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

If you prefer not to use `make`, each target maps directly to a `docker compose exec ...` command defined in the repository `Makefile`.

## Seed Data and Demo Accounts
Seeded accounts all use `Password123!`.

- `owner@acme.test`
- `admin@acme.test`
- `member@acme.test`
- `viewer@acme.test`
- `owner@northwind.test`

The seed data also creates organizations, deal spaces, folders, documents, share links, and sample audit events so a reviewer can inspect the system without creating everything manually.

## Smoke Test Sequence
Health and routes:

```bash
curl -i http://localhost:8080/api/v1/health
docker compose exec app php artisan route:list --path=api/v1 --except-vendor
```

Login and inspect the authenticated surface:

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"owner@acme.test","password":"Password123!","device_name":"local"}'

curl http://localhost:8080/api/v1/me \
  -H "Authorization: Bearer <access_token>"
```

Quality gates:

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec app composer quality
```

## Troubleshooting
- If `/api/v1/health` returns `degraded`, inspect `docker compose ps` and `docker compose logs`.
- If dependencies were not installed yet, run `docker compose exec app composer install`.
- If you want a clean demo dataset, run `make fresh`.
- If route behavior looks stale after environment changes, recreate the stack with `make restart`.

## Related Docs
- [Architecture](architecture.md)
- [API Overview](api-overview.md)
- [Security](security.md)
- [Deployment Notes](deployment-notes.md)
