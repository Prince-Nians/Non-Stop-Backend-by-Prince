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

The repo's `render.yaml` sets everything up on Render's **free** plan, with **no cloud storage**:
uploaded images and videos are kept in the database (`PUBLIC_DISK_DRIVER=database`) and served by this API at `/media/...`.

1. **Render → New → Blueprint** → pick this repo → **Apply**. It creates the Postgres database `nonstop-db` and the web service `nonstop-backend`. Nothing needs to be typed in: the app key is derived from a generated seed, `APP_URL` defaults to the service address, and mail goes to the log.
2. On first start the container runs the migrations and loads the demo content (text, images, 4 reels) **only if the database is empty**, so a redeploy never overwrites edits. Set `FORCE_SEED=true` once to reload the defaults.
3. Check it: `https://<service>.onrender.com/up`, `/api/v1/home` (image links should be `.../media/...`).
4. Copy the database's **Internal Database URL** (nonstop-db → Connections) for the CMS.

Free-tier notes: the service sleeps after ~15 idle minutes (the first request then takes 30–60 s, and images load slowly until it is awake); the free Postgres is deleted after 30 days. Everything is stored in that one database (~20 MB for the demo).

### Going beyond a demo
Database storage suits demos and small sites. For real traffic use any S3-compatible bucket (Cloudflare R2, AWS S3, Backblaze B2…) on **both** apps, with the same values:

```
PUBLIC_DISK_DRIVER=s3
AWS_BUCKET=… AWS_ACCESS_KEY_ID=… AWS_SECRET_ACCESS_KEY=… AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com   # empty for AWS S3
AWS_URL=https://<public url of the bucket / CDN>
```

Use a paid Render instance so the API does not sleep. The bucket must be publicly readable (no per-file ACLs are used).
