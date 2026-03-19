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
- Landing page with Microsoft and manual sign-in entry points
- Split admin workspace for authentication, user information, request logs, and application settings
- Country-aware public holiday support

## Highlights

- Planner navigation across the current month and the following months
- Department, site, and personnel filtering
- Pending request editing before approval
- Approval and rejection tracking with manager decision reasons
- Azure tenant sign-in with optional manual email/password accounts
- Dedicated manual sign-in page, even before manual accounts exist
- Expandable admin navigation with focused subpages instead of one overloaded admin screen
- Seeded absence options and holiday data for a clean first install

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

## Authentication

LeaveBoard supports two startup paths for authentication.

Users arrive on a landing page at `/` with direct entry points for:

- Microsoft sign-in
- Manual sign-in
- First-run setup when no users exist yet

### Recommended: Azure Setup

This is the preferred production-style setup.

1. Start the application and open the first-run setup page.
2. Enter the Azure Tenant ID, Client ID, and Client Secret from your Azure App Registration.
3. Save the configuration so LeaveBoard can verify the Microsoft OpenID endpoints.
4. Return to the landing page and sign in with Microsoft.
5. The first successful sign-in becomes the first admin user.

Once Azure is configured:

- Microsoft sign-in is available from the landing page
- The manual sign-in page remains available from the landing page as a separate path
- The first admin can grant admin access to other users
- New Azure users are created automatically on first sign-in
- Logout is available so another user or tenant-backed account can sign in

### Manual-Only Startup

If you do not want to configure Azure immediately, you can bootstrap the system with a manual admin account instead.

1. Open the first-run setup page.
2. Use the manual admin form to enter name, lastname, email, and password.
3. Submit the form to create the first admin account.
4. Sign in from the landing page with email and password.

Once a manual admin exists:

- Admins can create additional manual users with email/password credentials
- Admins can grant admin access to other users
- Azure can still be configured later from the admin workspace
- The landing page keeps a dedicated Manual sign-in button that opens the manual login screen
- The same authenticated session flow is used for planner, profile, admin, and logout

### Manual Sign-In Experience

- The landing page always exposes a Manual sign-in button
- The manual sign-in screen is reachable even before manual accounts exist
- If no manual accounts are available yet, the page explains that an admin must create one first
- Once manual accounts exist, the same page accepts email/password sign-in

## Admin Workspace

The admin workspace is now split into smaller focused sections instead of combining every operational control on one screen.

### Admin Navigation

The sidebar contains an expandable Admin group with links for:

- Authentication
- User information
- Application settings
- Request log

### Authentication

- Azure tenant configuration and verification
- Redirect URI visibility
- Required Microsoft Graph permissions and consent guidance

### User Information

- Manual user creation
- Admin access delegation
- Activation and deactivation controls
- User list with department, manager, and access status

### Application Settings

- Application name updates
- Absence-option creation, editing, and deletion
- Request log entry count with quick access to the full log

### Choosing Between Them

- Choose Azure when you want tenant-backed identity and Microsoft sign-in as the main access path.
- Choose manual-only startup when you need to get running before Azure is available.
- Even in manual-only mode, Azure remains the recommended long-term setup and can be added later without rebuilding the app.

### What You Get After Seeding

- Absence options and holiday data
- A landing page at `/`
- A dedicated manual sign-in page at `/login/manual`
- A working planner at `/planner`
- A current-user profile page at `/profile`
- An internal admin workspace at `/admin`
- No users are created automatically; first access goes through the authentication setup flow

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

For Docker, the app now runs behind Nginx with PHP-FPM instead of `php artisan serve`.

Start the stack:

```bash
docker compose up --build
```

Then open `http://127.0.0.1:8000`.

Notes:

- `app` runs PHP-FPM and initializes the SQLite database on container startup.
- `nginx` serves the public app and forwards PHP requests to the `app` service.
- Docker uses `docker/nginx.conf`; the root `nginx.conf` remains reserved for Azure Web Apps.
- The default compose file is intended for local use and keeps the setup free of Node tooling.

## Seeded Demo Setup

The default seeders provide absence options and holiday data only. After seeding, create the first admin through the authentication setup flow on the landing page.

## Project Notes

- The default product name is LeaveBoard and can be changed from the Application settings admin page.
- The admin workspace is intentionally simple but now split into focused subpages to keep authentication, user management, and settings separate.
- AI-agent-specific project context lives in `GEMINI.md`, `AGENTS.md`, and `.agent/context/`.

## License

This project is open-sourced under the [MIT license](LICENSE).
