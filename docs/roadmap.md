# Roadmap

The roadmap is intentionally conservative. The current repository already demonstrates the core backend concerns well, so future work should deepen that story rather than dilute it with unrelated features.

## Near-Term
- Generate an OpenAPI document and publish a reviewer-friendly API reference view.
- Add token scopes or MFA-ready extension points on top of the existing Sanctum flow.
- Expand request examples and fixture coverage around audit and share-link edge cases.

## Platform Extensions
- Introduce background jobs for heavier audit exports or reporting workflows.
- Add webhook delivery for selected organization or share-link events.
- Provide a stronger production deployment example around image release and runtime validation.

## Domain Expansion
- Add an object-storage adapter when the project moves beyond metadata-only documents.
- Support immutable export bundles for diligence or compliance review workflows.
- Consider cross-organization reporting only if the tenant model evolves to require operator-level visibility.

## Current Boundaries to Preserve
- Keep organization isolation explicit.
- Keep access control and audit behavior reviewable in code.
- Avoid adding binary storage or infrastructure claims before the implementation exists.

## Related Docs
- [Architecture](architecture.md)
- [Security](security.md)
- [Deployment Notes](deployment-notes.md)
