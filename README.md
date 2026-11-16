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

## Deploy on Render (free demo)

Everything runs on free tiers with **no cloud storage**: images/videos are kept in the database (`PUBLIC_DISK_DRIVER=database`) and served by this API at `/media/...`.

**1. A free Postgres database: Neon**
- Sign up at neon.tech (no card). Create a project (name `nonstop`, pick the region closest to your Render region).
- In **Connection details** untick *Pooled connection* and copy the string: `postgresql://USER:PASSWORD@ep-xxxx.REGION.aws.neon.tech/neondb?sslmode=require`.
- Use this same string as `DB_URL` for **both** the backend and the CMS.

**2. Render**: **New → Blueprint** → this repo. Paste the Neon string into `DB_URL` and **Apply**. Nothing else is asked: the app key is derived from a generated seed, `APP_URL` defaults to the service address, mail goes to the log.

On first start the container creates the tables and loads the demo content (text, images, 4 reels) **only if the database is empty**, so a redeploy never overwrites edits (`FORCE_SEED=true` reloads the defaults).
Check `https://<service>.onrender.com/up` and `/api/v1/home` (image links should be `.../media/...`).

Free-tier notes: the service sleeps after ~15 idle minutes (first request 30–60 s; images load slowly until it is awake); Neon's free database also sleeps when idle and wakes in about a second. Everything uses roughly 20 MB of the free 0.5 GB.

### Going beyond a demo
Database storage suits demos and small sites. For real traffic use any S3-compatible bucket (Cloudflare R2, AWS S3, Backblaze B2…) on **both** apps, with the same values:

```
PUBLIC_DISK_DRIVER=s3
AWS_BUCKET=… AWS_ACCESS_KEY_ID=… AWS_SECRET_ACCESS_KEY=… AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com   # empty for AWS S3
AWS_URL=https://<public url of the bucket / CDN>
```

Use a paid Render instance so the API does not sleep. The bucket must be publicly readable (no per-file ACLs are used).
