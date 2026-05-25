# LEXA Architecture Decisions (ADR log)

Every non-trivial architectural choice gets one entry. Newest at the top.

Format:
```
## YYYY-MM-DD — Decision title
**Context:** why this came up
**Decision:** what was chosen
**Alternatives considered:** what else was on the table
**Consequences:** what this means going forward
```

---

## 2026-05-25 — Stack baseline locked, Laravel version bumped to 12, single-DB tenancy, VPS deploy target

**Context:** First architectural commit of the project. The original LEXA brief specified Laravel 11, but Laravel 12 is the current stable. We also needed to decide single-DB vs DB-per-tenant for the first release, the local infra approach, and the production deployment target.

**Decision:**
- **Framework:** Laravel 12 (PHP 8.3+). Replaces the brief's Laravel 11 line.
- **Tenancy:** `stancl/tenancy` in **single-DB mode**. We add our own `tenant_id` column + `BelongsToTenant` trait + global scope. Stancl's automatic database switching is *off*. DB-per-tenant kept open as a future premium tier.
- **Tenant routing:** subdomain primary (`{slug}.lexa.test` locally → `{slug}.lexa.app` in prod). Path-based fallback (`/t/{slug}/...`) supported for contributors who cannot edit the hosts file.
- **Local infra:** Docker Compose. Services: `pgvector/pgvector:pg16` Postgres, `redis:7-alpine`, `getmeili/meilisearch:v1.x`. Phase 2 adds soffice + tesseract containers.
- **Production deploy target:** VPS / dedicated server with root access (DigitalOcean, Hetzner, Linode, or cPanel VPS). **NOT shared cPanel** — LEXA requires root-installable Postgres extensions (pgvector), long-running daemons (Redis, Meilisearch, Horizon), and headless binaries (LibreOffice, Tesseract) that shared cPanel cannot host.
- **Auth:** Laravel Breeze (Livewire 3 stack).
- **Test runner:** Pest 3.
- **Code style:** Laravel Pint (PSR-12-based).

**Alternatives considered:**
- *Laravel 11:* the brief's original lock. Rejected because L12 is stable and supported, and the user explicitly requested the latest version.
- *DB-per-tenant from day one:* simpler isolation guarantee, much higher operational cost (one DB per firm, harder reporting, harder backups). Single-DB with strict global scoping + a hard SQL `WHERE tenant_id = ?` on vector queries gives equivalent practical safety at a fraction of the ops burden.
- *Shared cPanel hosting:* would force us to drop Postgres+pgvector, Redis, Meilisearch, and Horizon, which would gut the RAG feature. Architecturally incompatible.
- *Hosted Postgres (Supabase / Neon):* viable for dev or as a backup plan. Rejected as default because it splits production data across vendors and complicates the in-Egypt data residency story for clients with regulatory pressure.

**Consequences:**
- Every tenant-scoped model MUST use the `BelongsToTenant` trait. Migrations get a `tenant_id` foreign key.
- The vector retrieval service MUST include `WHERE tenant_id = ?` in the SQL itself, in addition to the global scope (defense in depth).
- A Pest tenant-isolation test ships with every new tenant-scoped feature — treat as part of "done."
- Dev requires Docker Desktop; the README must spell that out.
- The brief in `CLAUDE.md` has been updated to say Laravel 12. The original brief reference (which still says Laravel 11 in §2) should be reconciled the next time the brief is re-issued.

---

## Open decisions (placeholder — fill these in as they're resolved)

- **Embedding model.** Defer; choose after a Phase 2 retrieval-quality eval on real Arabic firm contracts.
- **Egyptian litigation deadline day-counts.** Placeholder values in `config/lexa_deadlines.php` flagged `// VERIFY WITH LAWYER`. Need a practicing Egyptian lawyer to confirm civil/commercial/criminal/admin first-instance→appeal and appeal→cassation windows before any production deploy.
- **Hijri conversion library.** Decide during Phase 1 implementation.
- **Audit-log package vs custom.** `owen-it/laravel-auditing` is the safe default; custom is a minimal `audit_logs` table. Decide before writing the first audit hook.
- **Branding** (logo, primary colors, typography weights). Needed before Phase 4 client portal polish.
- **Production VPS provider, region, S3 bucket, Anthropic API contract.** Needed before Phase 2 ships.
- **Paymob/Fawry sandbox credentials.** Needed before Phase 4 billing work.
