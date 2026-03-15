# AGENTS.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

**Phirse** is a PHP-based order management system for the Supreme Student Council (SSC) of Pamantasan ng Lungsod ng Valenzuela (PLV). It allows student organizations (sellers) to list products, students to browse and order them, and the SSC admin to oversee the entire platform.

## Local Development Setup

**Requirements:** XAMPP (Apache + MySQL), PHP 8.2

1. Place the project under `C:\xampp\htdocs\Phirse` (or equivalent htdocs path).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Import the database: open phpMyAdmin, create database `phirse_db`, then import `database/phirse_db.sql`.
4. Access the app at `http://localhost/Phirse`.

**Syntax check a PHP file:**
```
C:/xampp/php/php.exe -l <file.php>
```

**Connect to MySQL CLI:**
```
C:/xampp/mysql/bin/mysql.exe -u root phirse_db
```

**Run database migrations** (schema changes are PHP scripts, not CLI migrations — run them in the browser):
```
http://localhost/Phirse/database/<migration-script>.php
```

## Production Deployment (Railway)

Deployed on [Railway](https://railway.app) using Nixpacks. PHP 8.2 is installed via Nix; the app is served with PHP's built-in server:
```
php -S 0.0.0.0:$PORT
```
Database credentials are injected via environment variables: `MYSQLHOST`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLPORT`. The health check endpoint is `health.php`.

## Architecture

### Portal Structure (Three Roles)

The app is split into three separate portals under one codebase. Every portal has its own session keys, DB includes, and directory:

| Portal | Directory | Session Keys |
|---|---|---|
| Admin (SSC) | `admin/` | `$_SESSION['user_id']`, `['username']`, `['role'] = 'admin'` |
| Seller (Org President) | `seller/` | `$_SESSION['seller_id']`, `['seller_name']`, `['organization']`, `['role'] = 'seller'` |
| Student | `student/` | `$_SESSION['student_id']` |

Entry point is `index.html`. `unified-auth.php` handles login for all three roles by trying admin → seller authentication in sequence. The student portal has its own login at `student/login.php`.

### Database Layer (Two Patterns — Important)

There are **two separate DB connection files** with different APIs:

- **`database/config.php`** — PDO, reads credentials from env vars (Railway-aware), used by `admin/` and `seller/` portals. All queries use `$pdo->prepare(...)`.
- **`student/db/config.php`** — mysqli, hardcoded `localhost`/`root`, used by the `student/` portal. Provides helper functions: `executeQuery()`, `fetchSingle()`, `fetchAll()`. Also sets security headers, timezone (`Asia/Manila`), and session config.

When adding features, use the connection pattern that already exists in that portal's directory.

### Key Database Tables

- **`users`** — Admin accounts only (SSC admins).
- **`sellers`** — Organization president accounts. Login uses `organization` field as the user ID.
- **`students`** — Student accounts. Bulk-imported via CSV/Excel upload.
- **`products`** — Products submitted by sellers. Require admin approval (`status`: `pending` → `approved`/`rejected`). Support multiple images (comma-separated `image_path`), size variants, ticket access restrictions (`ticket_type`, `allowed_organizations`), and pre-order mode.
- **`orders`** — Links students, sellers, and products. Status flow: `pending → paid → confirmed → claiming → completed` (or `cancelled`). Payment methods: `onhand` (cash) or `gcash` (receipt upload). Stores `payment_proof_path`, `claiming_datetime`, `cancellation_reason`.
- **`student_seller_affiliations`** — Maps students to their home organization (seller), populated during bulk student import.
- **`notifications`** — Student-facing notifications.
- **`seller_notifications`** — Seller-facing notifications.
- **`admin_notifications`** — Admin-facing notifications (e.g., pending product approvals).

### Schema Migrations

Schema changes live as individual PHP scripts in `database/` (e.g., `add-cancellation-column.php`, `add-adviser-column.php`). They are run once via the browser, not a CLI migration tool. The full baseline schema is `database/phirse_db.sql`.

### File Uploads

Uploaded files are stored under `uploads/` (relative to the project root):
- `uploads/products/` — Product images, named with a timestamp-based hash.
- `uploads/logos/` — Seller/organization logos.
- `uploads/images/` — Static UI assets (e.g., `Plogo.png`).
- `uploads/pdfs/` — PDF attachments for products.
- `uploads/payment_proofs/` — GCash payment screenshots uploaded by students.

Image paths stored in the DB are relative paths like `../uploads/products/<filename>`, resolved from the portal subdirectory.

### Authentication Guard Pattern

- Admin pages start with `session_start()` then check `$_SESSION['user_id']` / `$_SESSION['role']`.
- Seller pages include `seller/includes/seller-header.php` which enforces `$_SESSION['seller_id']`.
- Student pages use the `requireLogin()` helper from `student/db/config.php` which checks `$_SESSION['student_id']`.

### Notifications

Notifications are polled via AJAX from dedicated endpoints:
- `admin/fetch-notifs.php`, `admin/fetch-pending-products-notifs.php`
- `seller/fetch-notifications.php`
- `student/fetch-notifications.php`, `student/get-notification-count.php`

### Reporting / Exports

PDF generation and CSV/Excel exports are handled by standalone PHP files (e.g., `admin/export_orders_pdf.php`, `seller/export_seller_orders.php`, `seller/upload-students-excel.php`). These are accessed directly by the browser.
