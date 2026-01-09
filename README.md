# h3x.to

## Getting Started
- Start the stack: `make init` (or `docker compose up -d`). The database seeds itself on first boot via the files in `db/`.
- If you already have a `postgres_data` volume and need to recreate the schema (e.g., missing `blog_posts`), run: `make reset-db`.
- Default admin seed: user `hexdigest_admin` with password `Admin!234`.
