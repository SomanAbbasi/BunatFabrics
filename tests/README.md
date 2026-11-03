# Catalog verification

Run PHP checks from the repository root:

```bash
find src views database tests -name '*.php' -exec php -l {} \;
php -l routes/web.php
git diff --check
```

## Isolated database tests

`catalog.php` creates a uniquely named `bunat_catalog_test_<random>` database,
adds test-only users, verifies the migration runner, and exercises catalog
services inside a rolled-back transaction. It also checks that the configured
application's users remain unchanged. The disposable database and test accounts
are retained for HTTP checks; the script never drops a database.

The test database server user must have CREATE DATABASE permission. If the app
user does not, use a separate MySQL test instance; do not broaden the app user's
permissions. For example, an already running local isolated socket:

```bash
CATALOG_TEST_DSN='mysql:unix_socket=/tmp/bunat-catalog-mysql.sock;charset=utf8mb4' \
php -d session.save_path=/tmp tests/catalog.php
```

Optional `CATALOG_TEST_USER` and `CATALOG_TEST_PASSWORD` select test-only
credentials. Without a test DSN, the configured server/user is used to create a
separate database. No DROP/reset command is provided.

## HTTP checks

Copy the generated `TEST_DATABASE` name into the command below. Start the server
from the repository root, keeping it on loopback. The router refuses an absent
DSN or database name outside the test prefix and never edits `.env`.

```bash
CATALOG_TEST_DATABASE=bunat_catalog_test_REPLACE_WITH_GENERATED_SUFFIX \
CATALOG_TEST_DSN='mysql:unix_socket=/tmp/bunat-catalog-mysql.sock;charset=utf8mb4' \
php -d session.save_path=/tmp -S 127.0.0.1:8097 -t public tests/catalog-router.php
```

Then run `python3 tests/catalog_http.py`. It refuses servers without the isolated
test marker. It tests role checks on every catalog route, CSRF, form retention,
CRUD/status changes, public filtering and escaping, and auth regressions. It
leaves catalog fixtures only in that disposable database. Test users are
`admin@example.test` / `TestAdmin123` and
`customer@example.test` / `TestCustomer123`; never use these accounts in deployment.

## Manual browser checklist

- At desktop and 320–390px widths, open category/product lists and forms; check
  readable controls, horizontal table scrolling, no page overflow, and menu toggle.
- Create/edit a category; try duplicate name/slug; hide and enable it.
- Create a draft fabric, edit it, publish, unpublish, and archive it; verify the
  public homepage changes and hidden categories suppress their products.
- Try duplicate codes, invalid dimensions/category, and empty required fields;
  check inline errors and that entered values remain visible.
- Confirm signup/login/logout and customer dashboard remain functional; customers
  must get 403 on all admin routes, including POST submissions.
- Refresh after saving to confirm redirects avoid duplicate submissions.

The PHP test server exercises application routes and sessions, not Apache rewrite
configuration. Check `public/.htaccess` through Apache before deployment.
