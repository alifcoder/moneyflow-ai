# AI Workflow

## Working Rules

- Read `.ai` guidance before implementation.
- Keep changes scoped to the active task.
- Do not implement business CRUD unless the task explicitly asks for it.
- Prefer Laravel 13 conventions and simple application structure.
- Use enums for fixed values, services for business logic, and query classes for filterable table data.
- Validate input with FormRequest classes and authorize actions on the backend.

## Stack Assumptions

The target stack for AI-assisted work is Laravel 13, PHP 8.5, Inertia.js 3, Vue 3, PostgreSQL, Vite, and Tailwind CSS.

If installed package constraints or environment files do not yet match these assumptions, treat that as a separate dependency and configuration task rather than silently changing them during feature work.

## Implementation Checklist

- Confirm the task scope and avoid unrelated files.
- Inspect existing code before adding new abstractions.
- Add or update focused tests when behavior changes.
- Run Laravel Pint after PHP changes.
- Run relevant tests when implementation changes behavior.
- Document meaningful architecture or workflow decisions in `docs/`.
