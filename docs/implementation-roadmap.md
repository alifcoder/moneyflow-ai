# MoneyFlow AI Implementation Roadmap

## Phase 1: Foundation

- Confirm target stack assumptions: Laravel 13, PHP 8.5, Inertia 3, Vue 3, PostgreSQL.
- Establish project folders for enums, services, queries, and data objects.
- Define base enums for user roles and transaction types.
- Keep documentation aligned with implementation decisions.

## Phase 2: Authentication And Roles

- Add role persistence for users.
- Cast role values through `UserRoleEnum`.
- Implement backend authorization rules for SuperAdmin and normal users.
- Add policies for user-owned and global records.

## Phase 3: Reference Data

- Add global and user-owned reference records.
- Ensure normal users can read global records but cannot update or delete them.
- Add service classes, query classes, FormRequests, policies, and focused tests.

## Phase 4: Transactions

- Add income and expense transaction models and workflows.
- Cast transaction type values through `TransactionTypeEnum`.
- Scope all transaction access by authenticated user.
- Use numeric or decimal database columns for money values.

## Phase 5: Reports

- Add dashboard and reporting pages after transaction data exists.
- Keep charts responsive and limited to dashboard or report views.
- Use backend-scoped query parameters for filters.

## Phase 6: Review And Tests

- Add feature tests for authorization, ownership boundaries, validation, and query filtering.
- Review for user data leaks, missing scopes, unnecessary transactions, and over-engineering.
- Run formatter and test suite before completion of each implementation phase.
