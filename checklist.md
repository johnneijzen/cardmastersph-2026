# Client Job Order Application — Laravel Rewrite Checklist

## 1. Project Setup and Core Requirements

- [x] Review the existing application and document current functionality — `LEGACY_SYSTEM_ANALYSIS.md`
- [x] Identify existing database tables, relationships, and business rules — `LEGACY_SYSTEM_ANALYSIS.md`, `app/Enums/`
- [x] Set up the latest stable Laravel version compatible with the server environment — Laravel 13.38 on PHP 8.5
- [x] Configure environment variables and database connection — MariaDB, database sessions, cache and queue
- [x] Configure authentication and session management — Fortify, database sessions, login throttling, administrator driven password reset
- [x] Set up user roles and permissions — `spatie/laravel-permission`, roles `admin`/`manager`/`staff`
- [x] Configure application layout, navigation, and dashboard — Flux sidebar, `App\Support\Navigation`, dashboard statistics
- [x] Set up form validation and error handling — `App\Http\Requests\FormRequest`, `errors/403`, `errors/404`
- [x] Set up logging and exception handling — exception policy in `bootstrap/app.php`, `spatie/laravel-activitylog`
- [x] Implement database migrations and seeders — migrations run, reference data and roles seeded
- [x] Implement automated tests for critical business logic — 67 tests (roles, audit trail, dashboard, auth, password reset)
- [x] Configure file uploads and exports — `maatwebsite/excel` config, `SpreadsheetUpload` rule, `SpreadsheetExport`/`SpreadsheetImport` base classes
- [x] Review security, authorization, and data integrity — findings in `LEGACY_SYSTEM_ANALYSIS.md` §4

### 1.1 Open items

- [ ] **Password reset** — the deployment cannot send mail, so the reset is performed by an
      administrator under *Administration → User Accounts*. The account holder is then forced to
      choose their own password at the next sign in. The e-mail based reset of Fortify is disabled.
- [ ] **Backup restore test** — deferred by request. Backup creation and the health check are
      verified, but no restore has been performed yet. A restore test in a safe non-production
      environment is still required before the backups can be called usable.

## 2. Dashboard

- [x] Create dashboard page — Livewire page component `pages::dashboard`
- [x] Display relevant application statistics — `App\Services\DashboardService`
- [x] Display job order summaries — total, open, by status, overdue, due soon, archived
- [x] Display client summaries — total, new this month, clients with a balance
- [x] Display employee summaries — total, artists, with an account, assigned to job orders
- [x] Display inventory summaries — items and out of stock
- [x] Display cash receipt summaries — received this month, receipts, outstanding balance
- [x] Display pending tasks and upcoming activities — counts of the own tasks and the list of the job orders that are due
- [x] Apply role-based visibility to dashboard information — every section is only calculated and shown when the role holds the matching permission

## 3. Clients

- [x] Create clients list — `rappasoft/laravel-livewire-tables`, server side search, filters and pagination
- [x] Create client — `pages::clients.create`, creates the address and the client
- [x] View client details — `pages::clients.show`, shows the address, the agent, the contract persons and the job orders
- [x] Edit client — `pages::clients.edit`
- [x] Delete client with appropriate safeguards — a client is archived, never deleted, so the job orders, the cash receipts and the withdrawal slips keep their history
- [x] Implement search, filtering, and pagination — name, phone number, address, city and agent, filtered by status, agent, province and city
- [x] Validate client information — `App\Rules\ClientRules`, the name stays unique, also when the client is archived, and the city has to belong to the province
- [x] Preserve existing client relationships — the orders, the cash receipts, the withdrawal slips and the contract persons keep their link to an archived client

### 3.1 Contract Person List

- [x] Display contract persons — `rappasoft/laravel-livewire-tables`, server side search, filters and pagination
- [x] Add contract person — `pages::client-contact-persons.create`
- [x] View contract person details — `pages::client-contact-persons.show`
- [x] Edit contract person — `pages::client-contact-persons.edit`
- [x] Delete contract person — refused while a job order names the contract person
- [x] Associate contract persons with the correct clients — the client has to be an active client
- [x] Implement search and filtering — name, phone number, e-mail address, filtered by agent and province

### 3.2 Agent List

- [x] Display agents associated with clients — `rappasoft/laravel-livewire-tables` with the number of clients per agent
- [x] Add agent — `pages::client-agents.create`
- [x] Edit agent — `pages::client-agents.edit`
- [x] Delete agent — refused while a client still points at the agent
- [x] Validate agent information and relationships — the name of an agent has to be unique
- [x] Implement search and filtering — server side search on the name of the agent

### 3.3 Client Export

- [x] Export client records — `App\Exports\ClientExport`, one row per contract person
- [x] Support the required export format — `.xlsx` through `maatwebsite/excel`
- [x] Apply active filters to exported records where appropriate — the export holds the clients of the current search and filters
- [x] Verify exported data and column headings — the export is read back in the tests
- [x] Enforce export permissions — `clients.export`, the export is refused without the permission
- [x] Protect the export against spreadsheet formula injection — `App\Exports\Concerns\PreventsFormulaInjection`

## 4. Employees

- [x] Create employees list — `rappasoft/laravel-livewire-tables`, server side search, filters and pagination
- [x] Add employee — `pages::employees.create`, creates the address, the employee and optionally the account
- [x] View employee details — `pages::employees.show`
- [x] Edit employee — `pages::employees.edit`
- [x] Delete employee with appropriate safeguards — refused while the employee is the artist in charge of a job order
- [x] Implement search, filtering, and pagination — name, position, e-mail address, province and account status
- [x] Manage employee account status — `users.is_active`, a deactivated account is refused at the login
- [x] Manage employee roles and permissions where applicable — `employees.manage-accounts`, the role of the linked account is changed and written to the activity log

## 5. Inventory

- [x] Create inventory list — `rappasoft/laravel-livewire-tables`, server side search, filters and pagination
- [x] Add inventory item — `pages::inventory.create`, records the opening quantity as a stock movement
- [x] View inventory item details — `pages::inventory.show`, shows the classification, stock and stock history
- [x] Edit inventory item — `pages::inventory.edit`, the quantity is only changed through an adjustment
- [x] Delete inventory item with appropriate safeguards — archived (soft delete); refused while a withdrawal slip names the item
- [x] Implement search, filtering, and pagination — name and category, filtered by category, subcategory, stock and type
- [x] Track inventory quantities — `inventory_movements` keeps the quantity before, the change and the quantity after
- [x] Validate stock movements — `App\Rules\InventoryRules`, a reason is required and the quantity may not go below zero
- [x] Preserve inventory transaction history — the stock movements and the withdrawal history survive archiving

### 5.1 View Categories

- [x] Display inventory categories — table with the number of subcategories and inventory items per category
- [x] Add category — `pages::categories.create`, the name of an active category has to be unique
- [x] Edit category — `pages::categories.edit`
- [x] Delete category — archived (soft delete), so the activity trail is kept
- [x] Prevent deletion when dependent records exist — `App\Services\CategoryService`, refused while subcategories or inventory items still use the category, including archived records

### 5.2 View Subcategories

- [x] Display inventory subcategories — table with the category and the number of inventory items per subcategory
- [x] Add subcategory — `pages::subcategories.create`, assigned to a category
- [x] Edit subcategory — `pages::subcategories.edit`, the subcategory can be moved to another category
- [x] Delete subcategory — archived (soft delete); refused while inventory items still use the subcategory, including archived items
- [x] Associate each subcategory with the correct category — `subcategories.categories_id`; the legacy subcategories stay unclassified until they are assigned
- [x] Validate category and subcategory relationships — `App\Rules\SubcategoryRules` and `App\Rules\InventoryRules`, a name is unique inside its category and an item cannot use the subcategory of another category

### 5.3 Withdraw Slip

- [x] Create inventory withdrawal slip — `pages::withdrawal-slips.create`, `App\Services\WithdrawalSlipService`
- [x] Assign withdrawal slip number — `App\Services\NumberSequenceService`, `withdrawal_slips.slip_number` (`WS-YYYY-######`)
- [x] Add withdrawn items and quantities — `inventory_withdrawal_slips` with `qty` and `particulars`
- [x] Record withdrawal date and responsible employee — `withdrawal_slips.date` and `employee_id`
- [x] Validate available stock before withdrawal — `App\Rules\WithdrawalSlipRules`, the service locks the items and checks the quantity again
- [x] Update inventory quantities safely — one transaction around the slip, the locked items and the `withdrawal` stock movements
- [x] View and print withdrawal slips — `pages::withdrawal-slips.show`, `pages::withdrawal-slips.print`
- [x] Maintain withdrawal history — the slip is archived instead of deleted, the reversed slips stay available through the status filter and the stock movements stay readable
- [x] Prevent duplicate or unauthorized stock deductions — an item may only be named once per slip, the items are locked, the reversal can only run once and every action is checked against `withdrawal-slips.*`

### 5.4 Inventory Import

- [x] Import inventory records from file — `App\Imports\InventoryImport`, `pages::inventory.import`
- [x] Validate file format and required columns — `App\Rules\SpreadsheetUpload`, required headings checked before any row is read
- [x] Validate categories and subcategories — the category has to exist and the subcategory has to belong to it; missing records are reported instead of created
- [x] Detect duplicate or conflicting records — duplicate rows in the file and items that already exist are reported; an archived item is not recreated
- [x] Preview import results before processing — the preview runs the import in a transaction that is rolled back, so nothing is kept
- [x] Report invalid rows and import errors — every row keeps its status and messages, shown by spreadsheet row number
- [x] Provide an import summary — added, updated, unchanged and rejected counts in `App\Imports\Concerns\CollectsImportResults`
- [x] Prevent unintended inventory quantity changes — an existing item keeps its quantity; a different quantity is a conflict and has to go through a stock adjustment

## 6. Job Orders

- [ ] Create job order list
- [ ] Create job order
- [ ] View job order details
- [ ] Edit job order
- [ ] Delete or cancel job orders according to business rules
- [ ] Assign job order numbers
- [ ] Associate job orders with clients
- [ ] Assign agents and responsible employees
- [ ] Manage job order statuses
- [ ] Validate required job order information
- [ ] Implement search, filtering, and pagination
- [ ] Preserve job order history and relationships

### 6.1 View Classes

- [ ] Display job order classes
- [ ] Add class
- [ ] Edit class
- [ ] View class details
- [ ] Archive class
- [ ] Prevent unintended changes to related job orders

### 6.2 View Subclasses

- [ ] Display job order subclasses
- [ ] Add subclass
- [ ] Edit subclass
- [ ] View subclass details
- [ ] Archive subclass
- [ ] Associate subclasses with the correct classes
- [ ] Validate class and subclass relationships

### 6.3 Agents List

- [ ] Display agents available for job orders
- [ ] Add agent
- [ ] Edit agent
- [ ] View agent details
- [ ] Delete or deactivate agents as appropriate
- [ ] Associate agents with job orders
- [ ] Support agent search and filtering

### 6.4 Job Order Export

- [ ] Export job order records
- [ ] Include required job order fields
- [ ] Support filtering before export
- [ ] Verify status, client, and agent information
- [ ] Enforce export permissions

### 6.5 Job Order Import

- [ ] Import job orders from file
- [ ] Validate file format and required columns
- [ ] Validate clients, classes, subclasses, and agents
- [ ] Validate job order statuses and dates
- [ ] Detect duplicate job orders
- [ ] Preview imported records
- [ ] Report invalid rows and import errors
- [ ] Provide an import summary
- [ ] Prevent partial or inconsistent imports

### 6.6 Archive Job Orders

- [ ] Archive individual job orders
- [ ] View archived job orders
- [ ] Search and filter archived records
- [ ] Restore archived job orders where permitted
- [ ] Exclude archived records from active lists by default
- [ ] Preserve related records and historical data

## 7. Cash Receipts

- [ ] Create cash receipt list
- [ ] Create cash receipt
- [ ] View cash receipt details
- [ ] Edit cash receipt where permitted
- [ ] Void or cancel receipts according to business rules
- [ ] Assign receipt numbers
- [ ] Associate receipts with clients and relevant job orders
- [ ] Record payment dates and amounts
- [ ] Validate payment amounts
- [ ] Track receipt status
- [ ] Implement search, filtering, and pagination
- [ ] Maintain an audit trail of financial changes

### 7.1 Payment Options

- [ ] Display payment options
- [ ] Add payment option
- [ ] Edit payment option
- [ ] Activate or deactivate payment options
- [ ] Prevent deletion when existing receipts depend on an option

### 7.2 Cash Receipt Export

- [ ] Export cash receipt records
- [ ] Include required payment information
- [ ] Support date range and other applicable filters
- [ ] Verify totals and exported amounts
- [ ] Enforce financial data access permissions

## 8. Reports

### 8.1 Accounts Receivable

- [ ] Create accounts receivable report
- [ ] Display outstanding client balances
- [ ] Calculate amounts billed
- [ ] Calculate payments received
- [ ] Calculate outstanding balances
- [ ] Support client filtering
- [ ] Support date range filtering
- [ ] Support job order filtering where applicable
- [ ] Display aging balances where required
- [ ] Reconcile report totals against cash receipts and job orders
- [ ] Export accounts receivable report
- [ ] Restrict access to authorized users

## 9. Calendar

### 9.1 All Tasks View

- [ ] Display tasks for all authorized users
- [ ] Display task dates and deadlines
- [ ] View task details
- [ ] Create tasks where permitted
- [ ] Edit tasks where permitted
- [ ] Filter tasks by date, status, and assigned user
- [ ] Support calendar navigation
- [ ] Display overdue and upcoming tasks
- [ ] Enforce permissions for viewing and modifying tasks

### 9.2 User Tasks View

- [ ] Display the current user's tasks
- [ ] View task details
- [ ] Create tasks where permitted
- [ ] Edit assigned tasks where permitted
- [ ] Update task status
- [ ] Filter tasks by date and status
- [ ] Display upcoming and overdue tasks
- [ ] Prevent unauthorized access to other users' tasks

## 10. Admin Tools

> **Important:** Administrative bulk operations should require elevated permissions, confirmation, audit logging, and safeguards against unintended data changes.

### 10.1 Recalculate All Job Order Status

- [ ] Implement bulk job order status recalculation
- [ ] Define the authoritative status calculation rules
- [ ] Identify required source fields and status dependencies
- [ ] Preview the number of records affected
- [ ] Require administrator confirmation
- [ ] Execute the recalculation safely
- [ ] Process large datasets in batches where necessary
- [ ] Log execution details and errors
- [ ] Display the number of updated and skipped records
- [ ] Verify recalculated statuses against business rules

### 10.2 Archive All Classes

- [ ] Implement bulk class archiving
- [ ] Preview classes that will be affected
- [ ] Identify dependent subclasses and job orders
- [ ] Require administrator confirmation
- [ ] Apply the agreed rules for active and referenced classes
- [ ] Log archived records and operation results
- [ ] Verify that active records remain consistent

### 10.3 Archive All Subclasses

- [ ] Implement bulk subclass archiving
- [ ] Preview subclasses that will be affected
- [ ] Identify dependent job orders
- [ ] Require administrator confirmation
- [ ] Apply the agreed rules for active and referenced subclasses
- [ ] Log archived records and operation results
- [ ] Verify that historical job order relationships remain intact

### 10.4 Set Blank Agent ID

- [ ] Identify records with blank or null agent IDs
- [ ] Define which tables and records are affected
- [ ] Define the intended replacement value or handling rule
- [ ] Preview affected records before making changes
- [ ] Require administrator confirmation
- [ ] Validate foreign key and relationship constraints
- [ ] Execute updates in a transaction or safe batches
- [ ] Log updated records and operation results
- [ ] Verify that no unrelated records were modified

## 11. Security and Permissions

- [ ] Implement authentication and logout
- [ ] Implement role-based access control
- [ ] Protect all administrative tools
- [ ] Protect financial reports and cash receipt operations
- [ ] Validate authorization on the server for every protected action
- [ ] Apply CSRF protection to state-changing requests
- [ ] Validate and sanitize uploaded files
- [ ] Validate exported data and prevent spreadsheet formula injection
- [ ] Protect sensitive records from unauthorized access
- [ ] Implement audit logging for important operations
- [ ] Configure secure production settings

## 12. Testing and Data Migration

- [ ] Document the existing database schema
- [ ] Map legacy tables and fields to the new schema
- [ ] Preserve existing record IDs where required
- [ ] Migrate clients and contract persons
- [ ] Migrate agents and employees
- [ ] Migrate inventory, categories, and subcategories
- [ ] Migrate withdrawal slips and stock history
- [ ] Migrate job orders, classes, and subclasses
- [ ] Migrate cash receipts and payment options
- [ ] Migrate tasks and calendar data
- [ ] Preserve archive flags and historical relationships
- [ ] Validate migrated record counts
- [ ] Reconcile financial balances
- [ ] Test CRUD operations for every module
- [ ] Test imports and exports
- [ ] Test bulk administrative operations
- [ ] Test authorization and access restrictions
- [ ] Test performance with production-sized datasets
- [ ] Test database backup and restore procedures
- [ ] Conduct user acceptance testing

## 13. Deployment and Go-Live

- [ ] Configure production environment variables
- [ ] Configure production database and credentials
- [ ] Configure queues and scheduled tasks if required
- [ ] Configure storage permissions
- [ ] Enable HTTPS
- [ ] Run database migrations
- [ ] Back up the legacy database before migration
- [ ] Perform a trial migration and validate the results
- [ ] Prepare a rollback plan
- [ ] Deploy the new application
- [ ] Verify critical modules after deployment
- [ ] Verify reports and financial balances
- [ ] Train users and administrators
- [ ] Document maintenance and recovery procedures

---

## Completion Criteria

- [ ] All legacy features are accounted for
- [ ] All required modules and submenus are implemented
- [ ] Existing business rules are preserved or explicitly approved for change
- [ ] Migrated data has been validated
- [ ] Security and permissions have been tested
- [ ] Automated tests pass
- [ ] User acceptance testing is approved
- [ ] Production deployment is verified
- [ ] Legacy application retirement is approved
