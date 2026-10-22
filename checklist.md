# Client Job Order Application — Laravel Rewrite Checklist

## 1. Project Setup and Core Requirements

- [ ] Review the existing application and document current functionality
- [ ] Identify existing database tables, relationships, and business rules
- [ ] Set up the latest stable Laravel version compatible with the server environment
- [ ] Configure environment variables and database connection
- [ ] Configure authentication and session management
- [ ] Set up user roles and permissions
- [ ] Configure application layout, navigation, and dashboard
- [ ] Set up form validation and error handling
- [ ] Set up logging and exception handling
- [ ] Implement database migrations and seeders
- [ ] Implement automated tests for critical business logic
- [ ] Configure file uploads and exports
- [ ] Review security, authorization, and data integrity

## 2. Dashboard

- [ ] Create dashboard page
- [ ] Display relevant application statistics
- [ ] Display job order summaries
- [ ] Display client summaries
- [ ] Display employee summaries
- [ ] Display inventory summaries
- [ ] Display cash receipt summaries
- [ ] Display pending tasks and upcoming activities
- [ ] Apply role-based visibility to dashboard information

## 3. Clients

- [ ] Create clients list
- [ ] Create client
- [ ] View client details
- [ ] Edit client
- [ ] Delete client with appropriate safeguards
- [ ] Implement search, filtering, and pagination
- [ ] Validate client information
- [ ] Preserve existing client relationships

### 3.1 Contract Person List

- [ ] Display contract persons
- [ ] Add contract person
- [ ] View contract person details
- [ ] Edit contract person
- [ ] Delete contract person
- [ ] Associate contract persons with the correct clients
- [ ] Implement search and filtering

### 3.2 Agent List

- [ ] Display agents associated with clients
- [ ] Add agent
- [ ] Edit agent
- [ ] Delete agent
- [ ] Validate agent information and relationships
- [ ] Implement search and filtering

### 3.3 Client Export

- [ ] Export client records
- [ ] Support the required export format
- [ ] Apply active filters to exported records where appropriate
- [ ] Verify exported data and column headings
- [ ] Enforce export permissions

## 4. Employees

- [ ] Create employees list
- [ ] Add employee
- [ ] View employee details
- [ ] Edit employee
- [ ] Delete employee with appropriate safeguards
- [ ] Implement search, filtering, and pagination
- [ ] Manage employee account status
- [ ] Manage employee roles and permissions where applicable

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
