# MOFA — Made Of Faten

Pre-order website for MOFA, sourdough & bagels. Arabic and English, mobile first.

**Stack:** Laravel 13 · Livewire 4 · Filament 5 · Tailwind CSS 4 · Alpine.js · MySQL

## First-time setup

Requires PHP 8.3+ (8.4 recommended), Composer, Node 20+ and MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the database, then set the admin login in `.env`:

```bash
mysql -u root -e "CREATE DATABASE mofa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```dotenv
ADMIN_NAME=Faten
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=choose-a-strong-password
```

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Optional demo item (the Sunrise Roast from Instagram, price left at 0):

```bash
php artisan db:seed --class=MenuDemoSeeder
```

- Site: http://localhost:8000 (redirects to `/ar` or `/en`)
- Admin: http://localhost:8000/admin (English by default; switch to Arabic from the user menu)

For live reload while working: `npm run dev` in a second terminal.

## Tests

```bash
php artisan test
```

## How the project is organised

| Where | What |
| --- | --- |
| `resources/css/app.css` | Tailwind theme built from the MOFA design system: colors, fonts, radii, and the `btn`, `tag`, `brush`, `ornament` classes |
| `resources/views/components/` | Blade components: `button`, `tag`, `ornament`, `section-title`, `wave-frame`, `flower`, and `mofa.icon` (not `x-icon`: Filament's blade-icons package owns that name) |
| `resources/views/components/site/` | Header, footer, mobile bottom bar, and the hero |
| `resources/views/components/layouts/app.blade.php` | The page layout: `lang` and `dir`, meta tags, hreflang |
| `resources/views/pages/` | Page views |
| `lang/ar.json` | Arabic translations. English text is the key |
| `app/Support/Locales.php` | Supported locales, RTL, and the visitor's preferred language |
| `app/Http/Middleware/SetLocale.php` | Applies the `/{locale}` prefix and remembers the choice |
| `app/Providers/Filament/AdminPanelProvider.php` | The admin panel at `/admin`: MOFA theme, English by default, language switch |
| `resources/css/filament/admin/theme.css` | The dashboard theme: fonts, surfaces and shapes from the design system |
| `app/Filament/Resources/` | Dashboard screens: Categories, Items, Ingredients (the "Menu" group) |
| `app/Models/` | `Category`, `Item`, `Ingredient`, `ItemIngredient` (an ingredient line with its amount) |
| `app/Support/Nutrition.php` | Calories from protein × 4 + carbs × 4 + fat × 9 |
| `app/Support/Money.php` | Prices are stored in piasters and shown in EGP |

## Conventions

- **Mobile first.** Design every screen at 375px, then scale up with `sm:`/`lg:`.
- **RTL.** Use logical utilities (`ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`), never `ml-`/`left-`. Anything that points along the reading direction gets `flip-rtl` or `rtl:-scale-x-100`.
- **Latin inside Arabic.** Wrap handles, emails and codes in `<span dir="ltr">` so they never flip.
- **Text colors.** Text is `brown` or `ink-muted` only. Terracotta, mauve and olive are decorative and never used for text on cream.
- **Bilingual data.** Translated fields are two columns, `name_en` and `name_ar`; read them with `$model->localized('name')`.
- **Calories are never typed in.** `Item` recalculates them from the macros on every save.
- **Icons.** Use `<x-mofa.icon>`. `<x-icon>` belongs to Filament's blade-icons package.
- **Fonts.** Self-hosted via `@fontsource`. Each family pairs an English face with an Arabic one (Jost/Tajawal, Cormorant Garamond/Amiri, Bagel Fat One/Lalezar, Caveat/Aref Ruqaa). The Arabic faces are awaiting Faten's approval.

## Placeholders to replace

- `public/images/hero/faten.*` — cropped from an Instagram post; replace with the original high-resolution portrait (portrait orientation, at least 1600px tall).
- `public/images/brand/*` and `public/favicon.ico` — cut from a small PNG; replace with SVG or large transparent PNG exports.

## Phases

1. **Foundation** ← done: theme, components, layout, Arabic/English + RTL, home page with Hero A, admin panel.
2. **Menu** ← done: categories, items, nutrition (calories automatic), ingredients, menu and item pages, dashboard screens and theme.
3. **Cart and pre-ordering** ← done: browser cart, 3-step checkout (Livewire), `App\Delivery\DeliveryScheduler`, delivery areas, time slots, closed days, settings.
4. **Orders and payments** ← done: InstaPay with receipt upload, signed order page, order screens, prep list, customers, dashboard widgets.
5. **Notifications** ← done: customer emails (Arabic/English) and dashboard alerts. WhatsApp can be added later as a channel.
6. Quality and launch.

## Running the shop

Besides `php artisan serve`, the queue worker sends the emails:

```bash
php artisan queue:work
```

On the server, run it with Supervisor. There are no scheduled jobs: unpaid orders are never cancelled automatically.

**How payment works.** The customer transfers with InstaPay and sends the receipt on WhatsApp (the button on their order page opens the chat with the order number written). Faten checks it and presses **Confirm payment** on the order. An unpaid order holds its place in the day until she confirms or cancels it.

Before the first order, fill in the dashboard: **Delivery → Delivery areas** (at least one), **Time slots** (two are created for you), and **Ordering & payment** (the InstaPay address or phone, and the WhatsApp number for receipts). Until then the checkout shows "Online ordering opens soon".

| Where | What |
| --- | --- |
| `app/Delivery/DeliveryScheduler.php` | Which days and slots can be booked (cutoff, days off, closed days, capacity) |
| `app/Ordering/` | `Cart` (server-side pricing), `OrderPlacer`, `OrderWorkflow` (every status change) |
| `app/Payments/` | `PaymentMethod` interface; `InstapayManual` is the first one |
| `app/Notifications/` | Customer emails and dashboard alerts |
| `app/Support/Settings.php` | Shop settings with their defaults |
| `resources/js/cart.js` | The browser cart (localStorage) |
| `resources/js/app.js` / `livewire.js` | Plain pages load Alpine alone; checkout and order pages load Livewire's bundle |

Payment receipts are stored on the private `local` disk and only shown to signed-in admins.
