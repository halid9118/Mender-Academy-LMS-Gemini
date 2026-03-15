# Digital Mender Academy LMS

Production-ready LMS for academy.digitalmender.com, built with PHP 8.2+ and MySQL.

## Features
- Role‑based access (admin, instructor, student)
- Course/module/lesson management with drip and prerequisites
- Enrollment and progress tracking (including YouTube videos)
- Certificate generation (PDF)
- REST API for integrations
- Light/dark theme with brand colors (#d4af37, #0a0f18)

## Installation
See [install.txt](install.txt) for step‑by‑step deployment on cPanel.

## Development
1. Clone the repository.
2. Run `composer install`.
3. Copy `.env.example` to `.env` and adjust database credentials.
4. Import `sql/schema.sql` into your database.
5. Run `composer migrate` to apply migrations (if any).
6. Point your web server to `public/` as document root.
7. Access the site.

Run tests: `composer test`
Run linter: `composer lint`
Run static analysis: `composer stan`