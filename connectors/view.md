# IQ Connector — View

Base URL (dev): `https://unicornaxis.com/view/app/api/iq`
Base URL (prod): `https://view.axisone.si/app/api/iq`

Auth: same middleware as the rest of View (forward the user's existing token/session as-is). Every endpoint is scoped to the authenticated user's tenant/project automatically.

## Tools

### `view.rfi_count`
- **Endpoint:** `GET /rfi-count?status={open|closed|overdue|all}`
- **Description:** Count of RFIs (Request for Information) matching a status, for the current tenant/project.
- **Parameters:** `status` (optional, default `open`)
- **Returns:**
```json
{ "status": "open", "count": 23, "as_of": "2026-10-15T09:00:00Z" }
```

### `view.rfis`
- **Endpoint:** `GET /rfis?status={open|closed|overdue|all}&project_id={optional}`
- **Description:** List of RFIs matching a filter.
- **Parameters:** `status` (optional), `project_id` (optional — only if the user has multiple active projects)
- **Returns:**
```json
{ "data": [ { "id": 45, "subject": "Beam spec clarification", "status": "open", "days_open": 4, "assigned_to": "M. Raza" } ] }
```

### `view.submittal_count`
- **Endpoint:** `GET /submittal-count?status={pending|approved|rejected|all}`
- **Description:** Count of submittals by status.
- **Parameters:** `status` (optional, default `pending`)
- **Returns:**
```json
{ "status": "pending", "count": 8 }
```

### `view.overdue_items`
- **Endpoint:** `GET /overdue-items`
- **Description:** Count and breakdown of all overdue items (RFIs, submittals, punch list) across the project.
- **Parameters:** none
- **Returns:**
```json
{ "total": 11, "breakdown": { "rfi": 6, "submittal": 2, "punch_list": 3 } }
```
