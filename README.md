# LEXA

Multi-tenant, Arabic-first SaaS for Egyptian law firms. Litigation
management, corporate / formation work, government paperwork, document
automation, and a RAG-powered Arabic contract drafter that learns from
each firm's own archive.

> **Read [`CLAUDE.md`](CLAUDE.md) first.** It is the source of truth for
> scope, conventions, and constraints. See [`docs/DECISIONS.md`](docs/DECISIONS.md)
> for every architectural choice and the live gap log.

## Status

Phases 1–4 have foundations in place. **47 Pest tests passing, 109
assertions.** Pint clean.

- ✅ M1.1 — Tenancy, Arabic RTL shell, auth, Clients & Cases CRUD
- ✅ M1.2 — Egyptian litigation core (courts / case types / hearings +
  الطلبات / judgments + auto-deadline engine + chain viewer + calendar)
- ✅ Phase 2 — Documents schema, ArabicNormalizer + LegalChunker, RAG
  retrieval service with hard SQL tenant filter, embedding-driver
  interface, Anthropic client wrapper, document generator (DOCX),
  review workflow, proxies module
- ✅ Phase 3 — Corporate (companies, formation steps, shareholders,
  compliance, serials, IP assets)
- ✅ Phase 4 — Time tracking, invoicing (piastres, 14% VAT), payments,
  dashboard with stat tiles
- ⏸ Live AI / OCR / PDF / billing / lawyer-verified deadlines — each
  blocked by a specific credential or domain decision documented in
  [`docs/DECISIONS.md`](docs/DECISIONS.md)

## Quick start

See [`docs/SETUP.md`](docs/SETUP.md) for the full walkthrough.

```powershell
docker compose up -d        # postgres+pgvector, redis, meilisearch
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Add to `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1  lexa.test samir.lexa.test demo.lexa.test
```

Login at `http://samir.lexa.test:8000/login` as
`partner@samir.test` / `lexa-dev`.

## Testing

```powershell
./vendor/bin/pest --no-coverage
./vendor/bin/pint
```

## License

Proprietary — SamirGroup / SSS.
