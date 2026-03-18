<p align="center">
  <img src="public/brand/leaveboard-mark.svg" alt="LeaveBoard logo" width="88" height="88">
</p>

<h1 align="center">LeaveBoard</h1>

<p align="center">
  A Laravel-based leave planning app for visualising team availability, submitting absence requests,
  and handling manager approvals across departments and sites.
</p>

## Support

If you use the app and want to support the project:
https://buymeacoffee.com/rekanized

## What It Does

LeaveBoard gives teams a shared planning surface for time off and availability.

- Visual multi-month planner for team absences
- Configurable absence options with labels, codes, and colors
- Manager approval flow for submitted requests
- User profile page with holiday-country and theme preferences
- Lightweight admin area for impersonation, settings, and absence-option management
- Country-aware public holiday support

## Highlights

- Planner navigation across the current month and the following months
- Department, site, and personnel filtering
- Pending request editing before approval
- Approval and rejection tracking with manager decision reasons
- Seeded demo data for departments, personnel, holidays, and absence options

## Tech Stack

- PHP 8.3+
- Laravel 13
- Livewire 4
- Blade, Livewire interactions, and vanilla CSS
- Handcrafted styles served from `public/app.css`
- PHPUnit 12

## Quick Start

LeaveBoard does not use Node.js, npm, Vite, Tailwind, or Bootstrap. Local setup is PHP-, Composer-, and vanilla-CSS-only.

### Requirements

- PHP 8.3 or newer
- Composer
- SQLite

### Local Setup

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
```

The default `.env.example` uses SQLite, so the commands above are enough for a working local install.

Start the local server:

```bash
php artisan serve
```

Then open `http://127.0.0.1:8000`.

### What You Get After Seeding

- Departments, users, manager relationships, absence options, holidays, and sample absence history
- Automatic session fallback to the first available user on first visit
- A working planner at `/`
- A current-user profile page at `/profile`
- An internal admin workspace at `/admin`

### Useful Commands

Refresh the database with seed data:

```bash
php artisan migrate:fresh --seed
```

Run the test suite:

```bash
composer test
```

Clear cached framework state after changing environment or route/config files:

```bash
php artisan optimize:clear
```

## Docker

This repository includes a Dockerfile for a simple local container run without any Node tooling.

Build the image:

```bash
docker build -t leaveboard .
```

Run the container:

```bash
docker run --rm -p 8000:8000 leaveboard
```

Then open `http://127.0.0.1:8000`.

Notes:

- The container seeds a SQLite database during image build.
- Rebuild the image after code or database-seed changes.

## Seeded Demo Setup

The default seeders provide departments, users, manager relationships, absence options, holidays, and sample absence history. On first visit, the app stores the first available user in session as the active user.

## Project Notes

- The default product name is LeaveBoard and can be changed from the admin area.
- The admin area is intentionally simple and currently aimed at internal validation and prototyping.
- AI-agent-specific project context lives in `GEMINI.md`, `AGENTS.md`, and `.agent/context/`.

## License

This project is open-sourced under the [MIT license](LICENSE).
