# Foundation architecture

## Request boundaries

Authenticated verified user -> workspace membership middleware -> TenantContext -> permission middleware -> controller -> domain service -> tenant-scoped model -> transaction and audit.
Non-members see 404; members without a permission see 403.
Lead IDs from another workspace also return 404.
TenantContext is request-scoped and cleared in finally.
Model global scopes are a defense layer, not database RLS; raw queries need explicit tenant predicates.
No database-level tenant RLS is implemented yet.

## Modules

- Tenancy: workspace, memberships, role registry and fail-closed model trait.
- CRM: lead records and authorized transitions between the five basic statuses.
- Configuration: tenant-specific terminology snapshots and optimistic version check.
- Audit: append-only application writes of metadata for covered operations. DB credentials can still alter rows; DB immutability controls are a future hardening task.

Configuration publishing locks the tenant row then compares expected version inside the transaction.
Unique (tenant_id, version) is an additional guard.
SQLite tests verify behavior, not PostgreSQL row-lock concurrency.
Only terminology is configurable today. The universal customization blueprint is the target design:
shared core -> industry package -> tenant overrides. Industry selection currently records metadata only.

## Data ownership

User is global; tenant membership grants role per workspace.
Tenant-owned lead/configuration/audit rows reference a tenant.
Foreign keys prevent orphaning audited tenants. Hard-delete UI is intentionally absent.
Leads do not deduplicate yet. Source labels such as WhatsApp do not imply a live integration.
Member provisioning lacks invitation UI; never add an unauthenticated membership-write endpoint.

## Production blockers

- PostgreSQL integration/concurrency tests, backup/restore drill and migration rollout/rollback.
- Invitations, owner-transfer protections, audit viewer, retention and deletion policy.
- Rate limits/quotas beyond workspace creation, abuse controls and observability.
- Full Bengali localization, timezone-aware UI, accessibility and browser E2E tests.
- Queue worker tenancy tests and integration idempotency before enabling async jobs.
- Subscription billing, provisioning, domain verification and support tooling.
- Formal security review, tenant-isolation adversarial tests and financial compliance review.

## Deployment target

Containerized PHP application + web server, PostgreSQL, Redis queues/cache, object storage,
TLS load balancer, separate staging, CI migrations and tested backup restoration.
Use managed services initially when their support and operational cost suit the budget.
No live deployment is configured by this milestone.
Production must disable APP_DEBUG and use distinct rotated secrets.
Never run composer dev or the Vite development server as production hosting.
