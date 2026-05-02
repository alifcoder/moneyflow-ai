# Database Rules

- Use PostgreSQL.
- Use numeric/decimal for money values, never float.
- Use foreign keys.
- Use indexes for user_id, type, transaction_date, currency_id, category_id, cashbox_id.
- Global reference records use user_id = null.
- User-owned records use user_id = authenticated user id.
- Transactions must always have user_id.
- Soft deletes are not required unless specifically requested.