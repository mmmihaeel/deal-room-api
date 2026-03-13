# Domain Model

The domain centers on one idea: a deal room is not just a collection of records, but an organization-scoped workspace where access, sharing, and traceability are part of the model itself.

## Boundary View
![Domain overview diagram](../assets/readme/domain-overview.svg)

The organization is the primary tenant boundary. Every protected record in the repository is anchored to that scope either directly or through the deal space it belongs to.

## Core Entities
| Entity | Responsibility | Notable fields and behavior |
| --- | --- | --- |
| `User` | Authenticated actor | May belong to multiple organizations through memberships |
| `Organization` | Tenant root | Stores `name`, `slug`, `status`, and `owner_user_id`; uses soft deletes |
| `Membership` | Role assignment inside one organization | Unique on `organization_id + user_id`; roles are `owner`, `admin`, `member`, `viewer` |
| `DealSpace` | Transaction room inside one organization | Tracks `status`, `external_reference`, and `description`; uses soft deletes |
| `DealSpacePermission` | Per-user grant inside one deal space | Unique on `deal_space_id + user_id + permission`; effective elevation today comes from `upload`, `share`, and `manage` |
| `Folder` | Nested grouping for documents | Self-references `parent_id`; unique sibling names inside one deal space |
| `Document` | Metadata record for a file artifact | Tracks `title`, `filename`, `mime_type`, `size_bytes`, `checksum`, `metadata`, `version`, and `uploaded_at`; uses soft deletes |
| `ShareLink` | Public access handle for one document | Stores `token_hash`, `token_prefix`, expiry, download limits, access counters, and revocation state |
| `AuditLog` | Append-only security trail | Stores actor, organization, auditable target, request metadata, and structured context; no `updated_at` column |

## Relationship and Integrity Rules
- A membership is required before a user can access protected organization data.
- Deal spaces, folders, documents, and share links are all tied back to a single organization.
- Folder parents must belong to the same deal space as the child folder.
- Document folder assignment must remain inside the document's deal space.
- Share links inherit organization and deal-space scope from the document they expose.
- Only one membership row can exist for a given `organization_id + user_id`.
- Only one deal-space permission row can exist for a given `deal_space_id + user_id + permission`.

## Access Implications
- Membership establishes the baseline access boundary.
- `owner` and `admin` roles manage organizations, memberships, deal spaces, and audit visibility.
- `member` can work on documents and share links but cannot manage organization structure.
- `viewer` is read-only by default.
- Deal-space grants are per-room overrides. In the current implementation, `upload`, `share`, and `manage` affect behavior; `view` is stored and validated but is not required for read access because read access already follows organization membership.
- Public share-link resolution is the only flow that bypasses authenticated membership checks, and it remains bounded by token lifecycle validation and throttling.

## Lifecycle Notes
- Organizations, deal spaces, and documents are soft-deleted to preserve recovery and audit context.
- Folder deletion is hard delete and can null out document folder references when documents were attached to that folder.
- Document updates can optionally increment `version`.
- Share-link deletion through the API is revocation, not row removal.
- Audit logs are append-only from application code and are intended to retain the history of sensitive events.

## Reviewer Notes
- The repository models document metadata, not binary storage.
- Audit events are written explicitly in controller flows, which keeps security-sensitive behavior visible during review.
- The seeded demo data includes two organizations, multiple roles, several deal spaces, folders, documents, share links, and sample audit history.

## Related Docs
- [Architecture](architecture.md)
- [API Overview](api-overview.md)
- [Security](security.md)
- [Local Development](local-development.md)
