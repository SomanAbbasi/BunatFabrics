# Bunat Fabrics 🧵

> A modern B2B wholesale fabric ordering and textile management platform built for textile mills, apparel manufacturers, fashion designers, and retail brands.

---

## 📌 Project Overview

**Bunat Fabrics** streamlines bulk textile sourcing with transparent wholesale PKR pricing, verified technical fabric specifications (GSM, width, composition), flexible sample bolt and production meterage ordering, real-time order tracking, and a comprehensive admin management dashboard.

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.1+ (Native MVC Architecture, Strict Types, PDO MySQL)
- **Frontend:** Modern Responsive CSS (Design Tokens, Flexbox/Grid, Mobile Drawers), Vanilla JavaScript
- **Database:** MySQL 8.0+ / MariaDB 10.5+
- **Authentication:** Dual Auth (Secure Password Hash with Rate Limiting & Google OpenID Connect / OAuth2)
- **Dependencies:** Composer (`jumbojett/openid-connect-php`, `phpmailer/phpmailer`)
- **Server:** PHP Built-in Server (Development) / Apache & Nginx with URL rewriting (Production)

---

## ✨ Key Features

- **Wholesale Fabric Catalog:** High-resolution swatch previews, fiber composition, width (inches), weight (GSM), and wholesale unit rates.
- **Dynamic Bulk Ordering:** Apparel makers can order custom quantities (meters/yards) from sample cuts to production rolls.
- **Wholesale Cart & Checkout:** Streamlined checkout with delivery contact collection, city selection, and wholesale payment options (Bank Transfer, Cash on Delivery).
- **Customer Order Tracking:** Dedicated customer dashboard and mobile-optimized order cards to track order statuses (`Pending`, `Confirmed`, `Dispatched`, `Delivered`).
- **Admin Control Center:**
  - **Orders Management:** Review incoming orders, update fulfillment statuses, and inspect buyer shipping details.
  - **Catalog Management:** Create, edit, and publish fabrics, set specifications, and upload swatch images.
  - **Category Control:** Group fabrics into collections (Silk, Lawn & Cotton, Chiffon & Organza, Linen, Net & Karandi, Velvet).
  - **Account & Security Settings:** Update profiles, change passwords, and manage Google-linked sign-ins.

---

## 🚀 Getting Started & Setup Guide

Follow these steps to set up the project locally on your machine:

### 1. Prerequisites

- **PHP 8.1** or newer with extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `curl`
- **MySQL 8.0+** or **MariaDB 10.5+**
- **Composer** (PHP dependency manager)
- **Git**

### 2. Clone the Repository

```bash
git clone https://github.com/SomanAbbasi/bunat-fabrics.git
cd bunat-fabrics
```

### 3. Install Dependencies

Install the required PHP packages via Composer:

```bash
composer install
```

### 4. Environment Configuration

Copy the example environment configuration:

```bash
cp .env.example .env
```

Open `.env` in your editor and configure your local database credentials:

```ini
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bunat_fabrics
DB_USER=root
DB_PASSWORD=your_mysql_password
```

### 5. Run Database Migrations

Create the database in MySQL if it doesn't already exist:

```sql
CREATE DATABASE bunat_fabrics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run the migration script to create all necessary tables:

```bash
php database/catalog-migrate.php
```

### 6. Seed Fabric Catalog & Swatches

Populate the database with authentic fabric collections and high-resolution swatch images:

```bash
php database/seed-fabrics.php
```

### 7. Run the Application

Start the built-in development server:

```bash
php -S localhost:8080 -t public server.php
```

Visit **`http://localhost:8080`** in your browser.

---

## 🔐 Default Access Accounts

| Role | Portal URL | Email | Password |
|---|---|---|---|
| **Administrator** | `http://localhost:8080/admin` | `admin@gmail.com` | `Admin@123` |
| **Test Customer** | `http://localhost:8080/login` | `customer@bunatfabrics.com` | `Bunat@2026` |

*New customer accounts can also be created directly via the storefront **Sign up** page.*

---

## 👥 Contributors

This project is actively developed and maintained by:

- **Soman Abbasi** — [@SomanAbbasi](https://github.com/SomanAbbasi)
- **Muhammad Saad** — [@Muhammad-Saad-Programmer](https://github.com/Muhammad-Saad-Programmer)

---

## 📄 License

This project is licensed for proprietary wholesale operations under Bunat Fabrics.
