# MoneyFlow AI

MoneyFlow AI is a portfolio-focused personal finance application for tracking income, expenses, user-owned reference data, and simple financial reports. The project is intentionally built with a clear Laravel/Inertia structure so the implementation can demonstrate backend authorization, scoped data access, responsive UI, and an AI-assisted development workflow.

## Stack

- Laravel 13
- PHP 8.5
- PostgreSQL
- Inertia.js 3
- Vue 3
- Tailwind CSS
- Chart.js
- Vite

## Features

- Email/password authentication through Laravel Breeze.
- User roles with `USER` and `SUPER_ADMIN`.
- Global and user-owned currencies, categories, and cashboxes.
- CRUD UI for reference records with mobile card lists and desktop tables.
- Income and expense transaction CRUD.
- Backend policies and query scopes for ownership and SuperAdmin behavior.
- Dashboard with totals, recent transactions, and cashbox balances.
- Reports with monthly, category, currency, and cashbox summaries.
- Responsive bar and pie charts for dashboard and report pages.
- Feature tests for authorization, scoping, validation, CRUD flows, and reports.

## Screenshots

Screenshots can be added here before publishing the portfolio page.

- Dashboard mobile
- Reports desktop
- Transactions mobile
- Reference management tablet

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the database connection in `.env`. The production target is PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=moneyflow_ai
DB_USERNAME=postgres
DB_PASSWORD=
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

Build frontend assets:

```bash
npm run build
```

Start the local development stack:

```bash
composer run dev
```

Default seeded SuperAdmin credentials:

- Email: `admin@moneyflow.test`
- Password: `password`

## Testing And Quality Commands

```bash
composer format
php artisan test
npm run build
```

The Composer test script is also available:

```bash
composer test
```

## AI-Agentic Workflow

The repository includes a `.ai` folder that documents the implementation rules, agent roles, and task prompts used to build the application. These files are part of the portfolio story: they show how AI assistance was constrained by architecture, security, database, UI, and testing rules rather than being used as uncontrolled code generation.

The workflow is:

1. Read `.ai` rules before each implementation task.
2. Use focused prompts for one feature layer at a time.
3. Keep controllers thin and push business rules into services, requests, policies, and query classes.
4. Review for authorization leaks, user scoping, global reference behavior, responsive UI, validation, tests, and formatting.
5. Run formatter, backend tests, and frontend build before finishing.

Additional documentation:

- [Architecture](docs/architecture.md)
- [Database Design](docs/database-design.md)
- [AI Agentic Workflow](docs/ai-agentic-workflow.md)
- [Responsive UI Guidelines](docs/responsive-ui-guidelines.md)
- [Future Improvements](docs/future-improvements.md)
