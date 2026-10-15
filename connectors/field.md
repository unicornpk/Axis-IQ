# IQ Connector — Field

Underlying product: **AxisField**, `nosaas` edition (single-company). AxisField also has a `saas-next` (multi-tenant) edition in development — Axis IQ talks to `nosaas` only, since that's what serves the live URL below. Revisit this doc if/when the SaaS edition goes live and Axis IQ needs to support it too.

**Status: implemented.** Built on branch `axis-iq-connector` (off `nosaas`) in the AxisField codebase, not yet merged/pushed. Docs on that branch: `docs/AXIS-IQ.md`.

Base URL (dev): `https://field.unicornaxis.com/app/api/iq`
Base URL (prod): `https://field.axisone.si/app/api/iq`

## Auth (differs from the generic pattern in README/AGENT.md)

AxisField's connector does **not** use the end user's own session/token — `nosaas` is single-company with no per-user tenant scoping, so there's nothing to scope a forwarded token to. Instead it uses one **shared service token**, the same pattern as AxisField's existing billing API:

- A custom middleware `iq.token` checks `Authorization: Bearer <token>` against a setting called `iq_api_token`.
- The token is created/rotated in **AxisField → Settings → "Axis IQ API token"**. Existing installs need to run Settings → Update database first to get that setting.
- Axis IQ Core must hold this token server-side (`IQ_FIELD_API_TOKEN` in Axis IQ Core's `.env` — see `core/config/axis_iq.php`) and send it itself. It is **not** the logged-in product user's token, and the widget never sees or forwards it.
- Responses: `401` bad/missing token, `422` bad params, `405` anything other than `GET`.
- Rate limit: 120 requests/min.

**Consequence worth flagging to the product owner:** because the token is shared rather than per-user, Axis IQ answers the same for every AxisField user who can open the chat widget — there's no way for this connector to restrict, say, a technician from asking about headcount across the whole company the way the office might. If that's not acceptable, it needs an AxisField-side permission check (e.g. only expose the widget to office/admin roles in the frontend embed, or add real per-user auth to this endpoint later) — Axis IQ Core has no visibility into AxisField's roles to enforce this itself.

## Data model (as implemented, differs slightly from the original draft contract)

- **Job** = `work_orders` table (`scheduled_start`, `status`).
  - `open` = `unassigned, scheduled, en_route, on_site, in_progress, on_hold`
  - `completed` = `completed (awaiting review), reviewed, signed_off, invoiced`
  - Cancelled jobs are excluded entirely.
- **Technician** = `technicians` table (`status: active|inactive`, `approval: approved|pending|rejected`, `work_days`, time off in `technician_time_off`).
- **Clock-in** = `timesheets` table (`type: work|travel|break`, `started_at`, `ended_at` null while running).
- All timestamps are ISO 8601 in the company's configured time zone.

## Tools

### `field.technicians_working_today`
- **Endpoint:** `GET /api/iq/technicians-working-today`
- **Description:** Count and status breakdown of technicians working today.
- **Parameters:** none
- **Buckets:**
  - `on_site` = job in `on_site`/`in_progress`, OR a running `work` timesheet
  - `en_route` = job in `en_route`, OR a running `travel` timesheet
  - `off_today` = not a scheduled work day, or on approved time off
  - `available` = scheduled work day, nothing currently running (not in the original draft contract — added by the implementation)
  - `count` = `on_site + en_route` only (does **not** include `off_today` or `available`)
- **Returns:**
```json
{ "count": 14, "as_of": "2026-10-15T09:02:00+05:00", "breakdown": { "on_site": 9, "en_route": 5, "off_today": 3, "available": 2 } }
```

### `field.technicians`
- **Endpoint:** `GET /api/iq/technicians?status={on_site|en_route|off_today|available}&date={YYYY-MM-DD|today}`
- **Description:** List of technicians matching a status/date filter. For a **past** date, buckets are derived from that day's recorded timesheets only (not live status).
- **Parameters:** `status` (optional), `date` (optional, default `today`)
- **Returns:**
```json
{ "data": [ { "name": "A. Khan", "site": "Al-Noor Constructions · Site 4", "status": "on_site", "clocked_in_at": "2026-10-15T07:55:00+05:00" } ] }
```
`site` is a formatted `"Client · Site"` string, not separate fields. `clocked_in_at` is the start time of the relevant running/recorded timesheet, if any.

### `field.jobs_today`
- **Endpoint:** `GET /api/iq/jobs-today?status={open|completed|all}`
- **Description:** Jobs scheduled for today. Note: `count` reflects the `status` filter, but `completed` and `open` in the response are **always totals**, regardless of the filter.
- **Parameters:** `status` (optional, default `all`)
- **Returns:**
```json
{ "count": 22, "completed": 15, "open": 7 }
```

### `field.job_completion_rate`
- **Endpoint:** `GET /api/iq/job-completion-rate?period={today|this_week|this_month}`
- **Description:** Percentage of scheduled jobs completed in the given period. Weeks start Monday.
- **Parameters:** `period` (default `today`)
- **Returns:**
```json
{ "period": "today", "scheduled": 22, "completed": 15, "rate": 0.68 }
```
