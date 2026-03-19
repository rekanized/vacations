# Project Context: LeaveBoard

## Overview
LeaveBoard is an internal leave-planning application for visualising team availability and processing absence requests. The product centers on a multi-month planner, a landing/authentication entry flow, a profile workspace for the active session user, and a split admin workspace for operational controls.

## User-Facing Areas

### Landing and Authentication
- Landing page at `/` with Microsoft sign-in and manual sign-in entry points
- First-run setup flow when no users exist yet
- Dedicated manual sign-in page at `/login/manual`
- Manual sign-in page remains reachable even when no manual accounts exist, and explains that an admin must create one first

### Planner
- Multi-month timeline covering the selected month and the following two months
- Drag/range selection for request creation
- Department grouping with expandable user lists
- Department, site, and personnel filtering
- Current-user row spotlight and jump-back behavior
- Holiday markers driven by the active user's holiday country

### Profile
- Dedicated `/profile` page for the active session user
- Holiday-country preference management
- Light/dark theme preference management
- Request summaries, request history, and current-month snapshot

### Admin
- Expandable sidebar admin navigation
- Dedicated `/admin/authentication` page for Azure configuration and verification
- Dedicated `/admin/users` page for manual-user creation, admin delegation, and user status management
- Dedicated `/admin/settings` page for application settings and absence-option management
- Dedicated `/admin/logs` page for request log browsing

## Workflow Rules
- Requests from users with a manager are submitted as pending.
- Requests from users without a manager are approved immediately.
- Multi-day requests are grouped by a shared request UUID.
- Pending requests can be edited or deleted by the request owner before approval.
- Managers can approve or reject requests from direct reports.
- Rejections require a manager decision reason.

## Holidays and Preferences
- Holiday resolution is country-aware.
- `users.holiday_country` controls planner holidays per user.
- `holidays.country_code` scopes stored holiday overrides.
- `users.theme_preference` persists light/dark mode across pages.

## Authentication and Access
- Azure tenant sign-in is the recommended authentication path.
- Manual email/password accounts are supported as a fallback or bootstrap path.
- The first successful Azure sign-in becomes the first admin when no users exist yet.
- Manual admins can be created during setup when Azure is not ready.
- Admins can later create additional manual users from the dedicated user-information admin page.
- Inactive users cannot sign in or access planner/admin pages.

## Seeded Environment
- Seeders provide absence options and holidays for a clean first install.
- Users are created through authentication setup rather than seeded into the database.
