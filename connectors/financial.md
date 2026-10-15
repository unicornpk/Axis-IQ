# IQ Connector — Financial

Base URL (dev): `https://unicornaxis.com/financial/app/api/iq`
Base URL (prod): `https://financial.axisone.si/app/api/iq`

Auth: same middleware as the rest of Financial. Every endpoint is scoped to the authenticated user's tenant/company automatically.

## Tools

### `financial.invoice_summary`
- **Endpoint:** `GET /invoice-summary?status={paid|unpaid|overdue|all}&period={this_month|last_month|this_year}`
- **Description:** Count and total amount of invoices matching a status/period.
- **Parameters:** `status` (default `all`), `period` (default `this_month`)
- **Returns:**
```json
{ "status": "unpaid", "period": "this_month", "count": 12, "total_amount": 184500.00, "currency": "PKR" }
```

### `financial.budget_vs_actual`
- **Endpoint:** `GET /budget-vs-actual?project_id={optional}&period={this_month|this_quarter|this_year}`
- **Description:** Budgeted vs actual spend for a project/period.
- **Parameters:** `project_id` (optional), `period` (default `this_month`)
- **Returns:**
```json
{ "period": "this_month", "budgeted": 500000.00, "actual": 462300.00, "variance": -37700.00, "currency": "PKR" }
```

### `financial.cash_position`
- **Endpoint:** `GET /cash-position`
- **Description:** Current cash balance across the tenant's connected accounts.
- **Parameters:** none
- **Returns:**
```json
{ "as_of": "2026-10-15T09:00:00Z", "total_cash": 2150000.00, "currency": "PKR" }
```

### `financial.overdue_receivables`
- **Endpoint:** `GET /overdue-receivables`
- **Description:** Count and total of invoices overdue for payment from clients.
- **Parameters:** none
- **Returns:**
```json
{ "count": 5, "total_amount": 97200.00, "currency": "PKR" }
```
