# Axis IQ

Axis IQ is the AI query & reporting assistant for the **AxisOne Suite** (axisone.si). It lets any user of any AxisOne product ask a plain-language question and get an answer sourced from that product's own data — without leaving the product they're in.

> "How many technicians are working today?" asked inside **Field** → answered from Field's data.
> "How many RFIs are open?" asked inside **View** → answered from View's data.

## 1. Products in scope

| Product | Current (dev) URL | Production URL (axisone.si) |
|---|---|---|
| Financial | unicornaxis.com/financial/app | financial.axisone.si/app |
| Flow | unicornaxis.com/flow/app | flow.axisone.si/app |
| View | unicornaxis.com/view/app | view.axisone.si/app |
| Estimate | unicornaxis.com/estimate/ | estimate.axisone.si/app |
| Field | field.unicornaxis.com/app | field.axisone.si/app |
| — suite landing — | — | axisone.si (root) |
| Axis IQ | — | iq.axisone.si (assumption — confirm) |

**Domain plan (confirmed):** all products are currently on `unicornaxis.com` during development. On completion, everything moves to `axisone.si`, one subdomain per product, each subdomain's **root folder serving the marketing/landing page** and the **actual app one folder down** (`/app`) — mirroring the current `unicornaxis.com/<product>/app` pattern, just promoted to its own subdomain. The bare `axisone.si` root is the overall suite landing page.

This means every connector base URL and the widget's embed snippet need to be environment-aware (dev: `unicornaxis.com/<product>/app`, prod: `<product>.axisone.si/app`) — see §6 config.

## 2. What v1 does (and doesn't) do

- ✅ Answers natural-language questions with data pulled live from the product the user is currently in.
- ✅ Scoped to the logged-in user's own tenant/permissions — never sees data the user couldn't already see in the app.
- ✅ Read-only: counts, lists, sums, status breakdowns, simple trends.
- ❌ No write actions in v1 (no closing RFIs, no editing records). That's a deliberate v2 scope decision — see Roadmap.
- ❌ No cross-product correlation in v1 (e.g. "compare Field labor cost to Financial budget") — each query is answered against one product's connector. Cross-product reasoning is a v2+ idea once each connector is solid.

## 3. Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  Product app (e.g. field.unicornaxis.com/app)                   │
│                                                                   │
│   ┌───────────────┐        ┌─────────────────────────────┐      │
│   │ Axis IQ widget │  HTTPS │  /api/iq/*  (IQ Connector)   │      │
│   │ (JS, embedded) │◄──────►│  - read-only, scoped queries │      │
│   └──────┬────────┘        │  - reuses app's own auth/     │      │
│          │                 │    policies/Eloquent models   │      │
│          │ user query +    └───────────────▲───────────────┘      │
│          │ forwarded session/token                   │            │
└──────────┼─────────────────────────────────────────────┼──────────┘
           │                                             │
           ▼                                             │
┌─────────────────────────────────────────┐              │
│  Axis IQ Core (iq.axisone.si)            │              │
│  - Laravel service                       │              │
│  - Claude API: NL understanding +        │──calls IQ────┘
│    tool-calling orchestration            │  Connector API
│  - Per-product "tool" registry           │  of whichever product
│  - Conversation log (audit)              │  the query came from
└───────────────────────────────────────────┘
```

**Flow of one query:**

1. User types a question into the Axis IQ widget embedded in a product (e.g. Field).
2. Widget sends `{ query, product: "field", auth_token }` to Axis IQ Core over HTTPS.
3. Axis IQ Core calls Claude with the query and the set of "tools" (whitelisted connector endpoints) available for that product.
4. Claude decides which tool(s) to call (e.g. `field.technicians_working_today`), Axis IQ Core calls that product's `/api/iq/...` endpoint, **forwarding the user's own auth token** so the product enforces its normal authorization.
5. The product's Connector API runs a scoped, read-only query against its own database using its existing Eloquent models/policies and returns structured JSON (not raw SQL results).
6. Claude turns the structured data into a natural-language answer (and optionally a small table/chart payload).
7. Axis IQ Core logs the Q&A pair (for audit/analytics) and returns the answer to the widget.

### Why this shape

- **Per-product REST connector, not shared DB access** — each product keeps full control of its own authorization and business rules. Axis IQ never bypasses the app to touch a database directly, which matters a lot once different products have different tenancy/permission models.
- **Laravel + Claude, no new stack** — Axis IQ Core is a Laravel app like the rest of the suite. The only new piece is calling the Anthropic API for NLU and tool-calling; everything else (routing, auth forwarding, logging) is ordinary Laravel.
- **Embedded widget, not a separate destination** — users ask questions in the product they're already working in, with full context of what "today" or "this project" means there.

## 4. Repo structure (proposed)

```
axis-iq/
├── AGENT.md                  # behavior spec / system prompt for the Axis IQ agent
├── README.md                 # this file
├── core/                     # Axis IQ Core — Laravel service (new repo or monorepo package)
│   ├── app/Services/Claude/  # Anthropic API client + tool-calling loop
│   ├── app/Models/IqConversation.php
│   └── routes/api.php        # POST /query, GET /conversations/{id}
├── connectors/                # one folder per product, documents its IQ Connector API contract
│   ├── financial.md
│   ├── flow.md
│   ├── view.md
│   ├── estimate.md
│   └── field.md
└── widget/                    # embeddable JS chat widget, built once, used by all 5 products
    └── axis-iq-widget.js
```

Each product repo (Financial, Flow, View, Estimate, Field) gets its own small addition:

```
app/Http/Controllers/Api/IqController.php   # implements that product's connector contract
routes/api.php                               # Route::prefix('iq')->middleware('auth:sanctum')->group(...)
```

## 5. IQ Connector API contract (per product)

Every product exposes a small, whitelisted set of read-only endpoints under `/api/iq/*`, protected by the **same auth middleware the product already uses**. Example for Field:

```
GET /api/iq/technicians-working-today
  → { "count": 14, "as_of": "2026-10-15T09:00:00Z", "breakdown": {"on_site": 9, "en_route": 5} }

GET /api/iq/technicians?status=on_site&date=today
  → { "data": [ { "name": "...", "site": "...", "clocked_in_at": "..." } ] }
```

And for View:

```
GET /api/iq/rfi-count?status=open
  → { "count": 23, "status": "open" }
```

Rules for every connector:

- Read-only (`GET` only in v1).
- Scoped to the authenticated user's tenant/project/role exactly as the main app would scope it.
- Returns structured JSON, never raw SQL or internal IDs the user shouldn't see.
- Documented in `connectors/<product>.md` as a list of "tools" (name, description, parameters, example response) — this doubles as the Claude tool-calling schema.

## 6. Setup (Axis IQ Core)

> Scaffold only — fill in once the Laravel project is created.

```bash
composer create-project laravel/laravel core
cd core
composer require anthropic/sdk   # or raw HTTP client to the Anthropic API
cp .env.example .env
```

`.env` additions:

```
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-sonnet-5-5
AXIS_IQ_PRODUCTS=financial,flow,view,estimate,field
```

Each product's base URL + connector tool list is registered in `config/axis_iq.php`, one entry per product, so adding a 6th product later is config, not code. Base URLs are environment-driven so the same code works in dev (`unicornaxis.com/<product>/app`) and prod (`<product>.axisone.si/app`):

```php
// config/axis_iq.php
return [
    'products' => [
        'financial' => ['base_url' => env('IQ_FINANCIAL_URL', 'https://unicornaxis.com/financial/app')],
        'flow'      => ['base_url' => env('IQ_FLOW_URL',      'https://unicornaxis.com/flow/app')],
        'view'      => ['base_url' => env('IQ_VIEW_URL',      'https://unicornaxis.com/view/app')],
        'estimate'  => ['base_url' => env('IQ_ESTIMATE_URL',  'https://unicornaxis.com/estimate')],
        'field'     => ['base_url' => env('IQ_FIELD_URL',     'https://field.unicornaxis.com/app')],
    ],
];
```

In production, `.env` simply overrides each `IQ_<PRODUCT>_URL` to its `*.axisone.si/app` address — no code changes, no redeploy of connector logic.

## 7. Build status

| Product | Connector | Notes |
|---|---|---|
| Field (AxisField, `nosaas`) | ✅ implemented, ✅ verified live end-to-end | Branch `axis-iq-connector` off `nosaas` in the AxisField repo, **not pushed yet**. Shared service token, not per-user auth — see `connectors/field.md`. Real queries ("technicians working today", "job completion rate this week") answered correctly through the full pipeline: widget-shaped request → Axis IQ Core → Claude tool-calling → AxisField's live API → natural-language answer. |
| View | 📝 contract drafted from live traffic, not implemented | RFI read shape reverse-engineered from the real app's network calls (no codebase access yet) — see `connectors/view.md`. Same shared-token auth gap as Field, plus an unresolved multi-project (`pid`) selector Field never needed. Needs a developer with actual View codebase access to build it. |
| Financial | not started | |
| Flow | not started | |
| Estimate | not started | |

`core/` (Axis IQ Core) is scaffolded and runs: Laravel app, `POST /api/query` route, Claude tool-calling orchestrator, per-product config/auth, conversation audit log. **Verified live end-to-end against Field** — real `ANTHROPIC_API_KEY` and `IQ_FIELD_API_TOKEN` configured, real questions answered correctly, conversation log recording tool calls and answers.

## 8. Roadmap

- **v1 (this doc's scope):** read-only Q&A and reporting, one product at a time, embedded widget.
- **v2:** confirmed write actions (e.g. "close RFI #45" → shows a confirmation card, user approves, then the connector performs the write).
- **v3:** cross-product questions (e.g. "how does Field labor cost compare to the Financial budget this month"), full agentic multi-step actions.

## 9. Open questions to confirm with the team

1. Hosting/subdomain for Axis IQ Core itself (`iq.axisone.si` assumed — not yet confirmed).
2. Auth mechanism each remaining product uses — **do not assume "forward the user's token" by default.** Field turned out to use a single shared service token instead, because its auth model had no per-user scoping to forward. Confirm each product's real model before building its connector; `config/axis_iq.php` has a placeholder `'auth' => ['type' => 'forward_user_token']` for the other four that must be checked, not trusted.
3. Field-specific: since its connector uses one shared token, any AxisField user who can open the Axis IQ widget gets the same answers — there's no per-role restriction (e.g. a technician could ask company-wide headcount questions same as an office admin). Decide whether that's acceptable, or whether the widget should only be embedded for certain roles.
4. Whether all 5 products share one Laravel codebase/DB or are fully separate apps — affects how much of the connector code can be shared.
5. Rate limiting / cost controls on the Claude API calls (per-user, per-tenant quotas). Field's connector itself caps at 120 req/min.
6. Cutover plan: does Axis IQ get built/tested against the current `unicornaxis.com` dev URLs first and then just re-pointed via `.env` on the day each product moves to `axisone.si`, or does it wait for the move to be complete?
7. AxisField's `nosaas`/`saas-next` branches aren't pushed to GitHub yet — needs a durable remote home before this goes further.
