# SZN Store

SZN Store is a PHP and MySQL fashion e-commerce application with a customer storefront and an admin dashboard. It supports product browsing, categories, shopping carts, checkout, customer accounts, wishlists, order tracking, contact messages, and multilingual content.

## Features

- Fashion storefront with products, categories, sizes, stock, and product images
- Shopping cart with quantity and size selection
- Customer registration, login, profiles, saved addresses, and password reset
- Wishlist and order history
- Checkout and delivery-city pricing
- Admin dashboard for products, orders, users, cities, and social links
- English, French, and Arabic translations with right-to-left Arabic layout
- CSRF protection, session hardening, login rate limiting, and security headers
- Email support through PHPMailer

## Requirements

- PHP 7.4 or newer
- MySQL 5.7+ or MariaDB
- Apache (XAMPP is recommended for local development)
- Composer

## Local Installation with XAMPP

1. Clone or copy the project into the XAMPP web root:

   ```text
   /Applications/XAMPP/xamppfiles/htdocs/szn_store
   ```

2. Start **Apache** and **MySQL** from the XAMPP manager.

3. Install PHP dependencies from the project directory:

   ```bash
   composer install
   ```

4. Configure the database and mail settings in `config/security.php`. Use local environment variables or a local-only configuration file for credentials.

5. Initialize the database and seed the default categories, sizes, products, social links, and admin user:

   ```bash
   php setup_db.php
   php setup_db_cities.php
   ```

   `setup_db.php` recreates the configured database before creating its tables. Use it only with a local or disposable database.

6. Open the storefront:

   [http://localhost/szn_store/](http://localhost/szn_store/)

## Development Admin Account

The database setup script creates this development account:

```text
Email: admin@example.com
Password: Admin@123
```

Change or remove this account immediately in any shared, staging, or production environment. Never use the seeded credentials in production.

The admin dashboard is available at:

```text
http://localhost/szn_store/admin/dashboard.php
```

## Configuration and Security

- Do not commit real database, SMTP, or admin credentials.
- Rotate any credentials that have previously been stored in tracked files before publishing this repository.
- Prefer environment variables for `DB_*`, `SMTP_*`, and `ADMIN_EMAIL` values.
- Keep `logs/`, `uploads/`, and other runtime data outside version control.
- Use HTTPS and set `ENABLE_HTTPS=true` outside local development.
- Restrict access to `setup_db.php` or remove it after initializing a deployed database.

## Project Structure

```text
admin/                 Admin dashboard and management pages
assets/                CSS, JavaScript, and image assets
config/                Database and security configuration
includes/              Shared authentication, layout, mail, and utility code
lang/                  English, French, and Arabic translations
pages/                 Storefront pages loaded through index.php
setup_db.php           Database schema and seed data setup
setup_db_cities.php    Delivery city table and seed data setup
index.php              Main storefront entry point
```

## Composer Dependency

The project uses [PHPMailer](https://github.com/PHPMailer/PHPMailer) for SMTP email delivery.

## License

The project code is licensed under the [MIT License](LICENSE). Third-party
software, fonts, and external images have separate terms listed in
[THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
