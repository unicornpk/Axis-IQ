# IQ Connector — View

**Status: reverse-engineered from live network capture (2026-10-15), not yet implemented.** View has no Claude Code session or attached repo yet, so this contract was built by inspecting the real app's browser traffic directly, not source code. Everything below is observed fact except the sections marked "needs a developer" — those are the actual implementation gap.

View is a construction project management app — richer than originally assumed (it's not just RFIs: tabs include Overview, Schedule Gantt, Progress & Curves, Update Progress, Live View, Site Photos, Approvals, RFIs, Quality, Punch List, Daily Report, Documents, Attendance, Deliveries, Cash, Package). This doc covers RFIs only, as the first slice; the other tabs are candidate tools for later, once someone with real access builds them out.

## What exists today (observed)

Base: `https://unicornaxis.com/view-api/index.php`

A single-entry-point PHP router, not Laravel-style path routing — every call is `index.php?a=<action>&pid=<project_id>`. `pid` is required: **View is multi-project**, unlike Field (single-company, no project selector needed).

Read action for RFIs:
```
GET /view-api/index.php?a=rfis&pid=p1355cfa37502
```
Returns **all** RFIs for that project, unfiltered — the UI does status filtering client-side, not the server:
```json
{
  "ok": true,
  "items": [
    {
      "id": "rf7f7d90cd620b",
      "ref": "RFI-002",
      "title": "Color code for ceiling paint",
      "question": "Color code for ceiling gypsum flush at corridor near IT Room",
      "zone": "Zone A",
      "pkg": "PAINT WORK - Zone A",
      "drawing": "F-8",
      "priority": "normal",
      "needBy": "",
      "addressedTo": "Architect",
      "status": "open",
      "raisedBy": "Muhammad Azeem",
      "raisedAt": "2026-10-09",
      "answer": "",
      "answeredBy": "",
      "answeredAt": "",
      "files": [],
      "replyFiles": [],
      "history": [ { "at": "2026-10-09", "by": "Muhammad Azeem", "what": "raised" } ],
      "comments": []
    }
  ],
  "v": 134,
  "perm": "edit"
}
```

`status` values seen or implied by the UI: `open`, and at least one "answered but not closed" state (the RFI tab shows separate tiles for **Open**, **Awaiting close-out** (answered but not closed), **Urgent**, and **Raised in total**) — the exact string values for "answered" and "closed" weren't captured yet; confirm against real data with more than one RFI before building the connector's `status` enum.

Auth on this existing endpoint: a **PHP session cookie** (`axv_<install-id>=<session>`), set on `unicornaxis.com` with no explicit CORS/credential allowance for other origins. This is the blocker: a cross-origin `fetch()` from the Axis IQ widget (running on a different host, e.g. `iq.axisone.si`) can **never** carry this cookie — cookies are scoped to the domain being called, not the page making the call, regardless of `credentials: 'include'`. This is a harder version of the problem Field had; see below.

## What a developer needs to add

Same shape as what AxisField built for Field (see `connectors/field.md` "Auth"), plus one extra piece for the multi-project case:

1. A token-authenticated read path for the existing `a=rfis` data — either a new `/api/iq/rfis` route, or a bearer-token auth option added to the existing router for that one action. Validate `Authorization: Bearer <token>` against a stored service token, the same pattern as Field's `iq_api_token`.
2. **Project resolution.** Field never needed this (single company, one implicit scope). View does: every call needs a `pid`. Open question for the product owner, not something I can resolve by reverse-engineering: when a user asks Axis IQ a question from inside a specific View project page, does the widget pass that project's `pid` along with the query (likely — the widget already knows the page context), or does View have some other way to resolve "the user's current/default project" server-side? The connector's `project_id` parameter below assumes the widget supplies it.
3. A small response trim for IQ's purposes — the existing payload includes internal fields (`files`, `replyFiles`, `history`, `comments`) an IQ answer doesn't need; fine to pass through the full record if trimming isn't worth the effort, Claude will just ignore the extra fields per `AGENT.md`'s "only use tool data" rule.

## Proposed tools (pending implementation)

### `view_rfi_count`
- **Endpoint:** `GET /api/iq/rfis?pid={project_id}&count_only=1` (or compute client-side from `view_rfis` below — implementer's call)
- **Description:** Count of RFIs matching a status, for one project.
- **Parameters:** `status` (optional, default `open` — exact enum TBD, see above), `project_id` (required)
- **Returns:**
```json
{ "status": "open", "count": 1, "project_id": "p1355cfa37502" }
```

### `view_rfis`
- **Endpoint:** `GET /api/iq/rfis?pid={project_id}&status={open|...}`
- **Description:** List of RFIs matching a filter, trimmed to the fields an IQ answer needs.
- **Parameters:** `status` (optional), `project_id` (required)
- **Returns:**
```json
{
  "data": [
    {
      "ref": "RFI-002",
      "title": "Color code for ceiling paint",
      "status": "open",
      "zone": "Zone A",
      "priority": "normal",
      "raised_by": "Muhammad Azeem",
      "raised_at": "2026-10-09",
      "addressed_to": "Architect",
      "drawing": "F-8"
    }
  ]
}
```

## Not yet investigated

- The exact `status` string for "closed" and "answered but not closed" (only one open RFI existed in the captured project — need a project with closed/answered RFIs to confirm).
- Whether `priority: "urgent"` is a real value (the UI's "Urgent" tile suggests yes, unconfirmed from a live record).
- The other tabs (Punch List, Approvals, Quality, Daily Report, Attendance, Deliveries, Cash) — same reverse-engineering process would apply if/when View becomes a second or third connector target.
