# Delivery status

## M0 + first M1 slice — implemented, local verification passed

Official Laravel/React foundation; authentication; workspace creation; tenant isolation;
permission registry; leads create/search/filter/pagination/status; versioned terminology;
transactional audit writes; security-focused feature tests; Bengali product blueprint in repository.
The blueprint's full M0/M1 acceptance criteria are not all complete.
See [verification report](VERIFICATION.md). Foundation PostgreSQL/SQLite CI passed; this phase's CI status is recorded separately.

## M1 team-management slice — implemented, local verification passed

- Owners invite verified recipients through manually shared, hashed, single-use 72-hour links.
- Revoke/reissue, member pagination, invitation history, non-owner role assignment and removal.
- Backend owner checks, cross-tenant isolation, stale-role rejection and immutable owner safeguards.
- Team mutations and redacted audit events commit or roll back together.
- PostgreSQL-only tests exercise independent competing connections against real service row locks; locally skipped because no PostgreSQL server is available. See CI verification status.

This is a scoped team-management increment, **not all of M1**. Ownership transfer and automatic
invitation email delivery are deferred. See [Bengali phase report](PHASE_1_TEAM_BN.md).

## Next increments

1. CRM detail/edit, activity timeline, customer conversion and duplicate handling, with tenant/permission/transaction tests.
2. Industry package manifests, module entitlements/dependencies, typed custom fields, draft/preview/publish/rollback.
3. Sales estimates/orders/invoices with explicit monetary and tax rules.
4. Inventory ledger, purchasing, reservations and idempotent posting.
5. Double-entry accounting, financial period controls and reconciliations.
6. Subscription billing, reseller provisioning and white-label domain verification.
7. Ownership transfer with reauthentication and last-owner protection; reliable invitation delivery with queue tenancy and retry tests.
8. Security/performance audit, browser E2E, disaster recovery, pilot businesses.

## Commercial readiness gate

Do not sell the current preview as a complete ERP.
Sell only explicitly scoped paid pilots after tenancy/security review and backup restoration pass.
Before general release: signed support scope, data migration/export, privacy/retention policy,
onboarding, billing reconciliation, incident runbook and demonstrated value with pilot customers.
Pricing and infrastructure costs require current vendor quotes; no live pricing is implied here.
