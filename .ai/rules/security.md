# Security Rules

- Every query must be scoped by authenticated user unless SuperAdmin explicitly requests all scope.
- Normal users must never update/delete global records.
- Users must never access another user's transactions.
- Validate all inputs using FormRequest.
- Never trust frontend role/scope values.
- SuperAdmin all-scope must be checked on backend.