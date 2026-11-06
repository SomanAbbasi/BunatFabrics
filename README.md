# Bunat Fabrics

Bunat Fabrics is a modern B2B wholesale fabric sourcing and ordering platform built for textile mills, fashion brands, and apparel manufacturers. It streamlines bulk fabric procurement with transparent wholesale PKR pricing, verified textile specifications, and direct mill-to-factory order management.

---

## Key Features

- **Wholesale Fabric Catalog**: Browse fabrics with verified apparel specifications including fiber composition, width (inches), weight (GSM), and wholesale rates per meter or yard.
- **Custom Production Meterage**: Apparel brands can order sample bolts (e.g. 50m) up to full production roll runs (10,000m+) directly with custom quantity inputs.
- **Streamlined Wholesale Cart & Checkout**: Fast ordering workflow without forced redirects, allowing brands to add multiple fabric bundles, review subtotal breakdowns, and submit dispatch requests.
- **Customer Order Tracking**: Real-time order history with fabric thumbnail previews, dispatch status, tracking notes, and full line-item summaries.
- **Admin Control Center**:
  - **Orders Management**: Process incoming wholesale orders, review buyer contacts and delivery destinations, inspect fabric item photos, and update order statuses (`Pending`, `Confirmed`, `Shipped`, `Delivered`, `Cancelled`).
  - **Catalog Management**: Create and manage fabric categories, specifications, and upload high-resolution swatch photography.
  - **Admin Settings**: Securely update administrator name, email address, and account password.

---

## Quick Setup

### 1. Requirements

- PHP 8.1 or newer with `pdo_mysql` and `mbstring` extensions
- MySQL 8.0 or MariaDB 10.5+

### 2. Configuration

Copy the example environment file and configure your database connection:

```bash
cp .env.example .env
```

Ensure your `.env` contains valid database credentials:

```ini
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bunat_fabrics
DB_USER=root
DB_PASSWORD=your_password
```

### 3. Database Migration

Run the catalog migration runner to set up all tables and initial categories:

```bash
php database/catalog-migrate.php
```

### 4. Start the Application

Launch the local PHP development server:

```bash
php -S localhost:8080 -t public server.php
```

Open your browser at `http://localhost:8080` to view the storefront.

---

## Default Accounts

- **Administrator Portal**: Accessible at `http://localhost:8080/admin`
  - **Email**: `admin@gmail.com`
  - **Password**: `Admin@123` *(or as set in `.env`)*
- **Customer Portal**: Customers can register for wholesale sourcing via the **Create Account** button or log in at `http://localhost:8080/login`.

---

## Production Deployment Notes

- Point your web server document root directly to the `public/` directory.
- Enable URL rewriting (`mod_rewrite` for Apache or standard try_files for Nginx).
- Ensure the `public/uploads/` directory is writable by the web server user.

## Account Settings

Administrators use `/admin/settings`; customers use `/settings` from their profile menu or dashboard. Profile changes require the current password. Password changes also require a matching confirmation and the existing signup policy (8+ characters with uppercase, lowercase, and a number). The same password is rejected, and the server enforces bcrypt's 72-byte limit.

Both pages share `AccountSettingsController`, `AccountSettingsService`, and form partials. The controller takes the account ID from `Guard`, checks CSRF, and passes only explicit fields to the service. Successful updates refresh the signed-in account, regenerate the session ID, and renew the CSRF token. Passwords are never repopulated or flashed. Other sessions are not revoked; the application has no session registry or shared rate limiter.

No schema change is required. The existing migration runner and initial administrator setup preserve existing accounts, including changed emails and password hashes.

### Settings regression checks

Use an **isolated, disposable MySQL server** and an account allowed to create databases. The fixture script creates a uniquely named `bunat_settings_test_*` database; it never uses the application's configured database. It leaves the fixture available for HTTP checks. Do not run these checks against production or edit `.env` for testing.

```bash
export SETTINGS_TEST_DSN='mysql:host=127.0.0.1;port=3307;charset=utf8mb4'
export SETTINGS_TEST_USER='test_user'
# Set SETTINGS_TEST_PASSWORD through your local environment if needed.
php tests/account_settings.php
```

Copy the printed `TEST_DATABASE` name into the following command, then start the guarded test server:

```bash
export SETTINGS_TEST_DATABASE='bunat_settings_test_REPLACE_WITH_PRINTED_SUFFIX'
php -S 127.0.0.1:8098 -t public tests/settings_router.php
```

In another terminal:

```bash
python3 tests/account_settings_http.py
php tests/dashboard.php
php tests/catalog_search.php
git diff --check
```

The PHP checks cover validation, unique emails, password hashing/authentication, session renewal, registration, and repeatable migrations. HTTP checks cover both roles, ownership, CSRF, malformed input, escaped errors, password changes, login/logout, and existing pages. The HTTP runner refuses a server without the test marker. Stop the test server after testing; dispose of the isolated fixtures when finished.

For browser verification, sign in to the test server with `new-admin@example.test` or `new-user@example.test`, password `Changed456`. Check both settings pages at 1440, 768, 390, and 320 pixels; keyboard focus; Show/Hide controls; field errors; and profile-menu links. These accounts exist only in the disposable fixtures.
