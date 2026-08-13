# Repository Guidelines

## Project Structure & Module Organization

This repository is a Laravel 13 application for running-event registration. Domain code lives in `app/`: controllers and form requests handle HTTP input, models represent event and registration data, policies enforce access, services contain payment and registration workflows, and jobs perform queued work. Routes are defined in `routes/web.php`. Blade templates and frontend sources live under `resources/views`, `resources/css`, and `resources/js`; Vite emits compiled assets to `public/build`. Database migrations, factories, and seeders are in `database/`. Keep unit tests in `tests/Unit` and end-to-end application behavior in `tests/Feature`. Do not edit generated files in `vendor/`, `storage/framework/`, or `public/build/`.

## Build, Test, and Development Commands

- `composer run setup` installs PHP/Node dependencies, creates `.env`, generates the app key, migrates, and builds assets.
- `composer run dev` starts the Laravel server, queue listener, log viewer, and Vite watcher together.
- `npm run build` creates production frontend assets.
- `composer test` clears cached configuration and runs the PHPUnit suite.
- `php artisan migrate --seed` rebuilds schema changes and loads demo data.
- `php artisan storage:link` exposes public uploads; run it once per environment.

## Coding Style & Naming Conventions

Follow PSR-12 and Laravel conventions. Use four-space indentation (two spaces for YAML), LF endings, and UTF-8 as configured in `.editorconfig`. Format PHP with `vendor/bin/pint`. Name classes in PascalCase, methods and variables in camelCase, database columns in snake_case, and Blade files by feature (for example, `resources/views/registrations/create.blade.php`). Keep controllers thin and place transactional business logic in `app/Services`.

## Testing Guidelines

Tests use PHPUnit 12 with an in-memory SQLite database, synchronous queues, and array-backed mail/session stores. Name test classes `*Test.php` and write behavior-focused methods such as `test_registration_fails_when_quota_is_full`. Add feature coverage for routes, authorization, database state, pricing, quota locking, and payment status transitions. Run `composer test` before submitting changes. If Laragon has SQLite disabled, use the extension-loading command documented in `README.md`.

## Commit & Pull Request Guidelines

Git history is not included in this checkout. Use short, imperative commit subjects, optionally with a conventional prefix, such as `feat: add event duplication` or `fix: prevent quota overbooking`. Keep each commit focused. Pull requests should explain the user-visible change, note migrations or configuration updates, link related issues, report test results, and include screenshots for Blade/UI changes.

## Security & Configuration

Never commit `.env`, credentials, demo passwords changed for production, or uploaded payment proofs. Add new settings to `.env.example` with safe placeholders. Validate uploads through form requests, enforce access through policies, and keep payment-provider details behind the `PaymentService` contract.
