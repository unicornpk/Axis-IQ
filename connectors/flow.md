# IQ Connector — Flow

Base URL (dev): `https://unicornaxis.com/flow/app/api/iq`
Base URL (prod): `https://flow.axisone.si/app/api/iq`

Auth: same middleware as the rest of Flow. Every endpoint is scoped to the authenticated user's tenant/workflow automatically.

## Tools

### `flow.task_count`
- **Endpoint:** `GET /task-count?status={pending|in_progress|completed|overdue|all}`
- **Description:** Count of workflow tasks matching a status.
- **Parameters:** `status` (default `pending`)
- **Returns:**
```json
{ "status": "pending", "count": 17 }
```

### `flow.tasks`
- **Endpoint:** `GET /tasks?status={pending|in_progress|completed|overdue|all}&assigned_to={optional}`
- **Description:** List of tasks matching a filter.
- **Parameters:** `status` (optional), `assigned_to` (optional — defaults to all users the requester can see)
- **Returns:**
```json
{ "data": [ { "id": 101, "title": "Approve change order #12", "status": "pending", "assigned_to": "S. Ahmed", "due_date": "2026-10-16" } ] }
```

### `flow.approval_queue`
- **Endpoint:** `GET /approval-queue`
- **Description:** Count and list of items currently awaiting the requesting user's approval.
- **Parameters:** none
- **Returns:**
```json
{ "count": 3, "data": [ { "id": 55, "type": "change_order", "title": "CO #12 - extra excavation", "submitted_by": "A. Bilal" } ] }
```

### `flow.average_cycle_time`
- **Endpoint:** `GET /average-cycle-time?workflow={optional}&period={this_month|this_quarter}`
- **Description:** Average time tasks/approvals take to complete in a workflow.
- **Parameters:** `workflow` (optional), `period` (default `this_month`)
- **Returns:**
```json
{ "period": "this_month", "average_hours": 18.5 }
```
