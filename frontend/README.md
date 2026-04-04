# Frontend

React + TypeScript frontend for the Symfony API in the repository root.

## Local run

1. Start the backend and MariaDB:

   ```bash
   docker compose up --build
   ```

2. In a second terminal, install frontend dependencies:

   ```bash
   cd frontend
   npm install
   ```

3. Start the Vite dev server:

   ```bash
   npm run dev
   ```

4. Open the URL printed by Vite, usually:

   ```text
   http://localhost:5173
   ```

The Vite dev server proxies `/api/*` to `http://localhost:4000` by default.

## Optional environment override

If your backend runs on a different URL, create `frontend/.env.local`:

```bash
VITE_API_PROXY_TARGET=http://localhost:4000
```
