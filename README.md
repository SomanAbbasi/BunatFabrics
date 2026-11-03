# BunatFabrics

B2B fabric wholesale ordering website — a small PHP 8 MVC app with customer
signup / login and an admin dashboard.

## Requirements

- PHP 8.1+ with the `pdo_mysql` extension
- MySQL 8
- Apache with `mod_rewrite`, document root pointed at `public/`

## Setup

1. Create your environment file and set the database credentials:

   ```bash
   cp .env.example .env
   ```

2. Create the database table and seed the predefined data:

   ```bash
   php database/migrate.php
   ```

3. Configure your web server so the document root is the `public/` folder,
   then open the site in a browser.

### Local development without Apache

The built-in PHP server works on Chrome/any browser at `http://localhost:8080`:

```bash
php -S localhost:8080 -t public server.php
```

`server.php` serves files from `public/` and sends clean URLs to the front
controller. Set `APP_URL=http://localhost:8080` in `.env` so redirects use the
same address. Apply catalog tables with:

```bash
php database/catalog-migrate.php
```

## Login

The administrator account is defined in `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`).
`database/migrate.php` syncs it into the database, storing the password as a
bcrypt hash. New customers can register from the signup page.

## Project structure

```
config/        application and database configuration
database/      schema.sql, seed.sql and the migrate.php runner
public/        front controller, .htaccess and assets (web root)
routes/        route definitions
src/Core/      framework pieces (Env, Database, Session, Router, ...)
src/Auth/      authentication service, guard and controllers
src/Home/      public landing page controller
views/         page templates and layout
```

## Security notes

- Passwords are stored as bcrypt hashes (`password_hash`).
- All queries use prepared statements.
- Forms are protected with CSRF tokens.
- The real `.env` file is git-ignored and must never be committed.

## Catalog migrations (existing installations)

Use the additive catalog runner, **not** the legacy `database/migrate.php`, which
re-seeds accounts and synchronizes administrator passwords:

```bash
php database/catalog-migrate.php
```

It targets `DB_NAME` from the existing configuration, requires `APP_ENV` to be
`local`, `development`, or `testing`, and creates `categories`, `products`, and
`schema_migrations`. It does not create/drop databases or alter users. A database
lock prevents concurrent runners; version checksums detect edits to applied SQL.
Run it again safely to apply only pending versions. Keep applied migration files
unchanged and add a new numbered SQL file for later changes. MySQL DDL is not
transactional: the initial CREATE TABLE migrations use `IF NOT EXISTS` so an
interrupted run can resume. This is not a schema-drift repair tool; investigate
unexpected pre-existing catalog tables before running it.

The catalog follows PRD v1.0's composition, `width_inch` (DECIMAL(5,1)), GSM, and
meter/yard unit fields. Optional specifications may be left blank. Prices, MOQ,
stock, variants, images, and price tiers are intentionally deferred. Future
variant/tier tables can reference the stable product ID; use DECIMAL values for
money and quantities. Categories are hidden instead of deleted, and the foreign
key restricts deletion of a category in use. Only published products in visible
categories appear publicly. Lists use 20 admin rows or 12 public cards per page.

## Catalog verification

PHP needs `pdo_mysql` and `mbstring`. See [tests/README.md](tests/README.md) for
isolated MySQL, HTTP regression, and manual browser checks. No test command seeds
or resets the configured application's users.
