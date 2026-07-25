# Production Deployment

Updated: 2026-03-22

## Recommended shape

For production, run one container for the Symfony + React app and connect it to an existing MariaDB with the CQRLOG schema.

This repository now supports that flow directly:

- the Docker image builds the React frontend from `/frontend`
- the built frontend is copied into `/public`
- Apache serves the frontend and Symfony API from the same origin
- frontend API calls continue to use `/api/*`, so no Vite server is needed in production

## Requirements

Before deployment, make sure you have:

- a reachable MariaDB instance with the required CQRLOG tables already present
- a stable `APP_SECRET`
- outbound HTTPS access from the app container to `www.hamqth.com` for `/api/dxcc`
- an Apache2 reverse proxy in front of the container if you publish the app from the server itself

Important:

- this app does not create the CQRLOG schema for you
- the development `docker-compose.yml` imports SQL dumps for local work only
- for production, it is better to connect to the real database instead of importing bundled dump files into a fresh container

## Deploy steps

1. Create a production env file from the example:

   ```bash
   cp .env.prod.example .env.prod
   ```

2. Fill in at least:

   - `APP_SECRET`
   - `DEFAULT_URI`
   - `LOGIN_USERNAME`
   - `LOGIN_PASSWORD`
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASSWORD`

3. Build and start the production stack:

   ```bash
   docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
   ```

4. Verify the deployment:

   ```bash
   curl -fsS http://127.0.0.1:8080/api/health
   ```

5. Put Apache2 in front of port `8080` and publish your real hostname there.

## Apache2 reverse proxy

The app container listens on port `80` internally and on `${APP_PORT}` externally, default `8080`.

Typical production setup:

- `http://log.example.com/` -> frontend
- `http://log.example.com/api/*` -> same container

Because frontend and API share one origin, you do not need CORS configuration for the normal deployment shape.

A sample Apache2 site config without SSL is available in [apache2-site.conf](/Users/petr/Projects/private/con/docs/apache2-site.conf).
That file intentionally leaves TLS out so you can add certificates later via certbot on the server.

## Minimal access protection

Recommended production shape:

- use Basic Auth in the front Apache2 reverse proxy
- keep the app container itself without Basic Auth
- `/api/health` stays public for simple health checks

Optional:

- the app has a built-in login form
- it is enabled only when both `LOGIN_USERNAME` and `LOGIN_PASSWORD` are set
- when enabled, all API endpoints (except `/api/health`) require a valid session
- sessions are created via the login form at the application level

This is intentionally minimal and suitable mainly for a private or small-team deployment.

## Updates

For a new release:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

## Security checklist / secret rotation

Images built before the `.dockerignore` fix (see repository history) bake
`.env.prod` and SQL dumps into their layers. If you have ever deployed such
an image, treat every secret it could have exposed as compromised and
rotate:

1. Generate a fresh `APP_SECRET` and put it in `.env.prod`:

   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```

2. Rotate the production database password, both on the MariaDB server
   and in `DB_PASSWORD` in `.env.prod`.

3. Rotate `LOGIN_PASSWORD` in `.env.prod`.

4. Rebuild and redeploy with the new secrets:

   ```bash
   make prod
   ```

5. Purge every previously built image — each one built before this fix
   contains `.env.prod` and the SQL dumps in its layers, even if the
   running container has since been replaced. Remove local copies
   (`docker image rm`) and purge any copies pushed to a registry or kept
   in backups.

6. Confirm no env file or SQL dump was ever committed to the repository:

   ```bash
   git log --all --oneline -- .env.prod .env.local cqrlog002-data.sql
   ```

   This should print nothing.

7. Rotating `APP_SECRET` and `LOGIN_PASSWORD` invalidates all active
   sessions; every logged-in user will need to log in again.

## Operational notes

- `GET /api/health` is the simplest smoke test.
- First request after restart may be a little slower while Symfony warms runtime cache.
- If HamQTH is unreachable, `/api/dxcc` will return `502`.
- Back up the MariaDB database, not just the container image.
