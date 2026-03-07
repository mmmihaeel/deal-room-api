# deal-room-api

API-first deal room backend for organization-scoped document collaboration, secure external sharing, and auditable access control.

## Business problem
Transaction teams (M&A, fundraising, legal diligence) need a controlled way to organize sensitive files, manage internal and external access, and prove who accessed what. `deal-room-api` models that workflow with explicit tenant boundaries, role-aware permissions, expiring share links, and immutable audit events.

## Core features
- Bearer token authentication with Laravel Sanctum
- Organization, membership, deal-space, folder, and document metadata CRUD
- Role-based and policy-based authorization (`owner`, `admin`, `member`, `viewer`)
- Deal-space permission grants (`view`, `upload`, `share`, `manage`)
- Share links with expiration, revoke support, and download limits
- Audit log querying with filters and organization scoping
- Search, sort, and pagination across major list endpoints
- Versioned API namespace at `/api/v1`

## Technology stack
- PHP 8.3, Laravel 10
- MySQL 8.4, Redis 7
- Apache 2.4 + PHP-FPM
- Docker Compose
- PHPUnit, Pint, PHPStan (Larastan)
- GitHub Actions CI

## Architecture decisions
- Controllers remain orchestration-focused; business rules live in services and policies.
- Form Requests handle validation at the edge of each write/read contract.
- Policies delegate complex checks to `AuthorizationService` for shared, testable rules.
- Resources define stable JSON responses.
- Migrations enforce referential integrity and query-oriented indexes.
- Audit logging is explicit for sensitive actions, not implicit via model events.

More detail: [docs/architecture.md](docs/architecture.md)

## Permissions and access control
Primary decision order:
1. Organization membership check
2. Organization role check
3. Optional deal-space permission override

| Role | Organization scope | Deal space | Document metadata | Share links | Audit logs |
| --- | --- | --- | --- | --- | --- |
| `owner` | Full control | Full control | Full control | Full control | Read |
| `admin` | Operational control | Full control | Full control | Full control | Read |
| `member` | Read | Read | Create/update/delete | Create/revoke | No |
| `viewer` | Read | Read | No | No (unless `share` grant) | No |

Deal-space permission grants can elevate a user for a specific deal space (`upload`, `share`, `manage`) without changing organization-level role.

## Share-link security design
- Random 64-character token generated on create.
- Only SHA-256 token hash is persisted; plaintext token is returned once.
- Public resolution endpoint enforces:
  - `expires_at` not passed
  - `revoked_at` is null
  - `download_count < max_downloads` when limit exists
- Resolution increments download count transactionally.
- Rate limiting is applied for login and public share-link resolution.

## Caching strategy
- Redis-backed cache for selected read-heavy list/show responses.
- Versioned cache keys via `CacheVersionService` (`domain + scope + version + params hash`).
- Write operations bump relevant version keys to invalidate stale list/show payloads.
- Share-link resolution caches token-hash lookup for short-lived acceleration.

## Quick start (Docker)
```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose ps
curl http://localhost:8080/api/v1/health
```

## Local quality and tests
```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec app composer quality
```

## Demo credentials
Seeded users all use password `Password123!`.
- `owner@acme.test`
- `admin@acme.test`
- `member@acme.test`
- `viewer@acme.test`
- `owner@northwind.test`

## Demo flow
1. Login and capture token.
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"owner@acme.test","password":"Password123!","device_name":"demo-cli"}'
```
2. Call authenticated endpoints with `Authorization: Bearer <access_token>`.
```bash
curl http://localhost:8080/api/v1/me -H "Authorization: Bearer <access_token>"
curl "http://localhost:8080/api/v1/organizations?per_page=5" -H "Authorization: Bearer <access_token>"
```
3. Create a share link for an existing document and resolve it publicly.
```bash
curl -X POST http://localhost:8080/api/v1/share-links \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{"document_id":1,"expires_at":"2030-01-01T00:00:00Z","max_downloads":3}'

curl http://localhost:8080/api/v1/share-links/<token>
```

## API overview
Base path: `/api/v1`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /me`
- `GET|POST|PUT|DELETE /organizations`
- `GET|POST|PUT|DELETE /deal-spaces`
- `GET|POST|PUT|DELETE /folders`
- `GET|POST|PUT|DELETE /documents`
- `GET|POST|DELETE /share-links`
- `GET /share-links/{token}` (public)
- `GET /audit-logs`
- `GET /health`

Full endpoint and filter details: [docs/api-overview.md](docs/api-overview.md)

## CI pipeline
GitHub Actions workflow (`.github/workflows/ci.yml`) runs:
- dependency install
- migration check
- Pint
- PHPStan (Larastan)
- PHPUnit
- Docker build verification (`app`, `apache`)

## Repository layout
```text
app/
  Enums/
  Http/Controllers/Api/V1/
  Http/Requests/
  Http/Resources/
  Models/
  Policies/
  Services/
database/
  factories/
  migrations/
  seeders/
docker/
  apache/
  php/
docs/
.github/workflows/
```

## Documentation
- [Architecture](docs/architecture.md)
- [Domain model](docs/domain-model.md)
- [API overview](docs/api-overview.md)
- [Security](docs/security.md)
- [Local development](docs/local-development.md)
- [Deployment notes](docs/deployment-notes.md)
- [Roadmap](docs/roadmap.md)

## Current limitations
- Document binary storage is out of scope; this project manages document metadata only.
- External identity provider integration is not included.
- OpenAPI artifact is not generated yet; endpoint behavior is documented in `docs/api-overview.md`.
