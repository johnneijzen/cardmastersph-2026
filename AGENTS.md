<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>

# Additional Project Requirements — Client Job Order Rewrite

These requirements supplement the Laravel Boost guidelines above. Keep all existing project rules unless a requirement below explicitly extends them.

## Required Package Versions

The user has approved the following five Composer packages:

```json
{
  "maatwebsite/excel": "^4.0",
  "rappasoft/laravel-livewire-tables": "^3.10",
  "spatie/laravel-activitylog": "^5.1",
  "spatie/laravel-backup": "^10.3",
  "brick/money": "^0.15.2"
}
```

- Check compatibility with the installed Laravel and PHP versions before installation.
- Confirm installed package versions and follow documentation for those versions.
- Do not change version constraints or substitute major versions without explaining the conflict and obtaining approval.
- Approval applies only to these five packages. Other dependency changes require approval.
- Keep `composer.json` and `composer.lock` consistent.

## Excel Imports and Exports — `maatwebsite/excel`

Use Laravel Excel to replace the legacy Excel/table export library where appropriate.

- Create dedicated import and export classes for each relevant feature.
- Validate file types, file sizes, required headings, data types, and referenced records.
- Provide server-side validation, row-level errors, and an import summary.
- Handle duplicate and conflicting records deliberately.
- Use chunking and queued processing for large files where appropriate.
- Use database transactions for related writes where practical.
- Apply authorization and current filters to exports where appropriate. Never export unauthorized fields.
- Prevent spreadsheet formula injection in user-controlled values where relevant.
- Test valid and invalid imports, exports, permissions, and failure cases.
- Apply these conventions to client exports, inventory imports, job order imports/exports, cash receipt exports, and accounts receivable exports where applicable.

## Standard Data Tables — `rappasoft/laravel-livewire-tables`

Use Rappasoft Laravel Livewire Tables v3 as the standard library for application list and table pages.

- Follow the installed v3 API and documentation.
- Use server-side searching, sorting, filtering, and pagination where appropriate.
- Enforce authorization and record visibility in the underlying query, not only in the UI.
- Whitelist searchable and sortable columns. Never use arbitrary user input as SQL identifiers.
- Use eager loading where appropriate to prevent N+1 queries.
- Keep complex business logic out of table definitions and views.
- Provide clear empty, loading, and error states, with responsive and accessible behavior.
- Confirm destructive and bulk actions, and authorize every action server-side.
- Test searching, sorting, filtering, pagination, and access restrictions.
- Do not introduce another table library without a documented need and approval.

## Application-Wide Activity Logging — `spatie/laravel-activitylog`

Activity logging is required for security, accountability, and auditing across all applicable Eloquent models.

- Inventory Eloquent models and document intended logging coverage.
- Establish a reusable convention, such as a shared trait, base-model convention, or tested observer/registration approach, so existing and future applicable models are covered.
- Log create, update, delete, and restore events where supported by the model lifecycle.
- For updates, record meaningful changed attributes rather than unchanged fields.
- Capture the authenticated actor when available, event, model type and ID, timestamp, and relevant operation context.
- Cover imports, background jobs, scheduled tasks, administrative bulk operations, and system-initiated changes.
- Audit important client, inventory, job order, cash receipt, payment option, archive/restore, and admin-tool operations.
- Use consistent log names and understandable descriptions.
- Restrict audit log viewing to authorized roles. Ordinary users must not be able to tamper with audit records.
- Test that every intended model and event is logged. Installing the package alone does not provide complete coverage.

### Sensitive Data and Audit Security

- Never log passwords, password hashes, authentication/session tokens, API keys, secrets, recovery codes, or other credentials.
- Explicitly exclude or redact sensitive attributes.
- Avoid unnecessary personal or financial data, full request payloads, and uploaded file contents.
- Review casts, nested JSON fields, accessors, and custom properties for indirectly exposed secrets.
- Enforce appropriate access controls, retention policies, and backup protection for audit logs.

## Backup and Recovery — `spatie/laravel-backup`

Use Spatie Laravel Backup for configured database and application-file backups.

- Configure database connections, included files/directories, and exclusions for caches and temporary files.
- Store backups in an approved protected destination, preferably separate from the production server.
- Configure scheduling, retention, cleanup, failure notifications, and backup-health monitoring.
- Protect backup destinations and credentials. Never expose backups through public web paths.
- Restrict who can trigger, access, or restore backups.
- Document recovery procedures, required environment configuration, and dependencies.
- Verify backup creation and test restoration in a safe non-production environment.
- Record the result and date of restore tests. Never claim a backup is usable until verified.
- Treat backups as complementary to migrations, audit logs, and a tested disaster-recovery plan.

## Monetary Values and Calculations — `brick/money`

Use `brick/money` as the standard domain-level money library for job orders and other applicable financial features. Existing job order monetary fields use `DECIMAL(13,2)`. Migrate application-level money handling to `Brick\Money\Money` without introducing floating-point arithmetic.

### Money Representation and Calculations

- Use `Brick\Money\Money` for monetary calculations, comparisons, and currency-aware operations.
- Use explicit ISO 4217 currency codes. Never assume a currency symbol uniquely identifies a currency.
- Never use PHP floats for monetary calculations, comparisons, totals, discounts, taxes, balances, or payment amounts.
- Never construct money values from floats. Use validated integer minor units or exact decimal strings with APIs supported by the installed package version.
- Define explicit rounding modes and business rounding rules.
- Respect currency-specific minor units. Do not assume every currency has two decimal places.
- Use Brick Money arithmetic and allocation operations instead of ad hoc decimal arithmetic.
- Keep pricing, discounts, taxes, totals, payments, and balances in appropriate domain services rather than duplicating business logic across controllers, Livewire components, and views.

### Job Order Fields and Database Persistence

- Inventory all monetary fields, including unit prices, line totals, subtotals, discounts, taxes, grand totals, payments, and balances.
- Use `Money` objects in the application layer at appropriate boundaries instead of passing raw decimals or floats through business logic.
- Retain existing `DECIMAL(13,2)` columns initially when needed for backward compatibility. Brick Money does not automatically require a database schema change.
- Define a consistent persistence strategy. When retaining decimal columns, store and retrieve exact decimal strings and convert them to and from `Money` without passing through floats.
- Before proposing integer minor-unit storage or another schema change, assess existing data, currency precision, column capacity, migration safety, and compatibility. Obtain approval before material schema changes outside the approved scope.
- Explicitly define currency handling. For a single-currency application, document and enforce the currency invariant. For multiple currencies, persist the currency code with each amount or use an equally explicit and reliable currency relationship.
- Validate monetary inputs for precision, scale, range, currency, and rounding rules.
- Prevent silent truncation, unintended rounding, overflow, and currency mixing.
- Preserve existing job order values and verify calculations during migration.
- Never silently change historical job order amounts or recalculate them without an approved business rule.

### Forms, Tables, Exports, and Reports

- Centralize conversion between user-entered values and `Money` objects.
- Centralize currency-aware formatting for forms, Livewire tables, detail pages, print views, PDFs, and spreadsheet exports.
- Keep display formatting separate from persisted values and calculation logic.
- Never use formatted currency strings as database values or arithmetic inputs.
- Parse imports from validated decimal strings without floating-point conversion.
- Preserve intended precision in exports.
- Apply consistent rounding and currency rules to job orders, balances, receipts, and accounts receivable reports.

### Money Testing Requirements

- Test construction from exact decimal strings and integer minor units.
- Test addition, subtraction, multiplication, discounts, taxes, rounding, and comparisons.
- Test currency precision, invalid values, overflow boundaries, and currency mismatch handling.
- Test database persistence and retrieval for existing `DECIMAL(13,2)` fields without precision loss.
- Test job order creation, editing, imports, exports, payment recording, receipts, and balance calculations.
- Add regression tests comparing representative existing job orders with the new implementation.
- Verify that no monetary calculation path converts amounts to floats.

## General Rewrite Requirements

- Preserve existing business rules and historical relationships unless an approved requirement explicitly changes them.
- Prefer incremental, reversible migrations over destructive schema changes.
- Enforce authorization server-side for all relevant operations.
- Validate all user-controlled input, including spreadsheet imports and bulk actions.
- Add or update automated tests for changed features, including authorization, validation, failure paths, and historical-data compatibility.
- Follow existing Laravel Boost guidelines and project conventions unless explicitly extended by these requirements.
- Document significant implementation decisions, schema changes, rounding rules, and operational procedures.