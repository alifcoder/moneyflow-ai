# Database Design

MoneyFlow AI targets PostgreSQL for production and uses SQLite in automated tests. Money values are stored as decimal/numeric values, never floats.

## Core Tables

### users

Stores authenticated users and their role.

Important fields:

- `name`
- `email`
- `password`
- `role`
- `email_verified_at`

The `role` column is cast to `UserRoleEnum` in the `User` model.

### currencies

Stores global and user-owned currency references.

Important fields:

- `user_id` nullable
- `code`
- `name`
- `symbol` nullable
- `is_default`
- `enabled`

When `user_id` is `null`, the currency is a global reference.

### categories

Stores global and user-owned transaction categories.

Important fields:

- `user_id` nullable
- `type`
- `name`
- `is_default`
- `enabled`

The `type` field uses income and expense values through `TransactionTypeEnum`.

### cashboxes

Stores global and user-owned cashboxes.

Important fields:

- `user_id` nullable
- `currency_id`
- `name`
- `is_default`
- `enabled`

Each cashbox belongs to a currency.

### transactions

Stores income and expense transactions.

Important fields:

- `user_id`
- `cashbox_id`
- `currency_id`
- `category_id`
- `type`
- `amount`
- `transaction_date`
- `comment` nullable

Transactions always belong to a user. The selected category type must match the transaction type.

## Global Reference Rule

Reference tables use nullable `user_id`:

- `user_id = null` means global reference.
- `user_id = authenticated user id` means user-owned reference.
- Normal users can read global and own references.
- Normal users cannot update or delete global references.
- SuperAdmin can manage global references.

Transactions do not use nullable ownership. They always have a required `user_id`.

## Reporting Notes

Reports aggregate transaction data by:

- month
- category
- currency
- cashbox

There is no exchange-rate conversion yet. Totals are calculated within the selected filtered dataset, so mixed-currency totals should be interpreted as simple raw sums until conversion is implemented.
