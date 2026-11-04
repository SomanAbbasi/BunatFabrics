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

2. Create the database table and initialize an administrator if none exists:

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
`database/migrate.php` creates it if absent, storing the password as a
bcrypt hash. New customers can register from the signup page.

## Shopping cart and checkout

Customers can add a fabric and quantity to a cart, update quantities, remove
lines, and see line totals plus the order subtotal. The cart is stored in the PHP
session, so guests can build a cart before signing in: the cart survives the
login and signup redirects and is not lost when the session id is regenerated.

Checkout requires an account. A guest who opens `/checkout` is sent to the login
page with the cart intact and then returned to checkout after signing in. Before
placing the order the customer must enter a validated phone number, delivery
address, city, and payment method. Placing the order writes `orders` and `order_items` rows (including the
contact details) and clears the cart. Existing installations must apply the
orders migrations once:

```bash
php database/catalog-migrate.php
```

## Admin orders API

The admin dashboard can read placed orders from these JSON endpoints. They use
the normal administrator session, so `fetch()` calls work after signing in as an
admin. Unauthenticated requests receive `401`; non-admin users receive `403`.
The controller lives in `src/Admin/Orders/OrderApiController.php` and the routes
are registered in `routes/web.php`.

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/admin/orders` | Paginated order list, newest first |
| `GET` | `/api/admin/orders/show?id=1` | One order with its line items |

`/api/admin/orders` accepts `status` (`pending`, `confirmed`, `processing`,
`dispatched`, `delivered`, `rejected`, `cancelled`),
`page` (default `1`) and `per_page` (default `20`, maximum `100`). The response
includes `data` and `meta` (`page`, `per_page`, `total`, `total_pages`, `status`).
Each order exposes `id`, `user_id`, `status`, `subtotal_pkr`, `contact_phone`,
`shipping_address`, `created_at`, `updated_at`, `customer_name`,
`customer_email`, `item_lines` and `total_quantity`.

The detail response adds an `items` array with `product_id`, `product_code`,
`title`, `unit`, `unit_price_pkr`, `quantity` and `line_total_pkr` for each line.
Amounts are strings (as returned by MySQL `DECIMAL`); cast them in JavaScript.

## Project structure

```
config/        application and database configuration
database/      schema.sql, seed.sql and the migrate.php runner
public/        front controller, .htaccess, assets and uploaded images
routes/        route definitions
src/Core/      framework pieces (Env, Database, Session, Router, ...)
src/Admin/     admin dashboard, category and product management
src/Auth/      authentication service, guard and controllers
src/Cart/      session cart service and cart/checkout controllers
src/Home/      public catalog controllers and queries
src/Orders/    order persistence for placed carts
views/         page templates and layout
```

## Security notes

- Passwords are stored as bcrypt hashes (`password_hash`).
- All queries use prepared statements.
- Forms are protected with CSRF tokens.
- The real `.env` file is git-ignored and must never be committed.

## Catalog migrations (existing installations)

Use the additive catalog runner, **not** the legacy `database/migrate.php`, which
initializes accounts (existing accounts are preserved):

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

The catalog stores composition, `width_inch` (DECIMAL(5,1)), GSM, meter/yard
unit, and the wholesale `price_pkr` (DECIMAL(12,2)). Optional specifications may
be left blank. Categories are hidden instead of deleted, and the foreign key
restricts deletion of a category in use. Only published products in visible
categories appear publicly.

Product photos uploaded from the admin product form are stored under
`public/uploads/catalog/{category_id}/product-{product_id}/`. The product id
keeps the folder stable, and moving a product to another category moves its
folder and rewrites the stored paths. JPG, PNG, and WebP files are accepted up
to 2 MB each (six per product). Uploaded files are runtime data and are not
committed to Git. Set the PHP upload limits to at least 2 MB before deployment.

## Catalog verification

PHP needs `pdo_mysql` and `mbstring`. See [tests/README.md](tests/README.md) for
isolated MySQL, HTTP regression, and manual browser checks. No test command seeds
or resets the configured application's users.

## Orders and settings

Run `php database/catalog-migrate.php` to apply pending orders migrations. The same incremental runner never seeds accounts. The legacy setup script now preserves existing accounts; administrator credentials are changed through Settings. Order confirmation does not deduct stock; inventory is not implemented.

## Customer and admin order integration

Checkout writes the admin `orders` and `order_items` records in one transaction.
Product prices and availability are rechecked while the records are locked; amounts
are calculated in integer cents and saved as decimal snapshots. Customers can view
only their own confirmation. The admin page and existing read-only API use the
same records and status flow. No colour is selected until variants are implemented.

Apply pending migrations with `php database/catalog-migrate.php`. The order bridge
migrations preserve older checkout-shaped records and the existing migration
history; the `subtotal_pkr` compatibility alias allows the earlier contact
migration to run against the admin schema. Legacy orders without a city or payment
method show those values as unrecorded. Back up the database before deployment.
