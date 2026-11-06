<div align="center">

# MOFA — Made of Faten

**A pre-order website for a home bakery that makes sourdough, bagels and layered sandwiches.**
Customers order today for delivery tomorrow morning. The site is in Arabic and English and is built for phones first.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-4E56A6?logo=livewire&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-5-FDAE4B)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)

</div>

---

## Contents

- [Features](#features)
- [How an order works](#how-an-order-works)
- [Tech stack](#tech-stack)
- [Getting started](#getting-started)
- [Configuration](#configuration)
- [Running the shop](#running-the-shop)
- [Tests](#tests)
- [Project structure](#project-structure)
- [Conventions](#conventions)

## Features

### Storefront
- **Arabic and English** with full right-to-left layout. Every URL has a `/{locale}` prefix, and the site remembers the visitor's choice.
- **Menu by category**, with a filter bar. Each item has its own page with a photo, ingredients and nutrition facts. Calories are calculated automatically from the macros.
- **Notes under item photos.** These are short labels like "Suitable for diabetics", with brand colours, fonts and shapes chosen from the dashboard.
- **Cart in the browser.** It keeps working across pages and gives feedback on every add: a small animation, a soft chime and a short vibration on phones.
- **Three-step checkout** built with Livewire:
  1. Contact details. Email and date of birth are optional.
  2. Delivery area, address, and an optional pin on a map (Leaflet).
  3. Delivery day and time slot.
- **Private order page** behind a signed link. It refreshes itself while the order waits for payment.

### Dashboard (`/admin`, Filament)
- **Orders.** Confirm the payment, mark the order out for delivery or delivered, or cancel it.
- **Prep list.** Everything to bake for a given day, with totals per item.
- **Menu.** Categories with cover images, items and ingredients. Slugs are generated from the name.
- **Delivery.** Delivery areas (with fees, or pickup), time slots with capacity, and closed days.
- **Customers.** Order history, plus a 🎂 badge when a birthday falls this week.
- **Settings.** Order cutoff time, InstaPay details and the WhatsApp number.
- **Live alerts.** A notification bell for new orders, checked every 30 seconds.
- **Look.** Uses the MOFA theme, works in English or Arabic, and Arabic text uses Tajawal.

## How an order works

```
Customer places order ──► Pending payment ──► Faten confirms ──► Confirmed ──► Out for delivery ──► Delivered
                              │                    │
                              │                    └─► one email to the customer (if they gave an email)
                              └─► pays with InstaPay, sends the receipt on WhatsApp
```

- **Payment** is a manual InstaPay transfer. The customer's order page has a WhatsApp button that opens a chat with the order number already written, so they can send the receipt.
- **Confirmation** is done by Faten from the dashboard after she checks the receipt.
- **Unpaid orders** are never cancelled automatically. Each one keeps its place in the day until it is confirmed or cancelled.
- **Prices** are always recalculated on the server. The browser cart only stores item IDs and quantities.
- **Email** is sent only once: when the order is confirmed. If the customer left the email blank, nothing is sent.

## Tech stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ (8.4 recommended), MySQL |
| Interactive pages | Livewire 4 (checkout, order page) |
| Dashboard | Filament 5 |
| Frontend | Tailwind CSS 4, Alpine.js, Vite |
| Map | Leaflet with OpenStreetMap tiles (CARTO tiles when a key is set) |
| Fonts | Self-hosted through `@fontsource`: Jost, Cormorant Garamond, Bagel Fat One and Caveat for English; Tajawal for Arabic |

## Getting started

**Requirements:** PHP 8.3+, Composer, Node 20+ and MySQL 8.

```bash
git clone <repo-url> mofa && cd mofa

composer install
cp .env.example .env
php artisan key:generate
```

Create the database:

```bash
mysql -u root -e "CREATE DATABASE mofa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Set the first admin account in `.env`:

```dotenv
ADMIN_NAME=Faten
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=choose-a-strong-password
```

Then migrate, link storage and build the assets:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

| | URL |
| --- | --- |
| Site | http://localhost:8000 (redirects to `/ar` or `/en`) |
| Dashboard | http://localhost:8000/admin |

For live reload while you work, run `npm run dev` in a second terminal.

To add a demo item (price left at 0):

```bash
php artisan db:seed --class=MenuDemoSeeder
```

> **Using a phone's location on the map** needs HTTPS. With Laravel Valet, run `valet secure mofa`.

## Configuration

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Full site URL. Signed order links use it. |
| `APP_TIMEZONE` | `Africa/Cairo`. Cutoff times and delivery days depend on it. |
| `APP_LOCALE` | Default language for new visitors (`ar`) |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | The first dashboard account, created by the seeder |
| `QUEUE_CONNECTION` | `database`. The confirmation email goes through the queue. |
| `MAIL_*` | Mail server for the confirmation email. `MAIL_MAILER=log` writes the email to the log instead of sending it. |
| `CARTO_API_KEY` | Optional. Switches the map to CARTO tiles; without it, OpenStreetMap is used. |

Before taking the first order, fill in these dashboard screens:
- **Delivery → Delivery areas:** at least one area.
- **Delivery → Time slots:** two slots are created for you.
- **Ordering & payment:** the InstaPay address and the WhatsApp number.

Until these are filled in, the checkout shows "Online ordering opens soon".

## Running the shop

Keep a queue worker running so the confirmation email gets sent:

```bash
php artisan queue:work
```

In production, run the worker under Supervisor (or a similar process manager). There are no scheduled jobs.

**Previewing the email.** In the local environment, open `/_mail/confirmed?locale=ar` (or `locale=en`). It renders the email for the latest order.

**Payment receipts** stay in WhatsApp; the site does not store them.

## Tests

```bash
php artisan test
```

The feature tests cover:
- checkout (validation, server-side pricing, optional fields)
- delivery scheduling (cutoff, closed days, slot capacity)
- the order workflow and the confirmation email
- dashboard alerts and order screens
- the menu, locale routing, and nutrition calculation

## Project structure

| Path | What it holds |
| --- | --- |
| `app/Ordering/` | `Cart` (server-side pricing), `OrderPlacer`, and `OrderWorkflow`, which handles every status change |
| `app/Delivery/DeliveryScheduler.php` | Which days and slots can be booked: cutoff, days off, closed days, capacity |
| `app/Payments/` | The `PaymentMethod` interface; `InstapayManual` is the first implementation |
| `app/Notifications/` | The customer confirmation email and the dashboard alerts |
| `app/Livewire/` | `Checkout` and `OrderPage` |
| `app/Filament/` | Dashboard resources, pages (prep list, settings) and widgets |
| `app/Support/` | `Locales`, `Money` (prices in piasters), `Nutrition`, `Settings`, `ItemNote`, `BrandPalette` |
| `resources/views/components/` | Blade components; `site/` holds the header, footer, hero and story slider |
| `resources/views/pages/` | Home, menu, item, checkout and order pages |
| `resources/js/` | `cart.js` (browser cart), `location-picker.js` (map), `setup.js` (Alpine components) |
| `resources/css/app.css` | Tailwind theme built from the MOFA design system |
| `resources/css/filament/admin/theme.css` | Dashboard theme |
| `lang/ar.json` | Arabic translations; the English text is the key |

## Conventions

- **Mobile first.** Design each screen at 375px wide, then add `sm:` and `lg:` styles for larger screens.
- **RTL-safe utilities.** Use the logical ones (`ms-`, `pe-`, `start-`, `end-`), never `ml-` or `left-`. Anything that points along the reading direction gets `rtl:-scale-x-100`.
- **Latin text inside Arabic.** Wrap phone numbers, emails and handles in `dir="ltr"` so they never flip.
- **Formal Arabic.** All Arabic UI text and emails are written in Modern Standard Arabic.
- **Bilingual data.** Each translated field is stored in two columns (`name_en`, `name_ar`) and read with `$model->localized('name')`.
- **Money.** Prices are stored in piasters as integers and shown in EGP.
- **Icons.** Use `<x-mofa.icon>`. The name `<x-icon>` belongs to Filament's blade-icons package.
- **Text colours.** Text is `brown` or `ink-muted`. Terracotta, mauve and olive are for decoration only.

---

<p align="center">Baked fresh by Faten · <a href="https://www.instagram.com/mofa20261/">@mofa20261</a></p>
