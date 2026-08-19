# IT Coffee Shop — Staff Attendance & Leave Management

A Laravel API + React SPA for staff to clock in/out, request leave, and for
admins to review leave requests and manage users. Supports login via
email/password or the Telegram Login Widget.

## Stack

- **Backend:** Laravel 13, JWT auth (`php-open-source-saver/jwt-auth`), MySQL or SQLite.
- **Frontend:** React + Vite, in `frontend/`.

## Backend setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret        # required — auth throws SecretMissingException without this
php artisan migrate
php artisan serve
```

Telegram login requires `TELEGRAM_BOT_TOKEN` (from @BotFather) and
`TELEGRAM_BOT_USERNAME` set in `.env`; without them, `/api/auth/telegram-auth`
returns a 500.

## Frontend setup

```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

Set `VITE_API_BASE_URL` to point at wherever `php artisan serve` is running.
`VITE_TELEGRAM_BOT_USERNAME` must match the backend's `TELEGRAM_BOT_USERNAME`
or the Telegram login button won't render.

## Testing

```bash
php artisan test
cd frontend && npm run lint && npm run build
```

## Roles

Two roles exist: `admin` and `employee`. Admin-only endpoints live under
`/api/admin/*` and are enforced by the `role:admin` middleware.
