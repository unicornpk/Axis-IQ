# IQ Connector — Estimate

Base URL (dev): `https://unicornaxis.com/estimate/api/iq`
Base URL (prod): `https://estimate.axisone.si/app/api/iq`

Auth: same middleware as the rest of Estimate. Every endpoint is scoped to the authenticated user's tenant automatically.

## Tools

### `estimate_open_estimates_count`
- **Endpoint:** `GET /open-estimates-count?status={draft|submitted|won|lost|all}`
- **Description:** Count of estimates matching a status.
- **Parameters:** `status` (default `draft`)
- **Returns:**
```json
{ "status": "draft", "count": 9 }
```

### `estimate_estimates`
- **Endpoint:** `GET /estimates?status={draft|submitted|won|lost|all}&client_id={optional}`
- **Description:** List of estimates matching a filter.
- **Parameters:** `status` (optional), `client_id` (optional)
- **Returns:**
```json
{ "data": [ { "id": 212, "client": "Al-Noor Constructions", "amount": 1250000.00, "status": "submitted", "submitted_on": "2026-10-10" } ] }
```

### `estimate_win_rate`
- **Endpoint:** `GET /win-rate?period={this_quarter|this_year}`
- **Description:** Percentage of submitted estimates that converted to won jobs in a period.
- **Parameters:** `period` (default `this_quarter`)
- **Returns:**
```json
{ "period": "this_quarter", "submitted": 24, "won": 9, "rate": 0.375 }
```

### `estimate_pending_approval`
- **Endpoint:** `GET /pending-approval`
- **Description:** Count and list of estimates awaiting internal approval before being sent to the client.
- **Parameters:** none
- **Returns:**
```json
{ "count": 2, "data": [ { "id": 230, "client": "Zenith Builders", "amount": 480000.00 } ] }
```
