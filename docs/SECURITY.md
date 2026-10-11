# Security status and threat checklist

This repository is an early foundation, not a security-certified product.
Report a suspected security issue privately to the repository owner; do not publish customer data or exploit tokens.

## Covered by foundation tests

Non-member workspace access, cross-workspace lead identifiers, filtered search isolation,
read-only role writes, forged tenant_id, status audit, configuration ownership/version conflict,
and missing tenant context after a request.

## Before accepting live customer data

1. Add invitation token hashing, expiry, one-use acceptance and role escalation tests.
2. Test raw SQL, exports, uploads, queue workers and caches for tenant isolation.
3. Enforce TLS, secure cookies, least-privileged DB access, MFA for administrators and secret rotation.
4. Verify recovery from backups into an isolated environment.
5. Add application quotas, mutation rate limits, monitoring and incident response.
6. Add CSP and upload validation; do not allow executable tenant configuration.
7. Define retention/deletion/export procedures, access logs and privacy agreements.
8. Run PostgreSQL transaction/race tests and an independent security review.

App global scopes do not secure direct DB access. Tenant row-level policies are not yet present.
Audit metadata avoids contact values, but the application still stores customer PII in leads.
Logs, backups and support access must be protected accordingly.
No penetration testing or live production deployment has been completed.

## Dependency advisory handled in foundation

The initial starter's formatter chain pinned tinypool 2.1.0.
An npm override pins patched tinypool 2.1.2; remove it only after the formatter dependency
itself supplies a patched version and checks/audit pass.
Advisory: https://github.com/advisories/GHSA-85c8-ppgw-ccpr
An audit with no reported vulnerabilities is not proof the application is secure.
