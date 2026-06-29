# Tend

A personal task and habit manager that keeps a long to-do list from getting
overwhelming. Tasks sort into two intent-based lanes, habits build streaks, and a
lightweight insights panel nudges you toward what matters today.

Built with Laravel + Inertia + Vue, with a clean, Splitwise-inspired interface.

## Features

- **Two-lane tasks** — separate **Important** (act now) from **Eventual** (later)
  so the "do now" set stays small and honest.
- **Habit tracker** — daily and weekly habits with one-tap check-off, current and
  longest streaks, and a contribution-style calendar grid.
- **Insights** — a small, deterministic engine that flags overdue work, an
  overloaded Important lane, at-risk streaks, and a suggested focus for the day.
- **Self-hostable** — multi-user auth out of the box (login, registration,
  password reset, 2FA, passkeys).

## Tech stack

- Laravel 13 (PHP 8.4+)
- Inertia 2 + Vue 3 (TypeScript)
- Tailwind CSS 4 + Vite
- PostgreSQL (Docker for local dev)
- Pest 4, Pint, PHPStan/Larastan

## Local development

Requirements: PHP 8.4+, Composer, Node 20+, Docker.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Start Postgres (Docker)
docker compose up -d

# 3. Set up the app
cp .env.example .env
php artisan key:generate
php artisan migrate

# 4. Run the app (server + queue + Vite)
composer dev
```

The app runs at http://localhost:8000. Postgres is published on host port
`55435` (configurable via `FORWARD_DB_PORT`). The app and Vite run on the host;
only the database lives in Docker.

### Tests

```bash
php artisan test          # Pest, in-memory SQLite
./vendor/bin/pint --test  # code style
./vendor/bin/phpstan analyse --memory-limit=1G
```

## License

MIT
