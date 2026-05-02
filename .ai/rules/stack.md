## Required Stack

- Laravel 13
- PHP 8.5
- PostgreSQL
- Inertia.js 3
- Vue 3
- Vite
- Tailwind CSS

## Version Rules

- Use Laravel 13 conventions.
- Use PHP 8.5 syntax only when it improves clarity.
- Do not use deprecated PHP features.
- Use typed properties, return types, enums, readonly classes/DTOs where useful.
- Use Inertia.js 3 patterns.
- Do not use old Inertia v1/v2 examples blindly.
- Prefer Laravel 13 official documentation when unsure.

## UI Responsiveness Rules

Every page must support:

- mobile
- tablet
- laptop/desktop

Frontend rules:

- Mobile-first layout.
- Tables must not break on small screens.
- For mobile, convert complex tables into card/list layout.
- Filters must stack vertically on mobile and inline on desktop.
- Forms must be single-column on mobile and two-column on larger screens.
- Buttons must be full-width on mobile when used in forms.
- Charts must be responsive and readable on small screens.
- Avoid fixed widths.
- Use Tailwind responsive classes:
    - sm:
    - md:
    - lg:
    - xl:
- Every CRUD page must be tested visually in mobile, tablet, and desktop sizes.