# Delivery status

## M0 + first M1 slice — implemented, local verification passed

Official Laravel/React foundation; authentication; workspace creation; tenant isolation;
permission registry; leads create/search/filter/pagination/status; versioned terminology;
transactional audit writes; security-focused feature tests; Bengali product blueprint in repository.
The blueprint's full M0/M1 acceptance criteria are not all complete.
See [verification report](VERIFICATION.md). PostgreSQL CI is configured, not locally executed.

## Next increments

1. Team invitations, role management, owner transfer safeguards.
2. CRM detail/edit, activity timeline, customers and duplicate handling.
3. Industry package manifests, module entitlements/dependencies, typed custom fields, draft/preview/publish/rollback.
4. Sales estimates/orders/invoices with explicit monetary and tax rules.
5. Inventory ledger, purchasing, reservations and idempotent posting.
6. Double-entry accounting, financial period controls and reconciliations.
7. Subscription billing, reseller provisioning and white-label domain verification.
8. Security/performance audit, browser E2E, disaster recovery, pilot businesses.

## Commercial readiness gate

Do not sell the current preview as a complete ERP.
Sell only explicitly scoped paid pilots after tenancy/security review and backup restoration pass.
Before general release: signed support scope, data migration/export, privacy/retention policy,
onboarding, billing reconciliation, incident runbook and demonstrated value with pilot customers.
Pricing and infrastructure costs require current vendor quotes; no live pricing is implied here.
