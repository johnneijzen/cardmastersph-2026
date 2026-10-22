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

- [ ] Create inventory list
- [ ] Add inventory item
- [ ] View inventory item details
- [ ] Edit inventory item
- [ ] Delete inventory item with appropriate safeguards
- [ ] Implement search, filtering, and pagination
- [ ] Track inventory quantities
- [ ] Validate stock movements
- [ ] Preserve inventory transaction history

### 5.1 View Categories

- [ ] Display inventory categories
- [ ] Add category
- [ ] Edit category
- [ ] Delete category
- [ ] Prevent deletion when dependent records exist

### 5.2 View Subcategories

- [ ] Display inventory subcategories
- [ ] Add subcategory
- [ ] Edit subcategory
- [ ] Delete subcategory
- [ ] Associate each subcategory with the correct category
- [ ] Validate category and subcategory relationships

### 5.3 Withdraw Slip

- [ ] Create inventory withdrawal slip
- [ ] Assign withdrawal slip number
- [ ] Add withdrawn items and quantities
- [ ] Record withdrawal date and responsible employee
- [ ] Validate available stock before withdrawal
- [ ] Update inventory quantities safely
- [ ] View and print withdrawal slips
- [ ] Maintain withdrawal history
- [ ] Prevent duplicate or unauthorized stock deductions

### 5.4 Inventory Import

- [ ] Import inventory records from file
- [ ] Validate file format and required columns
- [ ] Validate categories and subcategories
- [ ] Detect duplicate or conflicting records
- [ ] Preview import results before processing
- [ ] Report invalid rows and import errors
- [ ] Provide an import summary
- [ ] Prevent unintended inventory quantity changes

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
