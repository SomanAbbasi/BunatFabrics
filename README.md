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
