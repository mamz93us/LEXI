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

## 2026-05-25 — Phase 1/2/3/4 foundations landed in a single session

**Context:** Autonomous build session. Brief asked for all five phases. Realistic scope assessment landed on: full M1.1 + full M1.2 + Phase 2 foundations + Phase 3 + Phase 4 with the integrations that need external credentials clearly stubbed.

**Decision:**

Built and tested in this session (commits be02b9b → HEAD):
- **M1.1** — Laravel 12 + Livewire 3 + Breeze + Pest 3 + Pint; stancl/tenancy single-DB; BelongsToTenant scope; RTL Arabic shell; demo tenants samir + demo; Clients & Cases CRUD; tenant-aware login.
- **M1.2** — Reference data (5 lookup tables, OptionalTenantScope, 8 appeal-seat courts), cases extended (degree, court, type, circuit, roll_no, parent_case_id chain), hearings + structured `case_requests` (الطلبات), judgments + auto-deadline engine (JudgmentObserver + DeadlineCalculator + ScheduleDeadlineReminders job), case detail page with chain viewer + "Create استئناف" action, calendar.
- **Phase 2** — ArabicNormalizer + LegalChunker (used identically on ingest + query); 9 migrations for documents/document_versions/templates/template_versions/clauses/clause_versions/proxies/ai_generations/contract_embeddings (pgvector column on Postgres only); EmbeddingDriver interface with Null + Cohere drivers + Manager; RagRetrievalService with hard SQL `WHERE tenant_id = ?` filter and a lexical-LIKE fallback for SQLite tests; AnthropicClient wrapper with zero-retention header; PromptAssembler that forbids fabricated citations; RagGenerator orchestrator; TokenReplacer (literal `{{name}}` only — no expression eval) + DocumentGenerator producing DOCX via PHPWord; ConvertDocxToPdf job that shells out to soffice; ReviewWorkflow state machine (draft → reviewed → approved → locked).
- **Phase 3** — companies + company_formation_steps + shareholders + compliance_items + serials + ip_assets schema, models, relationships; Livewire Companies Index + Form; sidebar entry.
- **Phase 4** — time_entries, invoices + invoice_lines (per-tenant unique number, EGP in piastres, 14% VAT, Invoice::recalculate() resolves status from line totals + payment sum), payments. Livewire Invoices Index/Form with line repeater. Dashboard Livewire component with 5 stat tiles.

**Test coverage at hand-off:** 47 Pest tests, 109 assertions, all green. Pint clean. Coverage spans tenant isolation, RAG retrieval isolation, ArabicNormalizer parity, LegalChunker structural splits, TokenReplacer safety, the full litigation chain + hearings + auto-deadline flow, invoice VAT and status transitions, login per-tenant scope.

**What is deliberately NOT built (each blocked by something you must provide):**

| Gap | What unblocks it |
|---|---|
| **Live Claude API calls** | `ANTHROPIC_API_KEY` in `.env` + signed zero-retention agreement with Anthropic. Wrapper exists and throws a remediation message until set. |
| **Real embedding model decision** | 20–30 real Arabic firm contracts to run through the retrieval-quality harness against Cohere v3 / Voyage / BGE-M3 candidates. `EmbeddingDriverManager` is ready to swap. |
| **Production OCR (Tesseract `ara`)** | Tesseract container running. The Phase 2 stub in `docker-compose.yml` is commented out; uncomment after Docker is up. |
| **PDF conversion** | LibreOffice headless on PATH (or the placeholder `soffice` container). `ConvertDocxToPdf` job logs and skips when not found — never fails silently. |
| **S3 storage** | AWS keys + bucket in `.env`. Code already writes to the configured filesystem disk; switching from `local` → `s3` is a config change. |
| **Egyptian litigation deadline day-counts** | A practicing Egyptian lawyer must verify the placeholder values in `config/lexa_deadlines.php` (each flagged `// VERIFY WITH LAWYER`). Missing this is a malpractice exposure — do not ship without it. |
| **Paymob / Fawry billing** | Merchant credentials. Cashier is not yet wired. The invoice + payment tables exist and `Invoice::recalculate()` handles the math. |
| **Browser end-to-end verification** | Docker Desktop installation + hosts-file edits. Once Docker is up, `docker compose up -d` + `php artisan migrate --seed` + `php artisan serve` reaches the full app. |
| **Audit-log package selection** | A choice between `owen-it/laravel-auditing` and a custom `audit_logs` table. Decide before writing the first audit hook. |
| **Hijri conversion library** | A vetted library + a Pest test for boundary cases. Display is currently Gregorian only. |
| **Branding** | Logo, primary colors, typography weights. Tailwind theme has placeholder `lexa-{50..900}` blue palette. |

**Consequences / how to use this codebase going forward:**
- Every new tenant-scoped model must use `BelongsToTenant`. Every new lookup table that allows tenant additions must use `OptionallyBelongsToTenant`.
- Every new tenant feature ships with at least one Pest isolation test. Treat that as part of "done" — no exceptions.
- Money is always stored as `bigInteger ..._piastres` and converted at the form boundary only.
- The system prompt in `PromptAssembler` is the *guardrail* against fabricated citations. Do not loosen it without a partner review.
- The `ai_generations` row is written BEFORE the LLM call so the audit trail survives a failed call.
- `TokenReplacer` only handles `{{identifier}}` — never extend it to evaluate expressions; the resulting document goes to a court.

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
