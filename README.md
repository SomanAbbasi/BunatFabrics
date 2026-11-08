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

Administrators use `/admin/settings`; customers use `/settings` from their profile menu or dashboard. Password-based accounts require the current password; Google-only accounts require fresh linked Google verification. Email changes also require a code sent to the new address. Password changes also require a matching confirmation and the existing signup policy (8+ characters with uppercase, lowercase, and a number). The same password is rejected, and the server enforces bcrypt's 72-byte limit.

Both pages share `AccountSettingsController`, `AccountSettingsService`, and form partials. The controller takes the account ID from `Guard`, checks CSRF, and passes only explicit fields to the service. Successful updates refresh the signed-in account, regenerate the session ID, and renew the CSRF token. Passwords are never repopulated or flashed. Password/email changes invalidate older sessions on their next request; security endpoints use shared database rate limits.

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

## Google sign-in, recovery and verified email changes

Install the locked PHP dependencies with `composer install --no-dev` (or `php /path/to/composer.phar install --no-dev`). Apply the additive migrations with `php database/catalog-migrate.php`. Migrations 008–011 add hashed challenges, rate-limit counters, unique Google identity links, and permit NULL passwords for Google-only users. Existing records and password hashes are preserved. Deploy code and migrations together. Existing sessions from before this release must sign in again.

The application reuses `Guard`, `AuthService`, the shared settings controller/service, and existing PHP layouts. Google identity is keyed by verified `sub`, never automatically linked by matching email. An existing account is connected only from authenticated settings after password verification. New Google registrations always get the customer role. Google-only customers verify their linked identity again before profile changes or establishing a local password; this proof expires after five minutes and is cleared after saving. Once a local password exists, settings require it.

Email changes send a six-digit code to the requested new address; the old address stays active until confirmation. Codes expire after ten minutes, allow five attempts, and have a one-minute resend cooldown and five-send hourly limit. Reset links expire after 30 minutes and allow five failed secret attempts. Password/email changes invalidate older sessions on their next request. Reset links and email challenges are invalidated by credential changes. Google access/refresh tokens are not persisted.

### Local Google Cloud setup

Use Apache with `public/` as the document root and a `localhost` virtual host or alias. Set the application origin to **`http://localhost`** and browse from that origin throughout sign-in, linking and verification. Do not start on `bunat.local`: it is a custom domain and does not get Google's HTTP localhost exception, and its session cookies are separate.

Create a Google Auth Platform project, configure Branding/Audience and a **Web application** OAuth client. Register this exact authorized redirect URI:

```
http://localhost/auth/google/callback
```

The implementation uses a server redirect flow; no Google JavaScript SDK or authorized JavaScript origin is required. Request only `openid`, `email`, and `profile`. Add test users when required by your project's testing configuration. For production, register the actual HTTPS callback and configure the matching HTTPS application origin. If using a development port such as 8080, both `APP_URL` and the registered redirect must include that same port.

Configure these names privately in your server environment or local environment file; never commit the values:

```
APP_URL=http://localhost
APP_ENV=development
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
MAIL_DRIVER=file
MAIL_FROM_ADDRESS=accounts@example.test
```

Server environment variables override the local file. Blank Google credentials hide the sign-in option. The library checks signatures and issuer/audience; application checks additionally require nonce, expiry and issued-at claims. State is checked before token exchange, PKCE S256 is used, and fresh verification requires a recent `auth_time`. Real provider behavior still requires an OAuth smoke test after credentials are configured.

### Email delivery

`MAIL_DRIVER=file` writes private `.eml` messages under `storage/mail/` and is disabled in production. Open these files locally to test links/codes; do not share their contents. This is an intentional private mailbox, not application logging. Nothing is sent externally.

For production set `MAIL_DRIVER=smtp`, `MAIL_FROM_ADDRESS`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` (`tls` for STARTTLS, typically 587, or `ssl` for implicit TLS, typically 465), `MAIL_USERNAME`, and `MAIL_PASSWORD`. Use a provider-authorized sender. SMTP messages are queued outside the document root so network latency does not disclose account existence during recovery. Run this worker every minute using cron under the application user:

```bash
php /absolute/path/to/BunatFabrics/bin/send-account-mail.php
```

The worker retries failed delivery on later runs and discards unsent messages older than 30 minutes. Monitor its generic failure messages and ensure `storage/mail` is writable only by the application user. Without the worker, SMTP recovery and OTP emails will not be delivered. Notifications share the same queue. Configure TLS normally; certificate verification is not disabled. Do not put credentials in cron command arguments.

Keep the web document root at `public/`. Redact query strings from web/proxy/access/error logs for OAuth callbacks and reset URLs (Apache access logs can use `%U` rather than `%r` or `%q`). Reset URLs are immediately exchanged for session state and redirected to a clean URL; recovery/settings responses use no-store and no-referrer headers. Periodically delete expired `auth_challenges` and `auth_limits` rows and old development mail captures; retain neither in backups longer than needed. A mail failure never changes the account email.

### Verification

Use only an isolated MySQL server with the fixture configuration documented above:

```bash
php tests/account_verification.php
php tests/dashboard.php
php tests/catalog_search.php
composer audit
```

`account_verification.php` includes settings, recovery and Google identity service suites. Google tests supply simulated verified claims; they do not contact Google. For HTTP tests, create a fresh fixture with `php tests/account_settings.php`, start `tests/settings_router.php` with `APP_ENV=testing MAIL_DRIVER=file` and its printed database name, then run `python3 tests/account_settings_http.py` followed by `python3 tests/auth_recovery_http.py`. The recovery HTTP suite changes the test customer's password to `HttpReset789`; use fresh fixtures when repeating.

Manually verify Google consent/cancellation, new customer signup, same-email collision, connecting an existing account, wrong-account reauthentication, and fresh Google verification before setting a password. Confirm OTP delivery/expiry and SMTP notifications with your provider. Test desktop/mobile and keyboard navigation. Live Google and external SMTP delivery are not verified by offline tests. Distributed proxy deployments must configure trustworthy client-IP handling before scaling IP limits; the current implementation uses REMOTE_ADDR and never trusts arbitrary forwarding headers. The queue provides retryable delivery, not guaranteed exactly-once email delivery.
