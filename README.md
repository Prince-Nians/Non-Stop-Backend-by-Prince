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

## Deploy on Render

1. **Render → New → Blueprint** → choose this repo. `render.yaml` creates the Postgres database and the API service (Docker).
2. Fill the values marked `sync: false`: `APP_KEY` (`php artisan key:generate --show`), `APP_URL`, `FRONTEND_URL` (your Vercel domain), the storage variables (below) and, optionally, the `MAIL_*` SMTP details.
3. On start the container runs migrations (`RUN_MIGRATIONS=true`) and loads the default homepage content **only if the database is empty** (`RUN_SEED=true`). Editors' content is never overwritten; set `FORCE_SEED=true` once if you really want to reload the defaults.
4. Use at least the **Starter** instance: free instances sleep, and the website gives up after 5 s and shows its bundled fallback.

### File storage (required in production)
The CMS uploads images/videos and this API serves their URLs, so both must use one shared S3-compatible bucket
(Cloudflare R2, AWS S3, Backblaze B2, DigitalOcean Spaces…). Set the same values on **both** services:

```
PUBLIC_DISK_DRIVER=s3
AWS_BUCKET=… AWS_ACCESS_KEY_ID=… AWS_SECRET_ACCESS_KEY=… AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com   # empty for AWS S3
AWS_URL=https://<public url of the bucket / CDN>
```

The bucket (or its public URL / CDN) must be publicly readable; no per-file ACLs are used. Reel videos are uploaded through the CMS (Homepage → Social photos).
Switching provider later only means changing these variables (and copying the files across).
