# Architecture Rules

- Keep the project simple and portfolio-focused.
- Use Laravel service classes for business logic.
- Controllers must stay thin.
- Use FormRequest validation.
- Use Policies for authorization.
- Use Enums for fixed values.
- Avoid over-engineering.
- Do not introduce DDD, CQRS, event sourcing, or complex accounting logic unless explicitly requested.
- Every user-owned model must support global default records using nullable user_id.
- Normal users can read global records but cannot update or delete them.
- SuperAdmin can manage all records.