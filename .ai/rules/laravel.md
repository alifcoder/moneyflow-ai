# Laravel Rules

- Use Laravel 13 conventions.
- Use strict typing where practical.
- Use enum casts for enum columns.
- Use FormRequest for validation.
- Use Policy classes for access control.
- Use Service classes for create/update business logic.
- Use Query classes for filtering tables.
- Avoid business logic in controllers.
- Use database transactions only when multiple related writes happen.
- Do not wrap single-table simple CRUD in DB::transaction().