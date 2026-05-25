# CLAUDE.md — LEXA Project Context & Build Instructions

> This file is the persistent project brief for **Claude Code**. Read it fully before writing any code. It defines what we are building, the stack, the architecture decisions already made, the rules you must follow, and the phased build order. When in doubt, follow this document over generic conventions. Ask before deviating from any decision marked **[LOCKED]**.

---

## 1. What we are building

**LEXA** — a multi-tenant SaaS platform for **Egyptian law firms**. It unifies litigation management, corporate/company services, government paperwork tracking, document automation, and firm business operations in one system. A core differentiator is an **AI contract generator powered by RAG (Retrieval-Augmented Generation)** over each firm's own contract archive, drafting **primarily in Arabic**.

- **Primary users:** law firm partners, associates, corporate/formation teams, paralegals; optional client portal.
- **Market:** Egypt (Cairo-led), Arabic-first with English support. Scalable to MENA later.
- **Each firm is an isolated tenant** with its own users, data, clause library, templates, branding, and AI knowledge base.

### Non-negotiable product principles
1. **Confidentiality is paramount.** This is legal data under client privilege. Tenant isolation is absolute — Firm A must NEVER access Firm B's data, including via AI retrieval.
2. **Human-in-the-loop for all legal output.** AI drafts; a lawyer reviews and approves. Nothing AI-generated is ever auto-finalized. UI must always label AI output as "AI draft — pending review" (مسودة آلية — قيد المراجعة).
3. **Arabic-first.** RTL throughout, Arabic legal terminology, Arabic-aware search and embeddings.
4. **Regenerate-from-source.** Generated documents are stored as (template version + clause versions + filled data JSON), never as a flat blob. Output is regenerated, which makes versioning trustworthy and lets one source produce both DOCX and PDF.

---

## 2. Tech stack **[LOCKED]**

| Layer | Choice | Notes |
|---|---|---|
| Backend | **Laravel 12** (PHP 8.3+) | Primary framework. (Decision 2026-05-25: upgraded from the original brief's Laravel 11 spec.) |
| Multi-tenancy | **stancl/tenancy** | Start single-DB with `tenant_id` column; design so DB-per-tenant is possible later as a premium tier |
| Frontend | **Livewire 3 + Alpine.js**, Tailwind CSS | Server-driven, simpler than SPA for this team. RTL-first Tailwind config |
| Database | **PostgreSQL 16** | Use JSON/JSONB columns for filled-document data |
| Vector store | **pgvector** extension on the same Postgres | Do NOT add a separate vector DB. Tenant scoping via `WHERE tenant_id = ?` |
| Queue / async | **Redis + Laravel Horizon** | OCR, PDF conversion, embedding generation all run as jobs |
| Search | **Meilisearch** (Arabic analyzer) via Laravel Scout | Full-text search of documents/cases |
| File storage | **S3-compatible** | Per-tenant key prefixes; versioned objects |
| DOCX generation | **PHPWord** (or PHPOffice) | Token replacement + conditional sections; output stays editable |
| PDF generation | **LibreOffice headless** (`soffice --convert-to pdf`) | Convert the generated DOCX → PDF as a queued job. One source, identical output |
| OCR | **Tesseract** with `ara` language model | Background job on scanned uploads |
| LLM API | **Anthropic Claude API** (primary) | Use a server-side key only. Use zero-retention / no-training tier |
| Embeddings | **TBD — must be tested on real Arabic legal text** | Candidates: Cohere embed-multilingual-v3, Voyage multilingual, or self-hosted BGE-M3. See §6 |
| Billing | **Laravel Cashier** + Paymob/Fawry | Egyptian payment rails; usage metering |
| Notifications | Mail + **WhatsApp via Green API** | Reminders, alerts, client updates |

### Deployment target [LOCKED]
**VPS / dedicated server** (DigitalOcean / Hetzner / Linode / cPanel VPS with root access). **NOT shared cPanel hosting.** LEXA's stack requires root-installable extensions (pgvector), long-running daemons (Redis, Meilisearch, Horizon worker), and headless binaries (LibreOffice, Tesseract), none of which are available on shared cPanel plans.

### Conventions
- Follow Laravel conventions and PSR-12. Run **Laravel Pint** before considering work done.
- Use **Form Requests** for validation, **Policies** for authorization, **Actions/Services** for business logic (thin controllers).
- Tests with **Pest**. Every feature ships with at least feature tests for the happy path + tenant-isolation test.
- All money stored as integer **piastres** (EGP × 100), never floats.
- All user-facing strings go through Laravel localization (`lang/ar` primary, `lang/en`). Never hardcode Arabic/English in Blade.
- Migrations are the source of truth for schema. Never edit the DB directly.

---

## 3. Multi-tenancy rules **[LOCKED]**

- Every tenant-owned table has a `tenant_id` (foreign key to `tenants`). A **global scope** auto-filters every query by the current tenant. No query may bypass this without an explicit, reviewed reason.
- A `BelongsToTenant` trait + global scope is applied to all tenant models. New tenant models MUST use it.
- **The vector similarity search MUST include the tenant filter in the SQL itself**, not only in app code — defense in depth. Write a test that proves Firm A cannot retrieve Firm B's vectors.
- Tenant resolution is by subdomain (primary) or path (fallback for dev). Production default: subdomain `{firm}.lexa.app`.
- Central (landlord) database holds: tenants, plans, subscriptions, global users-to-tenant mapping. Everything else is tenant-scoped.

---

## 4. Core data model (initial)

Model these as migrations + Eloquent models. This is the starting shape; refine as needed but keep the relationships.

**Central / landlord**
- `tenants` (firm) — name, slug, plan, settings JSON, branding JSON
- `users` — belongs to a tenant; role enum: `partner | associate | paralegal | admin | client`
- `subscriptions` (Cashier)

**Practice**
- `clients` — type: `individual | company`; national_id / commercial_register_no; contact; balance
- `matters` — polymorphic-ish parent for work; type: `litigation | formation | service`; belongs to client; assigned users (pivot)
- `cases` — extends a matter. Egyptian-litigation-aware; see **§4A** for the full court/case/hearing model. Key fields: case_number, court_id (FK to seeded courts), circuit (الدائرة), roll_no (الرول), case_type_id (FK), degree (`partial | first_instance | appeal | cassation`), parent_case_id (self-ref linking stages/طعون), status, dispute_value, party roles (مدعي/مدعى عليه)
- `hearings` — belongs to case; session_date, purpose, decision/roll outcome, postponement_reason (سبب التأجيل), next_date; has many `case_requests`. Generates reminders. See §4A
- `case_requests` (الطلبات) — structured per-hearing requests; see §4A
- `judgments` (الأحكام) — type & finality; see §4A
- `proxies` (التوكيلات) — type: `general | specific`; client_id (principal); authorized lawyers (pivot); notary_serial; issue_date; expiry_date; scope; status: `valid | expiring | expired | revoked`; linked to cases (pivot)
- `deadlines` — polymorphic; due_date; type; auto-calculated (e.g. appeal/cassation window from ruling date — see §4A.5); alert offsets

**Corporate**
- `companies` — belongs to client; legal_form (`llc | jsc | sole | branch`); commercial_register_no; tax_card_no; gafi_file_no; capital; activity_codes; status; formation_stage
- `company_formation_steps` — belongs to company; ordered checklist; status; responsible_user; authority; fees; expected_date; actual_date
- `shareholders` — belongs to company; client/person; ownership_pct; change history
- `compliance_items` — belongs to company; type (`cr_renewal | vat | tax | social_insurance | agm | auditor | license`); due_date; recurrence; status
- `ip_assets` — trademarks/patents/copyrights; class; office_serial; renewal_date; status

**Government paperwork**
- `serials` — serial_no; document_name; issuing_authority; linked to case or company (polymorphic); fees; date; status (`pending | issued | collected`); scan file ref

**Documents**
- `documents` — title; type; linked matter (polymorphic); current_version_id; format; storage_ref; ocr_text
- `document_versions` — belongs to document; version_no; the generation source: template_version_id + clause_version_ids[] + filled_data (JSONB); storage_ref; created_by; locked (bool)
- `templates` → `template_versions` — token-based; variable definitions JSON
- `clauses` → `clause_versions` — reusable approved blocks; condition expression; topic
- `audit_logs` — actor, action, subject (polymorphic), before/after, timestamp

**RAG / AI**
- `contract_embeddings` — tenant_id; document_id; chunk_index; chunk_text (normalized Arabic); metadata JSONB (contract_type, article_topic, date, language); `embedding vector(N)` (pgvector); source_version_id
- `ai_generations` — tenant_id; matter_id; prompt snapshot; retrieved_chunk_ids[]; model; output; status (`draft | reviewed | approved | rejected`); reviewed_by

---

## 4A. Egyptian litigation module — courts, cases, hearings, requests, judgments **[LOCKED structure]**

This section makes the litigation core faithful to how Egyptian courts actually work. Model these as **seeded reference (lookup) tables** + transactional tables. All labels are stored bilingually (`name_ar`, `name_en`); Arabic is canonical. **The specific numeric values (appeal/cassation deadline counts, jurisdiction monetary thresholds, fees) MUST be stored as editable config/seed data and verified by a practicing Egyptian lawyer — do not hardcode them in logic.**

### 4A.1 Court hierarchy (ordinary judiciary — القضاء العادي)
Egypt litigates on **two degrees** (التقاضي على درجتين); cassation is a court of law, not a third degree. Seed a `courts` table with a `court_type` and a self-referential `parent_id` so circuits/branches can nest.

`court_types` seed (`code` → `name_ar` / `name_en`):
- `partial` → المحكمة الجزئية / Partial (Summary) Court — first degree, lower-value claims
- `first_instance` → المحكمة الابتدائية / Court of First Instance — main first-degree court; also hears appeals from partial courts
- `appeal` → محكمة الاستئناف / Court of Appeal — second degree (reviews facts + law)
- `cassation` → محكمة النقض / Court of Cassation — court of law only (apex of the ordinary judiciary)

**Specialized / separate courts** (seed as their own court_type entries):
- `economic` → المحكمة الاقتصادية / Economic Court — commercial, financial, IP, investment disputes
- `family` → محكمة الأسرة / Family Court — personal-status matters
- `administrative` → القضاء الإداري (مجلس الدولة) / Administrative Judiciary (Council of State) — disputes against the state; note this is a **separate judicial branch** with its own degrees (محكمة القضاء الإداري، المحكمة الإدارية العليا) — model as its own court_type set, do not force it into the ordinary-court degrees
- `criminal_misdemeanor` → محكمة الجنح / Misdemeanours Court
- `criminal_felony` → محكمة الجنايات / Felonies Court
- `constitutional` → المحكمة الدستورية العليا / Supreme Constitutional Court (reference only; rarely a firm's daily workload)

`courts` rows carry: court_type_id, name_ar/en, governorate (محافظة), parent_id (e.g. a specific first-instance court under an appeal court's jurisdiction). Seed the major appeal-court seats (القاهرة، الإسكندرية، طنطا، المنصورة، أسيوط، الإسماعيلية، بني سويف، قنا) as a starting set; let firms add specific courthouses.

### 4A.2 Case types (أنواع القضايا) — seed `case_types`
- `civil` → مدني / Civil
- `commercial` → تجاري / Commercial
- `economic` → اقتصادي / Economic
- `criminal_misdemeanor` → جنح / Misdemeanour
- `criminal_felony` → جنايات / Felony
- `labor` → عمالي / Labor
- `personal_status` → أحوال شخصية / Personal Status
- `family` → أسرة / Family
- `administrative` → إداري / Administrative
- `rent` → إيجارات / Rent (still common in Egyptian practice)
- `enforcement` → تنفيذ / Enforcement (execution of judgments)
- `summary_urgent` → أمور مستعجلة / Summary urgent matters

A case stores `case_type_id`, the `court_id`, `circuit` (الدائرة) as free text/number, and `roll_no` (رقم الرول) for the session roll.

### 4A.3 Case degree & linked طعون (appeals/challenges)
- `cases.degree` enum: `partial | first_instance | appeal | cassation`.
- `cases.parent_case_id` self-reference links a case to the judgment it challenges, so a matter's full journey (ابتدائي → استئناف → نقض) is one connected chain with each stage keeping its own court, number, and roll.
- Track `appeal_type` where relevant: `استئناف` (appeal), `نقض` (cassation), `تماس / إعادة نظر` (petition for reconsideration), `معارضة` (opposition to an in-absentia judgment).

### 4A.4 Hearings (الجلسات) + Requests (الطلبات)
`hearings`: case_id, session_date, court_id (may differ if circuit moved), purpose, attended_by, outcome, postponement_reason, next_date.

`case_requests` (الطلبات) — **first-class structured model**, belongs to hearing (and case). Each request has a `request_type_id`, the requesting party, optional notes, and a status (`pending | granted | rejected | deferred`). Seed `request_types`:
- `postponement` → طلب تأجيل / Postponement
- `submit_memo` → تقديم مذكرة / Submit memorandum (مذكرة)
- `join_docs` → ضم مستندات / Join documents to file
- `expert_request` → ندب خبير / Appoint an expert
- `witness_hearing` → سماع شهود / Hear witnesses
- `interim_relief` → طلب مستعجل / Interim/urgent relief
- `reserve_for_judgment` → حجز للحكم / Reserve for judgment
- `reopen_pleadings` → إعادة المرافعة / Reopen pleadings
- `dismissal` → طلب رفض / Request dismissal
- `acceptance` → طلب قبول / Request acceptance of claim

### 4A.5 Judgments (الأحكام) + deadline automation
`judgments`: case_id, judgment_date, `judgment_type`, `presence_type`, summary, and the resulting `appeal_deadline` (auto-calculated).
- `judgment_type` seed: `تمهيدي` (interlocutory/preparatory), `قطعي/نهائي` (final), `حضوري` (in-presence), `غيابي` (in-absentia), `بات` (incontestable/res judicata).
- `presence_type`: `حضوري | غيابي`.
- **Deadline engine:** on entering a judgment, auto-create a `deadline` for the challenge window (e.g. appeal/cassation period) computed from judgment_date. **The exact day-counts differ by case type and are set in config, verified by a lawyer — never hardcoded.** Fire reminders at configurable offsets; surface a countdown on the case + dashboard. These windows are short and fixed in law — missing one is a malpractice event, so this automation is a core safety feature.

### 4A.6 Dates
Store all dates in Gregorian (ISO) in the DB. Display in Gregorian by default with an **optional Hijri display** (التقويم الهجري) toggle, since some filings/notices reference Hijri. Use a tested Hijri conversion library; never roll your own.

### 4A.7 Seeders & tests
- Ship `CourtTypeSeeder`, `CourtSeeder`, `CaseTypeSeeder`, `RequestTypeSeeder`, `JudgmentTypeSeeder` with the Arabic data above (these are **central/shared reference data**, readable by all tenants but editable only by super-admin; tenants may add their own custom courts scoped to their tenant_id).
- Feature tests: creating a case with degree + linked طعن chain; adding a hearing with multiple الطلبات; entering a judgment auto-creates the correctly-dated deadline; the appeal-chain query returns all linked stages.

---

## 5. Document generation engine

The generator is hybrid — **deterministic clauses + AI-drafted narrative + lawyer approval**.

1. Lawyer picks a template → variable definitions render a dynamic form (Livewire).
2. Conditional clause rules evaluate against the answers (each clause has a condition expression; build a small, safe evaluator — do NOT `eval()`).
3. Assemble: vetted clause-library blocks dropped in **verbatim** + AI-drafted narrative sections (recitals, specific obligations) shaped by RAG (§6) + merged form data.
4. Produce a DOCX via PHPWord; queue LibreOffice → PDF.
5. Store as a `document_version` with full source (template + clauses + data). Log to audit trail.
6. Status flow: `draft → (partner) approved → locked`.

**Hard rule:** legally-binding boilerplate (arbitration, governing law, penalties, statutory references) comes ONLY from the vetted clause library, never from the model. The model must be explicitly forbidden from inventing article numbers, statutory citations (قانون العمل، قانون الشركات، etc.), or case law.

**POA rule:** powers of attorney must generate clean and final — no handwriting placeholders, no corrections — to avoid Notary Public (الشهر العقاري/التوثيق) rejection.

---

## 6. RAG pipeline (the AI feature) — Arabic-primary

Two separate flows. Build them as distinct services.

### 6.1 Ingestion (indexing a firm's contract archive)
Runs as queued jobs when contracts are uploaded/added to the repository.
1. **Extract text** from DOCX/PDF. For scans, run Tesseract `ara`. **Validate extraction quality before indexing** — bad OCR poisons retrieval. This is the #1 quality risk.
2. **Normalize Arabic** (critical, more impactful than in English). Apply consistently on BOTH ingestion and query:
   - Normalize alef: `أ إ آ ٱ → ا`
   - Normalize ya / alef-maqsura: `ى → ي`
   - Normalize ta-marbuta handling consistently: `ة` vs `ه` (decide and document)
   - Strip tashkeel (diacritics): ـَ ـِ ـُ ـّ ـْ etc.
   - Remove tatweel `ـ` (kashida)
   - Collapse whitespace
   - Put this in a single `ArabicNormalizer` service used everywhere.
3. **Chunk by legal structure, not fixed length.** Split on natural units: preamble/recitals (الديباجة), each article (بند/مادة), signature block. Each chunk stays a coherent legal unit. Attach metadata (contract_type, article_topic, date, client/matter, language).
4. **Embed** each chunk (see model note below). Store vector + normalized text + metadata in `contract_embeddings`, scoped to tenant.

### 6.2 Retrieval + generation (drafting a new contract)
1. Build a query from the structured form data (contract type + key terms).
2. Normalize + embed the query identically.
3. **Retrieve top-k** similar chunks **from this tenant only** (pgvector cosine distance, `WHERE tenant_id = ?`). Typically k = 5–10 clauses from the closest past contracts.
4. **Assemble prompt** (see §6.3).
5. Call Claude API. Store result as `ai_generations` (status `draft`) + create a `document_version`.
6. Lawyer reviews → edits → approves → locks.

### 6.3 Prompt assembly (combine all three sources)
- **System prompt:** role = Egyptian legal drafting assistant; draft in formal Arabic matching the style of the provided reference clauses; use retrieved clauses as the source of phrasing/structure; insert binding boilerplate from the clause library verbatim; **never fabricate article numbers, statutory citations, or case law** — if unsure, insert a clearly-marked placeholder for the lawyer; output clean final text suitable for notarization where relevant.
- **Reference block:** the retrieved firm clauses (so it writes like *this* firm).
- **Clause-library block:** the deterministic approved clauses to include verbatim.
- **Data block:** the structured form answers.
- Return draft → mark "AI draft — pending review."

### 6.4 Embedding model decision **[NEEDS TESTING — do not hardcode]**
Do NOT default to a model from a benchmark. Take 20–30 of a real firm's Arabic contract clauses and measure retrieval quality directly. Candidates:
- API: Cohere `embed-multilingual-v3`, Voyage multilingual, OpenAI `text-embedding-3-large`.
- Self-hosted (keeps data in-country, heavier ops): `BGE-M3`, `multilingual-e5-large`.
Make the embedding provider a **swappable driver** behind an interface so we can change it after testing. Store the vector dimension in config; the `contract_embeddings.embedding` column dimension must match the chosen model.

### 6.5 AI confidentiality **[LOCKED]**
- API key server-side only. Never expose to frontend.
- Use Anthropic's zero-retention / no-training option; document it in the firm DPA.
- Offer a future "self-hosted model" premium tier for firms that cannot send data to a US API.
- Every AI call is logged (which tenant, which matter, which chunks retrieved) for audit.

---

## 7. Security & compliance checklist (apply to every feature)
- Authorization via Policies on every model; default deny.
- Tenant global scope on every tenant model; isolation test per feature.
- Audit-log every view/edit of cases, companies, documents, proxies.
- No secrets in code or frontend; `.env` only; rotate keys.
- Validate and virus-scan uploads; downloads require explicit user action.
- PII (national ID, commercial register) access is role-gated and logged.
- Never auto-finalize legal documents or auto-send anything to clients/courts.

---

## 8. Build order (phased) — start at Phase 1, Milestone 1

**Phase 1 — Foundation**
- Tenancy (stancl/tenancy), auth, roles/policies, `BelongsToTenant` trait + global scope, tenant resolution.
- Egyptian litigation reference data (§4A): seed courts, court types, case types, request types, judgment types (Arabic canonical).
- Clients, matters, cases (degree + linked طعون chain), hearings + structured الطلبات, judgments + auto-deadline engine, calendar, reminder jobs.
- Arabic RTL layout shell, localization scaffolding, base Tailwind RTL theme.

**Phase 2 — Documents & AI core**
- Document repository (S3, versioning, Meilisearch Arabic search), OCR job.
- `ArabicNormalizer`, chunking, embedding driver interface, pgvector setup, ingestion jobs.
- Clause library + templates + generator (DOCX + PDF queue).
- RAG retrieval + generation + review/approve workflow. Proxies module.

**Phase 3 — Corporate**
- Companies, formation pipeline, shareholders, compliance items, serials, IP assets.

**Phase 4 — Business**
- Billing/Cashier, time tracking, invoicing, CRM, dashboards, client portal.

**Phase 5 — Intelligence**
- Advanced reporting, clause-risk flagging, deviation checks, optional self-hosted model tier.

---

## 9. START HERE — First milestone (do this first)

**Milestone 1.1 — Project skeleton + tenancy + Arabic shell.**

Deliver, in order, as small reviewable commits:

1. Fresh Laravel 12 app; install: stancl/tenancy, Livewire 3, Tailwind (RTL config), Pest, Pint, Horizon, Scout. Configure PostgreSQL + pgvector (migration enabling the extension).
2. Tenancy: central migrations (`tenants`, `users` with role enum), subdomain resolution, a seeded demo tenant "Samir Group Legal" with one partner user. Login flow.
3. `BelongsToTenant` trait + global scope. A second demo tenant + a **Pest test proving tenant isolation** (Firm A cannot read Firm B's clients).
4. RTL app shell in Blade+Livewire: sidebar nav (Arabic labels), top bar, dashboard placeholder. Tailwind configured `dir="rtl"`, Arabic web font, localization files `lang/ar` + `lang/en` with a language switcher.
5. `clients` + `cases` migrations/models/policies, a Livewire index + create form for each, validation via Form Requests, feature tests.

**Acceptance for Milestone 1.1:** I can log in as the seeded partner, switch language, see an RTL dashboard, create a client and a case scoped to my firm, and the isolation test passes. Pint clean, Pest green.

**Milestone 1.2 — Egyptian litigation core (§4A).**
1. Seed reference data: court types, courts (major appeal seats + a few first-instance/partial under them), case types, request types, judgment types — all with `name_ar` canonical + `name_en`, per the lists in §4A. Mark these central/shared, super-admin editable, tenant-extendable.
2. `cases` with `degree`, `court_id`, `case_type_id`, `circuit`, `roll_no`, `parent_case_id`; a Livewire screen showing a case and its linked طعن chain (ابتدائي → استئناف → نقض).
3. `hearings` with `case_requests` (الطلبات) as a structured child — add/edit multiple requests per session with request_type + status.
4. `judgments` with judgment_type/presence_type; on save, auto-create a `deadline` for the challenge window from config values (placeholder day-counts flagged `// VERIFY WITH LAWYER`), with a reminder job and a dashboard countdown.
5. Calendar view (firm-wide + per-lawyer + today's sessions), Arabic UI.

**Acceptance for Milestone 1.2:** I can create an ابتدائي case, record a جلسة with طلبات, enter a حكم that auto-creates a correctly-dated طعن deadline, then create the linked استئناف case from it and see the full chain. All Arabic-labelled, RTL, tenant-isolated, tested.

When you finish a milestone, summarize what changed, list new files, show how to run it, and propose the next milestone's task list before continuing.

---

## 10. How to work with me (Claude Code operating notes)
- Work in small, reviewable steps. Don't scaffold the whole app at once.
- Before each milestone, restate your plan as a short checklist and wait if anything is ambiguous.
- Prefer Laravel-native solutions over adding packages; justify any new dependency.
- Write the tenant-isolation test for every new tenant-scoped feature — treat it as part of "done."
- Never put real secrets in code. Use `.env.example` with placeholders.
- Keep this CLAUDE.md updated: when an architectural decision is made (e.g. the embedding model after testing), record it here.
- Flag anything that touches confidentiality, AI auto-finalization, or tenant isolation for explicit review.
