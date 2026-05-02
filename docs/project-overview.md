# MoneyFlow AI Project Overview

MoneyFlow AI is a portfolio-focused personal finance application built on a simple Laravel and Inertia architecture.

## Stack Assumptions

- Laravel 13 application conventions.
- PHP 8.5 as the target runtime.
- Inertia.js 3 as the frontend bridge.
- Vue 3 for client-side pages and components.
- PostgreSQL as the production database.
- Vite and Tailwind CSS for frontend builds and styling.

Current package constraints should be reviewed separately before dependency upgrades are made. This base structure task does not change Composer, npm, environment, or database configuration files.

## Architecture Baseline

- Controllers should stay thin.
- Business rules should live in service classes under `app/Services`.
- Table filtering and query composition should live in query classes under `app/Queries`.
- Fixed values should use PHP enums under `app/Enums`.
- Data transfer objects or structured request/response payloads should live under `app/Data` when useful.
- Validation should use FormRequest classes.
- Authorization should use policies and backend checks.

## Scope Boundaries

This project structure establishes the foundation only. Business CRUD, database schema changes, authentication role wiring, reports, and transaction workflows are intentionally deferred to later implementation tasks.
