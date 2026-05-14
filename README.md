# DECKO Size Management

A search-first warehouse dashboard for the **DECKO Warehouse SKU and Barcode list** workbook.
Type a SKU code, GTIN/EAN, internal barcode, or product name — see dimensions, weights, units-per-box, and product photos instantly.

- **Source of truth:** the master `.xlsx` workbook (kept at the project root).
- **Built for:** the DECKO ops team. Read-only catalogue. Re-import on demand.
- **Stack:** PHP 8.1+, SQLite, Tailwind via CDN, Alpine.js. No build step.

---

## What it does

- Live, debounced search across **102 SKUs / 7 categories**.
- Metric ↔ Imperial unit toggle.
- Category filters with SKU counts.
- Click any row → full detail panel with **5 photos** per SKU (product, internal barcode, GTIN/EAN barcode, box, box barcode).
- One-click CSV export of the currently filtered result.
- Admin page to upload a new workbook and re-import on the spot.
- Auth-gated. Five failed logins per email triggers a 5-minute lockout. CSRF on every state change.

---

## Quick start (local — XAMPP)

```powershell
# 1. From the project root, install PHP deps
composer install

# 2. Generate a strong APP_KEY and update .env
php -r "echo bin2hex(random_bytes(32));"

# 3. Build the database
php bin/migrate.php
php bin/seed.php

# 4. Import the workbook (drops into storage/database.sqlite)
php bin/import.php

# 5. Visit the dashboard
#    http://localhost/decko/size-management/public/
```

Sign in with the seeded admin credentials printed by `php bin/seed.php`.
**Change that password immediately** by re-running `seed.php` after editing `.env`.

### Or: skip the CLI and use the web installer

On a freshly-cloned project (no schema, no admin user), opening any URL redirects
to `/setup`. The wizard:

1. Checks the `.env` DB connection and reports the driver.
2. Runs the schema migration for SQLite or MySQL.
3. Creates the admin account from the form (name + email + password ≥12 chars).
4. Imports the uploaded `.xlsx` workbook, or falls back to `EXCEL_PATH` on disk.

Once an admin exists the route locks itself — the POST refuses to run again
to prevent accidental data wipes. Use Admin → Import to re-import later.

---

## Project structure

```
size-management/
├── public/                  ← Apache DocumentRoot (or wherever you point it)
│   ├── index.php            ← front controller
│   ├── .htaccess            ← URL rewrites + security headers
│   └── assets/
├── app/
│   ├── Controllers/         AuthController · DashboardController · ImportController
│   ├── Core/                Auth · Csrf · Database · Env · ExcelImporter
│   │                         · Paths · RichDataExtractor · Router · Session · View
│   ├── Models/              User · Sku
│   └── Views/               PHP templates (layouts, auth, dashboard, admin, errors)
├── bin/                     CLI: migrate · seed · import · reset
├── database/
│   └── schema.sql           SQLite schema (skus, categories, users, import_log)
├── storage/
│   ├── database.sqlite      not committed
│   ├── photos/              extracted SKU photos (not committed)
│   ├── uploads/             re-import drop zone
│   └── logs/                php-error.log
├── DECKO Warehouse SKU and Barcode list.xlsx   ← the source workbook
├── composer.json
├── .env / .env.example
└── README.md
```

---

## How the import works

The workbook stores most photos using Excel's **rich-data "Insert > Picture > Place in Cell"** feature (not the older `xdr:twoCellAnchor` drawing format). PHPSpreadsheet only parses the latter, so the importer uses a custom `RichDataExtractor` (in `app/Core`) that walks four XML manifests inside the .xlsx ZIP:

```
cell `vm` attribute  →  xl/metadata.xml             (1-based vm → rv index)
                    →  xl/richData/richValueRel.xml   (rv index → rId)
                    →  xl/richData/_rels/richValueRel.xml.rels   (rId → media file)
                    →  xl/media/imageN.png            (the actual bytes)
```

For each row:
1. Column 1 is treated as the category header (carried across rows until it changes).
2. Column 2 is the SKU code — non-empty values become rows in the `skus` table.
3. Columns 4, 5, 7, 16, 17 (product/barcode/box photos) are extracted and saved to `/storage/photos/<sku>__<kind>.<ext>`.
4. A denormalised `search_haystack` column is built per SKU for fast `LIKE` lookups.

Re-importing **wipes** the `skus` and `categories` tables, deletes all files in `/storage/photos`, then rebuilds from the workbook. Wrapped in a single transaction.

---

## Stack & design decisions

- **SQLite, not MySQL.** 102 SKUs and a single editor — SQLite is faster to provision, easier to back up (`cp database.sqlite`), and travels with the codebase. Schema is annotated for an eventual port to MySQL if the catalogue ever grows past a few thousand SKUs.
- **Composer + PHPSpreadsheet + Dotenv** are the only runtime PHP deps.
- **Tailwind via CDN + Alpine.js.** No npm, no build step, no toolchain to maintain. Fonts: Geist + Geist Mono. All numerics are `font-mono` with `tabular-nums`.
- **No emojis, no purple gradients, no centered hero.** Asymmetric sidebar+main with a single desaturated emerald accent (`#047857`). Off-black, never `#000`.
- **Mobile-first.** Search bar is sticky. Sidebar collapses into a drawer. Detail panel becomes a bottom sheet at `<xl`.

---

## Hosting it for the team

Three options, from easiest to "most production".

### 1) Same Windows machine, LAN only

Already done. Anyone on the same Wi-Fi can hit `http://<your-IP>/decko/size-management/public/`. Open Windows Firewall for port 80 if needed.

### 2) Cheap shared host (Hostinger, Namecheap, Bluehost…)

Requirements: **PHP ≥ 8.1, `pdo_sqlite`, `zip`, `gd`, `mbstring`**. Most hosts have these.

```bash
# On your local machine, zip the project (skip /vendor and /storage/database.sqlite)
zip -r decko-size.zip . -x 'vendor/*' 'storage/database.sqlite*' '*.bak' 'node_modules/*'

# Upload via FTP/cPanel. SSH in.
composer install --no-dev --optimize-autoloader

# Update .env on the server: APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain.com
php bin/migrate.php
php bin/seed.php
php bin/import.php

# Point your DocumentRoot to /public/ (cPanel: Domains → set "Document Root").
```

Make sure `/storage/` is writable (`chmod -R 775 storage`).

### 3) A small VPS (DigitalOcean droplet, $6/mo)

- Ubuntu 22.04 + Nginx + PHP-FPM 8.2 + Certbot for HTTPS.
- Document root → `/var/www/decko-size/public`.
- Set `SESSION_SECURE=true` in `.env` once HTTPS is wired.
- Uncomment the `Strict-Transport-Security` header in `public/.htaccess` (and add the equivalent in the Nginx config).

---

## Operational notes

- **Re-import workflow:** Admin → Re-import. Old workbook is auto-backed up to `*.YYYYMMDD-HHMMSS.bak`. The DB transaction means a failed import rolls back cleanly.
- **Backups:** `cp storage/database.sqlite backups/database.sqlite.$(date +%F)`. That's it. The workbook is your other source of truth — keep both.
- **Search performance:** `search_haystack` is indexed; queries are `LIKE %term%`. At 102 rows this completes in <1 ms. If the catalogue ever reaches 50k+ rows, swap to SQLite FTS5.
- **Sessions:** 8-hour lifetime by default. Edit `SESSION_LIFETIME_MINUTES` in `.env`.
- **Logs:** `storage/logs/php-error.log`.

---

## Troubleshooting

- **"Import failed: Excel source not found"** — set `EXCEL_PATH` in `.env`, or pass it on the CLI: `php bin/import.php "C:\path\to\workbook.xlsx"`.
- **Photos appear blank** — the dashboard expects images at `/storage/photos`. Re-run `php bin/import.php`. The importer logs how many photos it wrote.
- **MySQL on this XAMPP is broken (Aria recovery failed)** — unrelated to this project (we use SQLite). To fix the sibling `decko-management` Laravel project: stop XAMPP, delete `C:\xampp\mysql\data\aria_log.*`, restart. A clean Aria log will rebuild on first start.

---

## License

Proprietary — DECKO internal use only.
