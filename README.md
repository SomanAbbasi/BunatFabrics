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
