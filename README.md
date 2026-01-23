# Zendo

Zendo is a Laravel-based system for ride and parcel operations.

<p align="center">
  <a href="https://github.com/raydanielg/zendo-laravel-admin-panel">
    <img alt="Zendo" src="https://readme-typing-svg.demolab.com?font=Inter&weight=700&size=28&duration=2800&pause=700&color=0EA5E9&center=true&vCenter=true&width=700&lines=Zendo+%E2%80%94+Ride+%26+Parcel+Platform;Laravel+12+%2B+Modular+Architecture;Admin+Panel+%2B+REST+API" />
  </a>
</p>

<p align="center">
  <a href="https://github.com/raydanielg/zendo-laravel-admin-panel">
    <img alt="License" src="https://img.shields.io/badge/License-MIT-0EA5E9?style=for-the-badge" />
  </a>
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-0B5FFF?style=for-the-badge" />
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge" />
  <img alt="Modules" src="https://img.shields.io/badge/Architecture-Modular-22C55E?style=for-the-badge" />
</p>

---

## Table of Contents

- [Overview](#overview)
- [Mobile Apps](#mobile-apps)
- [Front-end (Landing Page)](#front-end-landing-page)
- [API (Backend) Overview](#api-backend-overview)
- [Requirements](#requirements)
- [Quick Start (Local)](#quick-start-local)
- [Web Installer](#web-installer)
- [Developer Details](#developer-details)
- [Support](#support)
- [Contributing](#contributing)
- [License](#license)

## Overview

Zendo is a Laravel-based system for **ride-hailing** and **parcel delivery** operations, built with a modular structure (`nwidart/laravel-modules`) and a REST-style API.

## Mobile Apps

- **Driver App**: https://github.com/REPLACE_WITH_DRIVER_APP_REPO
- **Customer App**: Coming Soon

## Front-end (Landing Page)

The public website/landing pages are served via `routes/web.php` using `App\\Http\\Controllers\\LandingPageController`.

### Pages & Links

- **Home**: `/` (`route('index')`)
- **About Us**: `/about-us` (`route('about-us')`)
- **Contact Us**: `/contact-us` (`route('contact-us')`)
- **Privacy Policy**: `/privacy` (`route('privacy')`)
- **Terms & Conditions**: `/terms` (`route('terms')`)
- **Parcel Tracking**: `/track-parcel/{id}` (`route('track-parcel')`) where `{id}` is the parcel `ref_id`

### Branding (Logo / Favicon)

Landing page layout uses:

- **Header logo**: `getSession('header_logo')`
  - If not set, falls back to `public/landing-page/assets/img/logo.png`
- **Footer logo**: `getSession('footer_logo')`
  - If not set, falls back to `public/landing-page/assets/img/logo.png`
- **Favicon**: `getSession('favicon')`
  - If not set, falls back to `public/landing-page/assets/img/favicon.png`

When set, images are loaded from:

- `storage/app/public/business/{filename}`

### Theme Colors

The landing page theme supports dynamic colors via business settings:

- `businessConfig('website_color')` applied to CSS variables:
  - `--text-primary`, `--text-secondary`, `--bs-primary`, `--bs-secondary`, `--bs-body-bg`
- `businessConfig('text_color')` applied to:
  - `--title-color`, `--secondary-body-color`

Default CSS variables are defined in:

- `public/landing-page/assets/css/main.css`

## API (Backend) Overview

This project exposes REST-style endpoints under the `/api` prefix. The root `routes/api.php` is minimal, and most API routes live inside module route files:

- `Modules/AuthManagement/Routes/api.php`
- `Modules/UserManagement/Routes/api.php`
- `Modules/TripManagement/Routes/api.php`
- `Modules/ParcelManagement/Routes/api.php`
- `Modules/Gateways/Routes/api.php`
- `Modules/BusinessManagement/Routes/api.php`
- `Modules/TransactionManagement/Routes/api.php`
- `Modules/VehicleManagement/Routes/api.php`
- `Modules/ReviewModule/Routes/api.php`
- `Modules/PromotionManagement/Routes/api.php`
- `Modules/ZoneManagement/Routes/api.php`
- `Modules/FareManagement/Routes/api.php`

### Example Endpoints

Auth:

- `POST /api/customer/auth/registration`
- `POST /api/customer/auth/login`
- `POST /api/driver/auth/registration`
- `POST /api/driver/auth/login`

Trips (requires `auth:api` + maintenance middleware unless noted):

- `POST /api/customer/ride/get-estimated-fare`
- `POST /api/customer/ride/create`
- `GET /api/customer/ride/details/{trip_request_id}`
- `GET /api/customer/ride/digital-payment` (public)

Parcels:

- `GET /api/customer/parcel/category`
- `GET /api/customer/parcel/vehicle`

Payments Config:

- `GET /api/v1/payment-config`

## Requirements

- PHP 8.2+
- MySQL/MariaDB
- Composer
- Node.js & npm (for building assets if needed)

## Quick Start (Local)

1. Install PHP dependencies

   - `composer install`

2. Create `.env`

   - Copy `.env.example` to `.env` (if `.env` does not exist)
   - Set database credentials

3. Generate app key (if needed)

   - `php artisan key:generate`

4. Run the app

   - `php artisan serve`

## Web Installer

If your project uses the web installer, open the installer URL in the browser and follow the steps.

Notes:

- Installation branding is set to **Zendo**.
- Installation pages include an **info (i)** button at the top-right with developer details.

## Developer Details

- Name: Ray Developer
- Phone: +255742710054

## Support

For installation or configuration support, contact the developer using the details above.

## Contributing

Contributions are welcome.

1. Fork this repository
2. Create a feature branch
3. Make changes with clear commits
4. Open a Pull Request with:
   - What changed
   - Why it changed
   - Steps to test

## License

This project is licensed under the MIT License. See [`LICENSE`](./LICENSE).
