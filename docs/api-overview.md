# API Overview

Base path: `/api/v1`

Protected endpoints use Laravel Sanctum bearer tokens:

```text
Authorization: Bearer <access_token>
```

## Response Conventions
- Successful payloads are wrapped in `data`.
- Validation failures return `422` with `message` and `errors`.
- Authentication failures return `401`.
- Authorization failures return `403`.
- Missing resources return `404`.
- Paginated responses follow Laravel's `data`, `links`, and `meta` structure.

## Access Summary
- All protected endpoints require an active authenticated user.
- Protected data is organization-scoped through membership.
- Membership roles provide the baseline capability set.
- Effective per-room elevation today comes from `upload`, `share`, and `manage`.
- Audit log listing is limited to privileged roles in accessible organizations.

## Endpoint Families
| Area | Method | Path | Auth | Notes |
| --- | --- | --- | --- | --- |
| Auth | `POST` | `/auth/login` | No | Returns a bearer token and user payload |
| Auth | `POST` | `/auth/logout` | Yes | Revokes the current token |
| Identity | `GET` | `/me` | Yes | Returns the current user and accessible organizations |
| Health | `GET` | `/health` | No | Checks application dependency health |
| Organizations | `GET/POST` | `/organizations` | Yes | List or create organizations |
| Organizations | `GET/PUT/DELETE` | `/organizations/{organization}` | Yes | Show, update, or delete |
| Memberships | `GET/POST` | `/memberships` | Yes | List or create organization memberships |
| Memberships | `PUT/DELETE` | `/memberships/{membership}` | Yes | Update or remove a membership |
| Deal spaces | `GET/POST` | `/deal-spaces` | Yes | List or create rooms |
| Deal spaces | `GET/PUT/DELETE` | `/deal-spaces/{deal_space}` | Yes | Show, update, or delete |
| Deal-space grants | `GET` | `/deal-spaces/{deal_space}/permissions` | Yes | List per-user grants |
| Deal-space grants | `PUT` | `/deal-spaces/{deal_space}/permissions` | Yes | Replace per-user grant sets |
| Folders | `GET/POST` | `/folders` | Yes | List or create folders inside one room |
| Folders | `GET/PUT/DELETE` | `/folders/{folder}` | Yes | Show, update, or delete |
| Documents | `GET/POST` | `/documents` | Yes | List or create document metadata records |
| Documents | `GET/PUT/DELETE` | `/documents/{document}` | Yes | Show, update, or delete |
| Share links | `GET/POST` | `/share-links` | Yes | List or create share links |
| Share links | `DELETE` | `/share-links/{shareLink}` | Yes | Revoke an existing share link |
| Share links | `GET` | `/share-links/{token}` | No | Public resolution endpoint |
| Audit logs | `GET` | `/audit-logs` | Yes | Query organization audit events |

## Query Patterns
| Area | Required selector | Optional filters | Sort options |
| --- | --- | --- | --- |
| Organizations | None | `search`, `status`, `per_page` | `name`, `status`, `created_at` |
| Memberships | `organization_id` | `role`, `search`, `per_page` | `created_at`, `role`, `joined_at` |
| Deal spaces | None | `organization_id`, `status`, `search`, `per_page` | `name`, `status`, `created_at` |
| Folders | `deal_space_id` | `organization_id`, `parent_id`, `search`, `per_page` | `name`, `created_at` |
| Documents | `deal_space_id` | `organization_id`, `folder_id`, `search`, `mime_type`, `per_page` | `title`, `version`, `uploaded_at`, `created_at` |
| Share links | None | `organization_id`, `deal_space_id`, `document_id`, `status`, `per_page` | `created_at`, `expires_at`, `download_count` |
| Audit logs | None | `organization_id`, `actor_user_id`, `event`, `from`, `to`, `per_page` | `created_at`, `event` |

`direction` accepts `asc` or `desc` wherever sorting is supported.

## Key Workflows
Login:

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"owner@acme.test","password":"Password123!","device_name":"cli"}'
```

Create and resolve a share link:

```bash
curl -X POST http://localhost:8080/api/v1/share-links \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{"document_id":1,"expires_at":"2030-01-01T00:00:00Z","max_downloads":3}'

curl http://localhost:8080/api/v1/share-links/<token>
```

## Share-Link Lifecycle
- `POST /share-links` creates a link and returns the plaintext token once.
- `GET /share-links/{token}` returns `404` when the token is expired, revoked, unknown, or over its download limit.
- Each successful public resolution increments `download_count`.
- `DELETE /share-links/{shareLink}` revokes the link by setting `revoked_at`.

## Rate Limits
- General API traffic: `120` requests per minute by authenticated user ID or request IP.
- Login: `10` requests per minute by `IP + email`.
- Public share-link resolution: `30` requests per minute per token hash and `120` requests per minute per IP.

## Related Docs
- [Architecture](architecture.md)
- [Domain Model](domain-model.md)
- [Security](security.md)
- [Local Development](local-development.md)
