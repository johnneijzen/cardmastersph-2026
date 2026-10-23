# Legacy System Analysis

Analysis of the retired application in `old_code/` (Laravel 8.75, Jetstream, Livewire 2,
`mediconesystems/livewire-datatables`, `maatwebsite/excel ^3.1`, `acaronlex/laravel-calendar`).

This document is the reference for checklist items **1.1** (review and document current
functionality) and **1.2** (identify tables, relationships and business rules). It is the
baseline the rewrite is checked against.

---

## 1. Modules

| Module | Legacy routes | Key classes |
| --- | --- | --- |
| Dashboard | `GET /dashboard` | `dashboard.blade.php` (static) |
| Clients | `clients.*` resource, `clients.export` | `ClientController`, `ClientsTable` |
| Contract persons | `clientContactPersons.*` resource | `ClientContactPersonController`, `ClientscontactpersonsTable` |
| Client agents | `clientAgents.*` resource | `ClientAgentController`, `ClientAgentTable` |
| Employees | `employees.*` resource | `EmployeeController`, `EmployeesTable` |
| Inventory | `inventory.*` resource, `inventory.import`, `inventory.fileimport` | `InventoryController`, `InventoryTable`, `InventoryImport` |
| Categories / subcategories | `categories.*`, `subcategories.*` + soft-delete routes | `CategoryController`, `SubcategoryController` |
| Classes / subclasses | `productClasses.*`, `productSubclasses.*` + import + soft-delete routes | `ProductClassController`, `ProductSubclassController` |
| Withdrawal slips | `withdrawalSlip.*` resource | `WithdrawalSlipController`, `WithdrawalSlipTable`, `InventoryWithdrawalForm` |
| Job orders | `jobOrders.*` resource, `jobOrders.export`, `jobOrders.exportfile`, `jobOrders.import`, `jobOrders.fileimport`, `jobOrders.routingSlip`, soft-delete routes | `JobOrderController`, `JobOrdersTable`, `jobOrdersImport`, `JobOrdersExport` |
| Job order agents | `jobOrderAgents.*` resource | `JobOrderAgentController`, `JobOrderAgentTable` |
| Routing slips | `routingSlip.multiIndex`, `routingSlip.index` | `RoutingSlipController` |
| Cash receipts | `cashReceipts.*` resource, `cashReceipts.export`, `cashReceipts.CreateFromJobOrder` | `CashReceiptController`, `CashreceiptsTable`, `CashReceiptsExport` |
| Payment options | `paymentOptions.*` resource | `PaymentOptionController`, `PaymentOptionsTable` |
| Reports | `reports.index` (stub) | `ReportsController` |
| Accounts receivable | `accountReceivables.index`, `accountReceivables.show` | `AccountReceivablesController`, `AccountReceivables` |
| Admin tools | `adminTools.index` | `AdminToolController`, `AdminTools` |
| Calendar | `calendar.allTask`, `calendar.userTask` | `CalendarController`, `AllCalendar`, `UserCalendar` |

Every legacy table component extended `mediconesystems/livewire-datatables`
(`LivewireDatatable`). Those are replaced by `rappasoft/laravel-livewire-tables` v3 in the
rewrite.

---

## 2. Database

### 2.1 Tables

| Table | Rows (migrated) | Notes |
| --- | --- | --- |
| `users` | 94 | Jetstream schema, has `is_admin` |
| `employees` | 94 | linked to `users` via `user_id` |
| `clients` | 3 221 | |
| `client_contact_persons` | 3 716 | |
| `client_agents` | 6 | reference |
| `addresses` / `cities` / `provinces` | — | reference |
| `orders` | 12 253 | **the invoice**; money lives here |
| `job_orders` | 12 237 | soft deletes, `employee_id`, `order_id`, `job_order_rating` |
| `orders_products` | — | pivot with `qty` |
| `products` | 19 297 | line items |
| `product_classes` / `product_subclasses` | 85 / 578 | soft deletes |
| `inventory` | 1 310 | `current_quantity` is a *string* column |
| `categories` / `subcategories` | 233 / 220 | soft deletes |
| `withdrawal_slips` | 245 | |
| `inventory_withdrawal_slips` | — | pivot with `qty`, `particulars` |
| `cash_receipts` | 13 110 | |
| `payment_options` | 8 | reference |

### 2.2 Relationships

```
Client ─┬─ hasOne Address ─ belongsTo City ─ belongsTo Province
        ├─ hasMany ClientContactPerson
        ├─ belongsTo ClientAgent (agent_id)
        └─ hasMany Order

Order ─┬─ belongsTo Client
       ├─ belongsTo ClientContactPerson (contact_person_id)
       ├─ belongsTo JobOrderAgent (agent_id)
       ├─ belongsToMany Product (orders_products, pivot qty)
       └─ hasOne JobOrder

JobOrder ─┬─ belongsTo Order (order_id)
          ├─ belongsTo Employee (employee_id)      "artist in charge"
          ├─ belongsTo User (created_by / updated_by)
          └─ hasMany CashReceipt

Inventory ─┬─ belongsTo Category (categories_id)
           ├─ belongsTo Subcategory (subcategories_id)
           └─ belongsToMany WithdrawalSlip (inventory_withdrawal_slips, pivot qty + particulars)

CashReceipt ─┬─ belongsTo Client
             ├─ belongsTo JobOrder (job_order_id)
             └─ belongsTo PaymentOption (payment_option_id)
```

### 2.3 Column quirks that must be preserved or corrected deliberately

- `inventory.current_quantity` and `inventory.price` are `varchar`, not numeric.
- `products.amount` and `products.unit_price` are `decimal(13,2)` but were never cast, so the
  legacy code handled money as strings and floats inconsistently.
- `orders.total_amount`, `orders.down_payment`, `orders.balance` and the two columns on
  `cash_receipts` are `decimal(13,2)`.
- `orders.status` is free text; `orders.payment_status` is server derived.
- `clients.agent` and `orders.agent` were dropped by later migrations but are still present in
  the `Client`/`Order` casts.
- `users` still carries the Jetstream columns `current_team_id` and `profile_photo_path`,
  which the rewrite does not use.

---

## 3. Business rules

### 3.1 Money

`orders` is the invoice. There is no separate invoice table.

| Column | Meaning |
| --- | --- |
| `orders.total_amount` | billed amount (sum of the line items) |
| `orders.down_payment` | down payment taken when the job order was created |
| `orders.balance` | outstanding amount |

`cash_receipts.amount` is a payment received; `cash_receipts.balance` is the outstanding
balance **as of** that receipt (a snapshot, not the amount still owed after it).

Balance derivation:

```
job order create : balance = total_amount - down_payment
job order update : balance = total_amount - down_payment - SUM(cash_receipts.amount)
cash receipt     : orders.balance = request.balance            (client supplied, validated)
receipt deleted  : orders.balance = orders.balance + receipt.amount
```

### 3.2 Payment status

Exactly three values, derived — never set by the user:

| Value | Rule |
| --- | --- |
| `Fully Paid` | balance is empty or `0` |
| `Partially Paid` | balance `!=` total_amount (and not zero) |
| `Unpaid` | balance `==` total_amount |

The legacy "recalculate all" admin tool adds a `balance > 0` guard, so a `NULL` balance kept
its stale value and an over-payment was labelled `Partially Paid`.

### 3.3 Job order status

Free text on `orders.status`. The values are:

```
In Artist
In Production
Assigned: In Progress
Assigned: On Hold
Completed: Not Yet Claimed
Completed: Claimed
```

`Assigned: In Progress` was filterable but could not be selected in the form — it only
arrived through an import. The calendar only rendered four of the six statuses, so job
orders in `In Artist` or `In Production` produced no calendar event.

**Overdue** is `due_date < now()` and the status is not one of the two `Completed:` values.

`orders.status` (operational) and `orders.payment_status` (financial) are independent.

### 3.4 Validation rules

- `ProductAmount` — `qty * unit_price == amount`, compared with exact float equality.
- `cashReceiptRule` — `total_amount == amount + balance` (the anti-tamper check). The
  constructor arguments are named `$balance`/`$amount` but the call site passes
  `($totalAmount, $amount, $balance)`, so the error message prints the two values swapped.
- Job order create validated `total_amount` and `balance` with `size:` against the
  server-computed values, so a tampered total was rejected.

### 3.5 Admin tools

| Tool | Legacy behaviour |
| --- | --- |
| Recalculate all job order status | four mass updates on `orders.payment_status`; `updated_at` deliberately preserved |
| Recalculate all balances | **empty stub, never implemented** |
| Archive all classes | soft-deletes every `product_classes` row, one query per row |
| Archive all subclasses | same for `product_subclasses` |
| Set blank agent id | `where('agent_id', '=', null)` — invalid SQL, so it was a **no-op**; and it used the same hard coded id `6` for two different foreign keys (`client_agents` and `job_order_agents`) |

None of the four was protected by the `admins` middleware. The middleware existed but was
never applied to any route.

### 3.6 Accounts receivable

There is **no aging logic** in the legacy application. The report lists
`DATE | JOB ORDER ID | ACCOUNT RECEIVABLES` per order, where the receivable is
`orders.balance`. No buckets, no totals, no per-client summary, no overdue list. Aging has
to be designed from scratch on top of `orders.balance`, `orders.due_date` and
`orders.order_date`.

### 3.7 Calendar / tasks

There is **no task entity**. The "All Tasks" and "User Tasks" views are a job-order
due-date timeline:

- All Tasks: every job order, filtered by client, employee and four status checkboxes.
- User Tasks: only job orders whose `employees.user_id` equals the signed in user.

Neither view can create or edit a task. The legacy `reminding_payment` table was a payment
column and was dropped in 2021.

---

## 4. Legacy defects to not carry over

### Authorization and security

1. `adminTools.index` had no admin gate — the bulk operations were reachable by any
   authenticated user.
2. `PaymentOptionController` and `WithdrawalSlipController` had no policy at all.
3. `AccountReceivablesController`, `ReportsController`, `CalendarController`,
   `RoutingSlipController` and `AdminToolController` had no authorization.
4. The import and export endpoints were not admin gated.
5. There was no record level scoping — every authenticated user saw every client, job order
   and cash receipt.
6. There was no audit log at all; `created_by`/`updated_by` existed only on `job_orders` and
   `withdrawal_slips`.

### Financial correctness

7. `setBlankAgentID()` was a no-op and used a magic id across two different foreign keys.
8. `RecalculateAllBalances()` was never implemented.
9. `cashReceiptRule::message()` printed the amount and balance swapped.
10. `orders.balance` was written from the client request instead of being derived.
11. Job order create computed the balance without the receipts while update included them.
12. Deleting a job order archived the `job_orders` row but left the `orders` row and its
    money live and orphaned.

### Data handling

13. No import validation, no row level errors, no import summary, no chunking on any import.
14. The job order import only ever read **one** product per row, so multi-product job
    orders lost their line items.
15. The job order import created a new `Order` per row because it matched on the whole
    attribute set.
16. The job order import hard coded `example@gmail.com` as the contact e-mail.
17. The client export inner-joined the contact persons, so clients without a contact person
    were dropped and clients with several appeared several times.
18. The cash receipt export had no filtering at all.
19. No export protected against spreadsheet formula injection.
20. The job order export filtered on `created_at` while the import expected `order_date`.

### User interface

21. All lists used `mediconesystems/livewire-datatables`.
22. `ClientAgentTable` and `JobOrderAgentTable` rendered the wrong action partial.
23. `routingSlip.index` pointed at a view that does not exist — that route returned a 500.
24. `RoutingSlipController@index` eager loaded `orders` instead of `order`.
25. `ClientAgentController@destroy` redirected to the job order agents index.
26. Debug output (`info($request)`, `{{ info($jobOrder) }}`) was left in production views.
27. `InventoryTable`, `EmployeesTable` and `ClientscontactpersonsTable` had their own CSV
    export, which means three different export mechanisms were in use.

### Data integrity

28. No transaction anywhere: a job order wrote an order, its products, the job order and the
    down-payment receipt non-atomically.
29. Inventory quantities are mutated in place with no stock movement ledger; deleting a
    withdrawal slip silently adds the quantity back.
30. `or_number` on a cash receipt is free text with no uniqueness and no sequence.
31. Job order numbers, withdrawal slip numbers and receipt numbers have no sequence at all.
32. `orders.balance` is `NOT NULL`, but the admin tool's recalculation silently skips rows
    whose balance is `NULL`.

---

## 5. Features that must be designed from scratch

These do not exist in the legacy application at all:

- Accounts receivable **aging**, totals and per-client summaries.
- A real **task** entity with its own table, assignment, deadlines and status.
- A **numbering sequence** for job orders, cash receipts (OR numbers) and withdrawal slips.
- A **stock movement ledger**.
- **Activity logging** and an audit trail.
- An **archive** workflow with restore and bulk archiving rules that are safe.
- A **role and permission** model beyond the single `is_admin` flag.

---

## 6. Rewrite mapping

| Legacy concept | Rewrite |
| --- | --- |
| `mediconesystems/livewire-datatables` | `rappasoft/laravel-livewire-tables` v3 |
| `maatwebsite/excel ^3.1` | `maatwebsite/excel ^4.0` with validated imports and protected exports |
| `users.is_admin` | `spatie/laravel-permission` roles (`admin`, `manager`, `staff`); `is_admin` kept as a mirrored legacy flag |
| `created_by` / `updated_by` | `spatie/laravel-activitylog` audit trail, with the legacy columns retained |
| `acaronlex/laravel-calendar` | job order due-date timeline, rebuilt on the new table standard |
| `orders.status` free text | `App\Enums\JobOrderStatus` |
| `orders.payment_status` free text | `App\Enums\PaymentStatus`, always derived |
| hard deleted client | archived client (`clients.deleted_at`); a client is never deleted, so the job orders, the cash receipts and the withdrawal slips keep their history and their link to the client |
| `ClientExport` with inner joined contract persons | `App\Exports\ClientExport`, one row per contract person; a client without a contract person is exported with empty contract person columns |
| self service password reset by mail | administrator driven reset under *Administration → User Accounts*, with a forced password change at the next sign in (the deployment cannot send mail) |
| `DECIMAL(13,2)` job order money fields | `brick/money` value objects for monetary calculations, comparisons, and currency formatting; replace float-based arithmetic with exact monetary operations and define an explicit persistence strategy for database amounts and currency codes |