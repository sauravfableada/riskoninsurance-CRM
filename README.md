# Fablead Insurance CRM

Fablead Insurance CRM is a Laravel 10 application for managing insurance, sales, customer relationships, staff work, documents, and communication workflows. The project combines a web dashboard with authenticated API endpoints for CRM modules, policy operations, investments, marketing, reporting, and master-data administration.

## Features

- Dashboard with CRM statistics, lead board widgets, task widgets, trends, customer reports, and deals widgets.
- Lead, deal, pipeline, follow-up, meeting, task, project, customer, product, service, invoice, and support ticket management.
- Insurance workflows for policies, life insurance, renewals, KYC, endorsements, commissions, policy documents, and policy reports.
- Investment workflows for investment records, holders, folios, fund types, sub-funds, invested parties, payment methods, post offices, areas, and business/client-status masters.
- Travel CRM modules including packages, quotations, bookings, itineraries, passengers, supplier costs, refunds, vouchers, rooming lists, and driver sheets.
- Role and permission controls using Spatie Laravel Permission plus custom matrix permissions.
- Staff/team member management, target reviews, user logs, notifications, and push subscriptions.
- Marketing tools for email campaigns/templates, SMS marketing, WhatsApp configuration/templates/logs, and a CRM assistant endpoint.
- Reporting and exports for leads, deals, follow-ups, projects, tasks, customers, policies, staff, sales, pending accounts, and profit/loss.
- REST-style API routes protected by Laravel Sanctum or custom token middleware, with an API login page at `public/api-login.html`.

## Tech Stack

- PHP `^8.1`
- Laravel `^10.10`
- Laravel Sanctum
- Spatie Laravel Permission
- Yajra DataTables
- DOMPDF and PDF parser packages
- Google API Client for Calendar integration
- Twilio SDK for SMS
- Bootstrap 5, Sass, Vite, and Axios
- MySQL/MariaDB or another Laravel-supported database

## Project Structure

```text
app/                 Application controllers, models, policies, services, middleware, traits
config/              Laravel and CRM configuration
database/migrations  Database schema for CRM, insurance, investment, marketing, and master data
database/seeders     Seeders for roles, permissions, settings, master data, demo data, and users
public/              Public assets, compiled/static CSS/JS, uploads, logos, API login page
resources/views      Blade views for CRM screens, masters, insurance, marketing, reports, auth
resources/js         Vite JavaScript entry point
resources/sass       Vite Sass entry point
routes/web.php       Web dashboard and module routes
routes/api.php       Public, Sanctum, and token-authenticated API routes
tests/               PHPUnit feature and unit test stubs
```

## Requirements

- PHP 8.1 or newer
- Composer
- Node.js and npm
- MySQL/MariaDB
- A web server such as Apache/Nginx, or Laravel's local development server

For Windows/XAMPP, place the project under your web root and make sure PHP, Composer, and Node are available in your terminal.

## Installation

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Install frontend dependencies:

   ```bash
   npm install
   ```

3. Create or copy your environment file:

   ```bash
   cp .env.example .env
   ```

   This repository currently may not include `.env.example`. If it is missing, create `.env` from your deployment/local environment values and include the standard Laravel keys such as `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`, database credentials, session/cache settings, mail settings, and integration credentials.

4. Generate the application key:

   ```bash
   php artisan key:generate
   ```

5. Configure the database in `.env`, then run migrations:

   ```bash
   php artisan migrate
   ```

6. Seed initial data as needed:

   ```bash
   php artisan db:seed --class=RolePermissionSeeder
   php artisan db:seed --class=MasterDataSeeder
   php artisan db:seed --class=SettingsSeeder
   php artisan db:seed --class=RoleSeeder
   ```

   Note: `DatabaseSeeder` currently references `UserSeeder` and `AllModulesSeeder`, but those files are not present in `database/seeders`. Use the available seeders directly or add the missing seeders before running `php artisan db:seed`.

7. Link storage if uploaded files should be publicly accessible:

   ```bash
   php artisan storage:link
   ```

8. Start the backend:

   ```bash
   php artisan serve
   ```

9. In another terminal, start Vite for local asset development:

   ```bash
   npm run dev
   ```

The web login route is `/`, and the dashboard route is `/dashboard` after authentication.

## Frontend Assets

Development:

```bash
npm run dev
```

Production build:

```bash
npm run build
```

Vite compiles:

- `resources/sass/app.scss`
- `resources/js/app.js`

The project also includes module-specific assets under `public/css`, `public/js`, and `public/assets`.

## API Usage

The API login endpoint is:

```text
POST /api/v1/login
```

Example body:

```json
{
  "email": "admin@crm.com",
  "password": "admin@123",
  "device_name": "web-app"
}
```

Use the returned token with:

```text
Authorization: Bearer <token>
```

Useful Sanctum-protected `/api/v1` endpoints include:

- `GET /api/v1/me`
- `POST /api/v1/logout`
- `GET /api/v1/me/permissions`
- `GET /api/v1/dashboard`

The larger resource API is mounted under `/api/*` with the custom `auth.api` middleware. It includes endpoints for leads, deals, follow-ups, projects, meetings, tasks, invoices, customers, services, tickets, pipelines, products, documents, users, masters, policies, prospects, team members, target reviews, and investments.

There is also a browser-based API login helper at:

```text
/api-login.html
```

See `API_LOGIN_GUIDE.txt` for a longer token-login walkthrough.

## Integrations

The codebase includes integration hooks for:

- Google Calendar OAuth and meeting sync via `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, and `GOOGLE_CALENDAR_AUTO_SYNC`.
- WhatsApp webhook/configuration using `WHATSAPP_VERIFY_TOKEN` and settings stored in the application.
- SMS/Twilio configuration through settings and Twilio SDK usage.
- SMTP/mail settings through the settings module.
- Stripe/Razorpay-style payment setting keys in the settings seeders.

Add real credentials only in `.env` or application settings. Do not commit secrets.

## Testing

Run the PHPUnit suite with:

```bash
php artisan test
```

The current `tests/` directory contains the default Laravel feature and unit test stubs, so add module-specific tests when changing business logic.

## Useful Artisan Commands

```bash
php artisan route:list
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan storage:link
```

## Notes for Contributors

- Web routes live mainly in `routes/web.php`; API routes live in `routes/api.php`.
- Many web screens use Blade views plus module-specific JavaScript in `public/js`.
- Permissions are split between Spatie roles/permissions and CRM matrix permissions in `config/crm_permissions.php`.
- Auth uses session login for the web app and token-based access for API/frontend compatibility.
- Keep generated uploads, local logs, and environment files out of version control.

## License 

This project is proprietary software developed by **Fablead Developers Technolab**. All rights reserved.
