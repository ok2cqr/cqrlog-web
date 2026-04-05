# CQRLOG Web

Symfony backend and React frontend for a CQRLOG-style web UI. The backend preserves and normalizes some legacy field names used by the original desktop application. A few of those names are more than 20 years old and are somewhat obsolete today, but they are kept for compatibility with the existing data model. Most of the code was created with help from OpenAI Codex.

## Stack

- PHP 8.2+ / Symfony 7
- React + TypeScript + Vite
- MariaDB
- Docker Compose for local development and production packaging

## Development

### Requirements

- Docker with Compose
- Node.js 20+ and npm

### Start the local stack

Start the Symfony app and MariaDB:

```bash
make dev
```

This starts:

- app on `http://localhost:4000`
- MariaDB on `localhost:23306`

The development database is initialized from [`schema.sql`](/Users/petr/Projects/private/con/schema.sql).

### Start the frontend dev server

In a second terminal:

```bash
cd frontend
npm install
npm run dev
```

Then open the URL printed by Vite, usually:

```text
http://localhost:5173
```

The Vite dev server proxies `/api/*` to the Symfony app running on port `4000`.

Radio sync expects a JSON API compatible with [ok2cqr/pico-radio-gateway](https://github.com/ok2cqr/pico-radio-gateway).

### Useful development commands

```bash
make dev-down
make dev-logs
make test
```

- `make dev-down` stops the local Docker stack
- `make dev-logs` tails development logs
- `make test` resets the local test database and runs backend PHPUnit tests

## Production

### Production shape

Production uses one Docker image for the Symfony app and built frontend. The container expects an existing MariaDB database with the required CQRLOG schema.

The production container:

- builds the frontend from `/frontend`
- copies the built assets into `/public`
- serves frontend and API from the same origin

### Requirements

- Docker with Compose
- reachable MariaDB instance with the required schema already imported
- valid production values for `APP_SECRET`, database access, and app URL
- outbound HTTPS access to `www.hamqth.com` for DXCC lookups

### Configure production environment

Create the production env file:

```bash
cp .env.prod.example .env.prod
```

Fill in at least:

- `APP_SECRET`
- `APP_PORT`
- `DEFAULT_URI`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

`APP_PORT` is the host port published by Docker for the app container, for example `8080`.

`DEFAULT_URI` should be the public base URL of the app, for example `https://log.example.com`. Symfony uses it as the default host and scheme when generating absolute URLs outside a normal HTTP request.

The `DB_*` values should point to the same MariaDB database used by the desktop CQRLOG application.

Optional:

- `BASIC_AUTH_USERNAME`
- `BASIC_AUTH_PASSWORD`
- `FRONTEND_RADIO_SYNC_DEFAULT_URL`
- `FRONTEND_RADIO_SYNC_DEFAULT_POLL_INTERVAL_SECONDS`

Radio sync expects a JSON API compatible with [ok2cqr/pico-radio-gateway](https://github.com/ok2cqr/pico-radio-gateway).

### Build and start production

```bash
make prod
```

This is equivalent to:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

Useful production commands:

```bash
make deploy
make prod-logs
make prod-down
make prod-config
```

### Verify production

Check the container:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml ps
```

Check the health endpoint:

```bash
curl -fsS http://127.0.0.1:8080/api/health
```

If you run the app behind Apache or another reverse proxy, publish your public hostname there and proxy traffic to `127.0.0.1:8080`.

Example:

```apacheconf
<VirtualHost *:80>
    ServerName log.yourdomain.com

    ProxyPreserveHost On
    ProxyRequests Off

    ProxyPass / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/
</VirtualHost>
```

## Notes

- `.env.prod` is ignored by Git and should stay local to the server.
- The app does not create the production schema for you.
- Back up the MariaDB database, not only the container image.
- If HamQTH is unreachable, `/api/dxcc` will return `502`.
