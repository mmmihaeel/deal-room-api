# Security Notes

## Authentication
- API authentication is token-based with Laravel Sanctum.
- Login: `POST /api/v1/auth/login`
- Logout: `POST /api/v1/auth/logout` (revokes current token)
- Tokens are scoped to authenticated users and never exposed in logs by application code.

## Permissions and access control
Protected resource access follows strict organization boundaries:
1. User must be a member of the organization.
2. Membership role gates baseline capabilities.
3. Deal-space permission grants can elevate access for a specific deal space.

Role intent:
- `owner`: full organization control
- `admin`: operational control
- `member`: operational work on deal artifacts
- `viewer`: read-only access by default

Deal-space grants:
- `view`, `upload`, `share`, `manage`

## Validation and input hardening
- Mutation endpoints use Form Requests.
- Server-side validation enforces:
  - enum values
  - foreign-key existence
  - date constraints (`expires_at` in future)
  - payload shape and size constraints

## Share-link security
- Plain tokens are generated once and not persisted.
- DB stores only token hashes (`sha256`).
- Resolution checks:
  - token exists
  - not expired
  - not revoked
  - not over download limit
- Resolution endpoint is rate-limited and lookup-cached.

## Audit trail
Sensitive flows write append-only events to `audit_logs`, including:
- actor (nullable for public token resolution)
- organization
- event key
- auditable target reference
- request metadata (IP, user-agent)
- structured context payload

## Caching strategy and safety
- Redis stores versioned API cache entries and short-lived share-link lookup data.
- Versioned keys reduce stale reads and avoid broad wildcard cache flushes.
- Write operations explicitly bump cache versions for affected domains/scopes.
- Authorization still runs on protected routes; cache is a performance layer, not an authorization shortcut.

## Data integrity controls
- Foreign keys enforce organization/deal/document consistency.
- Composite unique constraints prevent duplicate memberships and duplicate permission grants.
- Query-driven indexes support predictable filtering and audit retrieval patterns.

## Operational recommendations
- Enforce HTTPS and secure reverse proxy headers in production.
- Keep MySQL and Redis on private networks only.
- Rotate credentials and secrets regularly.
- Add centralized alerting for repeated auth failures and suspicious share-link activity.
