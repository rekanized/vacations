<p align="center">
  <img src="public/brand/leaveboard-mark.svg" alt="LeaveBoard logo" width="88" height="88">
</p>

<h1 align="center">LeaveBoard</h1>

<p align="center">
  A polished Laravel-based leave planning workspace for visualising team availability,
  submitting absence requests, managing approvals across departments and sites,
  and personalising public-holiday calendars per user.
</p>

## Overview

LeaveBoard is a lightweight internal planning tool built for teams that need a fast visual overview of who is away, when they are away, and which requests still need approval.

The application combines a timeline-style planner with a small admin workspace for:

- choosing the active user via impersonation,
- maintaining absence types,
- updating the application name,
- supporting manager-based approval workflows,
- letting each user manage their own holiday-country profile.

## Core functionality

### Planner experience

- Interactive multi-month planner covering the current month plus the following two months.
- Drag-to-select day ranges directly on the planner grid.
- Month and year navigation with quick return to today.
- Sticky headers and a floating visible-month indicator while scrolling.
- Department sections with expandable user rows.
- Department-level day counts to quickly see staffing impact.
- Current-user spotlight and quick jump back to your own row.
- Holiday markers that follow the current user's selected holiday country.

### Filtering and visibility

- Multi-select department filtering.
- Multi-select site filtering.
- Live personnel search by user name.
- Department results automatically collapse to only teams with visible users.

### Absence management

- Absence types are database-driven instead of hardcoded.
- Default seeded absence types:
  - `S` — Vacation
  - `FL` — Parental
- Each absence type has its own label, color, and display order.
- Optional free-text reason can be attached to a request.
- Existing absences can be removed directly from a selected range.

### Approval workflow

- Users with a manager submit absences as pending requests.
- Users without a manager are auto-approved immediately.
- Pending requests are grouped by a shared request UUID.
- Request owners can:
  - review their pending requests,
  - edit date range,
  - change absence type,
  - update the reason,
  - delete the request before approval.
- Managers can review requests submitted by their direct reports and approve or reject them.
- Approval metadata is tracked with status, approver, and approval timestamp.

### User profile

- Each active session user has a dedicated `/profile` page.
- Users can configure which country's public holidays should be used in the planner.
- The profile page shows:
  - the user's department,
  - the user's site,
  - the user's manager,
  - request totals for approved, rejected, pending, and total requests,
  - a current-month planner snapshot,
  - recent request history with approval or rejection details.

### Administration

The admin area is intentionally simple and currently acts as a proof-of-concept control panel.

It supports:

- session-based user impersonation,
- application name updates stored in the database,
- creation of new absence options,
- overview of configured absence types,
- overview of users and their managers.

### Holiday support

- Public holidays are generated dynamically through a dedicated calendar helper.
- Supported holiday calendars currently include Sweden, Denmark, Norway, Finland, Germany, the United Kingdom, and the United States.
- Both fixed and moveable holidays are supported.
- Holiday data is user-specific through `users.holiday_country`.
- Stored holiday rows are country-aware through `holidays.country_code` and can still override or supplement generated values.
- Seed data covers a rolling range from five years back to five years forward from the current year for every supported country.

## Data model highlights

Recent updates introduced a more complete leave workflow model:

- `users.manager_id` links users to a manager.
- `users.holiday_country` stores the selected public-holiday calendar for each user.
- `absences.status` stores `pending`, `approved`, or `rejected`.
- `absences.request_uuid` groups multiple dates into one request.
- `absences.approved_by` and `absences.approved_at` track approvals.
- `absence_options` stores configurable leave types.
- `holidays.country_code` scopes stored holiday overrides to a specific country.
- `settings` stores application-level configuration such as the product name.

## Tech stack

- PHP `^8.3`
- Laravel `^13.0`
- Livewire `^4.0@dev`
- Alpine-style client interactions in Blade views
- Vanilla CSS
- Material Symbols Outlined icons
- Vite-based frontend asset pipeline
- PHPUnit 12 for automated testing

## Local development

### Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite, MySQL, or another Laravel-supported database

### Installation

1. Clone the repository.
2. Install PHP dependencies.
3. Create the environment file.
4. Generate the application key.
5. Configure the database.
6. Run migrations and seeders.
7. Build frontend assets.

Example setup:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm install
npm run build
```

### Running the application

For local development:

```bash
composer run dev
```

For tests:

```bash
composer test
```

## Seeded demo behavior

The seeded environment provides:

- multiple departments,
- multiple Swedish office locations,
- generated personnel,
- manager assignments within each department,
- seeded absence options,
- seeded holidays for all supported countries,
- sample absence history.

On first visit, the application automatically stores the first available user in session as the active user.

## Project structure

- `app/Livewire/VacationPlanner.php` — main planner logic and request workflow.
- `app/Http/Controllers/AdminController.php` — admin actions.
- `app/Http/Controllers/ProfileController.php` — current-user profile and holiday-country updates.
- `app/Http/Middleware/EnsureCurrentUser.php` — ensures an active session user exists.
- `app/Models/Absence.php` — absence entity and approval fields.
- `app/Models/AbsenceOption.php` — configurable absence types.
- `app/Models/Holiday.php` — stored holiday overrides, now scoped by country.
- `app/Models/Setting.php` — simple key/value settings.
- `app/Support/HolidayCalendar.php` — country-aware holiday resolution.
- `app/Support/SwedishHolidayCalendar.php` — Swedish holiday generation used by the shared resolver.
- `resources/views/livewire/vacation-planner.blade.php` — planner UI.
- `resources/views/admin/index.blade.php` — admin workspace.
- `resources/views/profile/show.blade.php` — profile workspace.
- `resources/views/components/layouts/app.blade.php` — branded shared layout.

## Test coverage

The repository includes coverage for:

- session bootstrapping of the current user,
- approval flow behavior,
- pending request editing and deletion,
- multi-select filtering,
- user-specific holiday generation,
- profile page rendering and profile updates,
- planner navigation across year boundaries,
- admin application name updates.

## Notes

- The application branding defaults to **LeaveBoard** and can be changed from the admin area.
- The sidebar logo is stored in `public/brand/leaveboard-mark.svg`.
- The admin area is currently open by design for internal validation and prototyping.

## License

This project is open-sourced under the [MIT license](LICENSE).
