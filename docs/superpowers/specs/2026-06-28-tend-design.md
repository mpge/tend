# Tend — Design Spec

**Date:** 2026-06-28
**Repo:** `mpge/taskmanager` (public)
**Status:** Approved, in build

## Summary

Tend is a personal task and habit manager. It keeps a long task list manageable
by sorting work into two intent-based lanes, tracks recurring habits with
streaks, and surfaces lightweight insights that nudge the user toward what
matters today. Single primary user, but built multi-user so it can be
self-hosted.

Deployed at `todo.matthewpg.com`.

## Goals

- Tame a large task list by separating **Important** (act now) from **Eventual**
  (later) so the "do now" set stays small.
- Build consistency through a **habit tracker** with streaks and a calendar grid.
- Stay on track with a small, deterministic **insights** engine that points at
  overdue work, an overloaded Important lane, at-risk streaks, and a daily focus.

## Non-Goals (v1)

- No teams, sharing, or collaboration.
- No native mobile apps (responsive web only).
- No AI/LLM features. The insights engine is rule-based and deterministic. An AI
  layer is a clean later add-on, not part of v1.
- No notifications/email reminders in v1 (insights are shown in-app).

## Stack

- **Laravel 13** (PHP 8.4+) + **Inertia 2** + **Vue 3 (TypeScript)** + **Tailwind 4** + Vite
- Auth via **Laravel Fortify** (from the official Vue starter kit): login,
  registration, password reset, 2FA, passkeys
- **Pest 4** for tests (in-memory SQLite); **Pint** + **PHPStan/Larastan** for static analysis
- **Postgres 18** in Docker for local dev/prod; the app and Vite run on the host
- CI via GitHub Actions (Pest + Pint + PHPStan + asset build)

## Data Model

### `tasks`
| column | type | notes |
| --- | --- | --- |
| id | bigint pk | |
| user_id | fk → users | owner |
| title | string | required |
| notes | text nullable | |
| bucket | enum (`important`,`eventual`) | default `important` |
| status | enum (`open`,`done`) | default `open` |
| priority | unsigned tinyint | 0 = normal, higher = more urgent (within lane) |
| due_date | date nullable | drives overdue insight |
| position | integer | manual ordering within a lane |
| completed_at | timestamp nullable | |
| timestamps | | |

### `habits`
| column | type | notes |
| --- | --- | --- |
| id | bigint pk | |
| user_id | fk → users | owner |
| name | string | e.g. "Exercise", "Read", "Drink water" |
| cadence | enum (`daily`,`weekly`) | default `daily` |
| target_per_period | unsigned smallint | times per period, default 1 |
| color | string | hex/token for the UI |
| icon | string nullable | icon key |
| is_active | boolean | default true |
| position | integer | ordering |
| timestamps | | |

### `habit_entries`
| column | type | notes |
| --- | --- | --- |
| id | bigint pk | |
| habit_id | fk → habits | |
| entry_date | date | the day this completion counts for |
| created_at | timestamp | |

Unique index on `(habit_id, entry_date)`. A row's presence means "done that day".
Streaks and grid are computed from these rows.

## Components

- **TaskController** — index (lanes), store, update (edit/move bucket/toggle
  status), reorder, destroy. Authorize by owner.
- **HabitController** — index, store, update, destroy, reorder.
- **HabitEntryController** — toggle a habit for a given date (create/delete the
  entry).
- **InsightService** — pure service that takes a user's tasks + habits and
  returns an ordered list of insight DTOs. Rules:
  1. **Overdue** — tasks with `due_date < today` and `status = open`.
  2. **Overloaded Important** — if open Important tasks > threshold (e.g. 7),
     suggest moving the lowest-priority ones to Eventual.
  3. **Focus 3** — pick today's top 3 (Important, by priority then due date).
  4. **Streak at risk** — active daily habit with a current streak ≥ 2 not yet
     done today.
  5. **Weekly review** — prompt to review the Eventual lane once a week.
  Each insight has a type, message, and optional linked entity.
- **StreakCalculator** — pure helper computing current/longest streak and the
  grid (last N weeks) from `habit_entries`.

Controllers stay thin; InsightService and StreakCalculator are framework-light
and unit-tested in isolation.

## Pages (Inertia + Vue)

- **Today** (`/`, dashboard) — focus tasks, today's habits with one-tap toggle,
  insights panel.
- **Tasks** (`/tasks`) — Important / Eventual lanes side by side, quick-add,
  inline edit, drag to reorder, move between lanes, complete.
- **Habits** (`/habits`) — habit list with a GitHub-contributions-style streak
  grid, current/longest streak, one-tap check-off, add/edit habit.
- Auth + settings pages come from the starter kit.

## UI Direction

Splitwise-inspired: clean white cards on a soft background, generous whitespace,
rounded corners, one warm accent color, friendly sans-serif, mobile-first. A
single distinctive accent (warm coral/amber) instead of generic SaaS blue. The
habit grid is the signature visual element. Built with the frontend-design skill.

## Testing Strategy

- TDD with Pest. Feature tests for each controller action (auth + ownership +
  happy/edge paths). Unit tests for InsightService rules and StreakCalculator
  math. Tests run on in-memory SQLite.

## Local Dev

1. `docker compose up -d` — start Postgres.
2. `composer install && npm install`
3. `php artisan migrate`
4. `composer dev` — runs server + queue + Vite.

## Out of Scope / Later

AI-assisted suggestions, email/push reminders, recurring tasks, sub-tasks, tags,
calendar sync, shared/household lists.
