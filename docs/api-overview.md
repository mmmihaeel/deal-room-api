# API Overview

Base path: `/api/v1`

Authentication for protected endpoints uses Sanctum Bearer tokens:
`Authorization: Bearer <access_token>`

## Response conventions
- Success payloads are wrapped in `data`.
- Validation errors return `422` with `message` and `errors` object.
- Authentication failures return `401`.
- Authorization failures return `403`.
- Missing resources return `404`.

Pagination uses Laravel paginator JSON structure (`data`, `links`, `meta`).

## Access control summary
- All protected endpoints require an active user account.
- Data access is organization-scoped through membership.
- Role baseline: `owner`, `admin`, `member`, `viewer`.
- Deal-space permission grants may elevate capabilities for a specific deal space.
- Audit log listing is limited to `owner` and `admin`.

## Endpoint map

| Area | Method | Path | Auth | Notes |
| --- | --- | --- | --- | --- |
| Auth | `POST` | `/auth/login` | No | Returns bearer token |
| Auth | `POST` | `/auth/logout` | Yes | Revokes current token |
| Auth | `GET` | `/me` | Yes | Current user profile |
| Health | `GET` | `/health` | No | Service/dependency check |
| Organizations | `GET/POST` | `/organizations` | Yes | List/create |
| Organizations | `GET/PUT/DELETE` | `/organizations/{organization}` | Yes | Read/update/delete |
| Memberships | `GET/POST` | `/memberships` | Yes | List/create within org |
| Memberships | `PUT/DELETE` | `/memberships/{membership}` | Yes | Update/remove member |
| Deal spaces | `GET/POST` | `/deal-spaces` | Yes | List/create |
| Deal spaces | `GET/PUT/DELETE` | `/deal-spaces/{deal_space}` | Yes | Read/update/delete |
| Deal-space permissions | `GET` | `/deal-spaces/{deal_space}/permissions` | Yes | List grants |
| Deal-space permissions | `PUT` | `/deal-spaces/{deal_space}/permissions` | Yes | Upsert grants |
| Folders | `GET/POST` | `/folders` | Yes | List/create |
| Folders | `GET/PUT/DELETE` | `/folders/{folder}` | Yes | Read/update/delete |
| Documents | `GET/POST` | `/documents` | Yes | List/create metadata |
| Documents | `GET/PUT/DELETE` | `/documents/{document}` | Yes | Read/update/delete |
| Share links | `GET/POST` | `/share-links` | Yes | List/create |
| Share links | `DELETE` | `/share-links/{shareLink}` | Yes | Revoke |
| Share links | `GET` | `/share-links/{token}` | No | Public resolution |
| Audit logs | `GET` | `/audit-logs` | Yes | Owner/admin only |

## Filtering and sorting

## Organizations
- Filters: `search`, `status`
- Sort: `sort`, `direction`
- Pagination: `per_page`

## Memberships
- Required: `organization_id`
- Optional: `role`, `search`, `sort`, `direction`, `per_page`

## Deal spaces
- Optional: `organization_id`, `status`, `search`, `sort`, `direction`, `per_page`

## Folders
- Required: `deal_space_id`
- Optional: `organization_id`, `parent_id`, `search`, `sort`, `direction`, `per_page`

## Documents
- Required: `deal_space_id`
- Optional: `organization_id`, `folder_id`, `search`, `mime_type`, `sort`, `direction`, `per_page`

## Share links
- Optional: `organization_id`, `deal_space_id`, `document_id`, `status`, `sort`, `direction`, `per_page`

## Audit logs
- Optional: `organization_id`, `actor_user_id`, `event`, `from`, `to`, `sort`, `direction`, `per_page`

## Share-link lifecycle behavior
- `POST /share-links` creates link and returns plaintext token once.
- `GET /share-links/{token}` returns `404` when token is expired, revoked, unknown, or over limit.
- Each successful resolution increments `download_count`.
- `DELETE /share-links/{shareLink}` sets `revoked_at` and blocks further resolution.

## Example login request
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"owner@acme.test","password":"Password123!","device_name":"cli"}'
```
