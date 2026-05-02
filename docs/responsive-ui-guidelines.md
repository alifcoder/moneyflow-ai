# Responsive UI Guidelines

MoneyFlow AI uses mobile-first Inertia/Vue pages. Every authenticated workflow should be usable on mobile, tablet, and laptop screens.

## Layout Rules

- Start with a single-column mobile layout.
- Use `sm:`, `md:`, `lg:`, and `xl:` only when a wider layout improves scanning.
- Keep page padding modest on mobile.
- Avoid fixed widths that can cause horizontal scrolling.
- Use `min-w-0` and `overflow-hidden` or local `overflow-x-auto` where content may expand.

## Forms And Filters

- Filters stack vertically on mobile.
- Filters may become inline grids on tablet and laptop screens.
- Form action buttons should be full-width on mobile and natural width on larger screens.
- Validation errors should appear near the affected field.

## Lists And Tables

- Use card-style lists on mobile for references and transactions.
- Use data tables on tablet and laptop screens when columns improve comparison.
- If a table needs a minimum width, place horizontal scrolling on the table container, not the whole page.
- Keep actions large enough for touch screens.

## Charts

- Charts must use responsive containers.
- Canvas elements should be wrapped in a constrained `relative` container with a defined height.
- Use compact axis labels on mobile.
- Avoid legends that force the canvas wider than the viewport.
- For pie charts, prefer a compact external legend when labels may be long.

## Visual Review Checklist

Before considering a UI task complete, check:

- Mobile cards do not clip text or actions.
- Tablet tables fit or scroll inside their panel.
- Laptop layouts use space efficiently without becoming dense.
- Filters remain readable.
- Charts resize cleanly.
- Buttons are usable on touch screens.
- Empty states are visible and understandable.
