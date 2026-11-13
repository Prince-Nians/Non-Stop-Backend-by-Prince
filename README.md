# Non Stop — Backend (Laravel API)

Public JSON API, database schema and seed content for the Non Stop website.

```
nonstop_webapp  (Next.js site)  ── GET /api/v1/... ──►  nonstop_backend  (this repo)
nonstop_cms     (Filament admin) ── writes content ──►  PostgreSQL  ◄── reads ── nonstop_backend
```

This repo **owns the database**: migrations, models, seeders. The CMS app (`nonstop_cms`) connects to the same database and does not run migrations.

## Setup

```bash
composer install
cp .env.example .env          # set DB_* (PostgreSQL), APP_URL, FRONTEND_URL
php artisan key:generate
php artisan migrate
php artisan db:seed           # loads the homepage content + converts images to WebP
php artisan storage:link
php artisan serve --port=8000
php artisan test
```

`db:seed` reads Figma images from `SEED_ASSETS_PATH` (defaults to `../nonstop/public/figma`).

## API (`/api/v1`)

| Method | Path | Notes |
|---|---|---|
| GET | `/home` | Everything the homepage needs in one call |
| GET | `/settings`, `/products` | |
| GET | `/events`, `/events/{slug}` | Paginated list, detail |
| GET | `/blogs`, `/blogs/{slug}` | Paginated list, detail |
| POST | `/contact`, `/partner` | Saved to `enquiries`, emailed to the notification address. Rate limited, honeypot field `website` |

## Shared with the CMS

- **Database:** both apps use the same PostgreSQL database. A schema change is a migration here, plus a matching model/resource update in `nonstop_cms` (and a refresh of its `tests/schema` copy).
- **Images:** the CMS uploads to the `public` disk, this app serves them. In production both must point at the same S3/R2 bucket (locally the CMS sets `PUBLIC_DISK_ROOT` to this repo's `storage/app/public`).
