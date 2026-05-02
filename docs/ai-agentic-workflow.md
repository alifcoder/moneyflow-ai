# AI-Agentic Workflow

MoneyFlow AI was implemented with an AI-assisted workflow that uses explicit rules, focused prompts, and review gates. The purpose is to make AI collaboration auditable and repeatable for a portfolio project.

## Why AI Rules Exist

The `.ai/rules` files constrain implementation choices before code is written. They reduce avoidable drift in areas that matter for this project:

- Authorization and user scoping.
- Global reference behavior.
- Laravel and Inertia conventions.
- PostgreSQL-friendly data modeling.
- Mobile-first responsive UI.
- Testing and formatting expectations.
- Avoiding premature architecture such as DDD, CQRS, or complex accounting.

The rules make the AI workflow closer to working with a team playbook than asking for isolated code snippets.

## Agent Roles

The `.ai/agents` files describe review lenses used during implementation:

- Architect Agent: keeps the design simple and checks consistency.
- Backend Agent: focuses on Laravel structure, services, requests, policies, and controllers.
- Frontend Agent: focuses on Inertia pages, query-param filters, cards, tables, and responsive charts.
- Review Agent: checks authorization leaks, scoping, money types, over-engineering, and missing tests.
- Testing Agent: represents the expectation that behavior changes include focused test coverage.
- Database Agent: represents database design checks such as foreign keys, indexes, and decimal money fields.
- Documentation Agent: keeps project docs aligned with the implementation.

These roles do not require separate tooling to be valuable. They act as structured review perspectives for each task.

## Implementation Prompts

The `.ai/prompts` folder captures feature-by-feature implementation tasks. Prompts intentionally separate work into phases:

- project bootstrap
- authentication and roles
- reference CRUD
- transactions
- reports
- tests
- review and refactor

This phased approach keeps changes smaller, makes review easier, and prevents unrelated business features from leaking into foundation tasks.

## Review Workflow

Before finishing a task, review the changes against this checklist:

- Authorization: policies and backend checks protect every action.
- User scoping: normal users cannot read or mutate another user's private data.
- Global references: normal users can read global references but cannot update or delete them.
- Responsive UI: mobile uses cards where tables would overflow; tablet and laptop layouts remain usable.
- Validation: input rules live in FormRequest classes.
- Tests: feature tests cover new behavior and security boundaries.
- Formatting: PHP files pass Laravel Pint.

## CI Quality Gates

The expected quality gates are:

```bash
composer format
php artisan test
npm run build
```

For a CI pipeline, the same gates should run on every pull request. A stricter CI setup can use `composer format:test` instead of mutating files during the check phase.

Recommended CI sequence:

1. Install PHP dependencies.
2. Install Node dependencies.
3. Prepare the test environment.
4. Run `composer format:test`.
5. Run `php artisan test`.
6. Run `npm run build`.

## Portfolio Value

The AI-agentic workflow is part of the project deliverable. It demonstrates that AI was used with constraints, review criteria, and verification commands instead of replacing engineering judgment.
