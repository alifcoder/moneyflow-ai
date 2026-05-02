# Architecture

MoneyFlow AI uses a simple Laravel/Inertia architecture. The goal is to keep the code understandable while still showing production-minded boundaries for validation, authorization, query filtering, and UI rendering.

## Application Layers

- Controllers receive requests and return redirects or Inertia responses.
- FormRequest classes validate incoming data.
- Policies enforce authorization for references and transactions.
- Services contain business operations and reporting logic.
- Query classes centralize filterable table queries.
- Models define relationships, casts, and shared query scopes.
- Vue pages render the authenticated UI through Inertia.

## Backend Structure

- `app/Enums` contains fixed values such as user roles and transaction types.
- `app/Models` contains Eloquent models for users, references, and transactions.
- `app/Policies` contains backend authorization rules.
- `app/Queries` contains reusable filtered query builders.
- `app/Services` contains transaction and report service logic.
- `app/Http/Requests` contains validation rules.
- `app/Http/Controllers` stays thin and delegates behavior.

## Frontend Structure

- `resources/js/Pages` contains Inertia pages.
- Authenticated pages use `AuthenticatedLayout`.
- Reference and transaction indexes render mobile cards first, then table layouts for tablet and laptop screens.
- Dashboard and Reports use Chart.js only where charts are useful.
- Filters are submitted as query parameters so backend query classes and services remain the source of truth.

## Security Model

Normal users can manage only their own records. Global reference records are visible to normal users but read-only. SuperAdmin users can manage global records and may request all-scope views where explicitly supported.

Transactions are stricter than references: normal users can access only their own transactions. SuperAdmin users also see only their own transactions by default, and must explicitly request `scope=all` for all-user report or transaction views.

## Design Principles

- Keep the project simple and portfolio-friendly.
- Do not introduce complex accounting logic before the product needs it.
- Do not add Spatie Permission while two roles are enough.
- Do not trust frontend flags for authorization.
- Prefer focused tests over broad brittle tests.
- Keep responsive UI as part of feature completion, not a separate afterthought.
