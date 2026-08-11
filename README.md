# Facilitate Care Dashboard

This project now includes a production-style **Audit Upload** feature for Visit Logs and MAR sheets:

- Vue 3 + Vite frontend page: `src/pages/AuditUpload.vue`
- Node.js + Express + TypeScript backend: `server/`
- MySQL persistence for audits and findings
- Export formats: JSON, CSV, printable HTML

## 1) Database Setup (phpMyAdmin)

1. Open phpMyAdmin.
2. Create a database (example: `facilitate`) if it does not exist.
3. Select the database, open the **SQL** tab.
4. Run the SQL in:
   - `server/db/schema.sql`
5. Confirm tables exist:
   - `audits`
   - `audit_findings`

## 2) Environment Setup

Credentials are kept out of version control. After cloning, create both config
files from their templates:

```bash
cp server/.env.example server/.env
cp src/php/env.local.example.php src/php/env.local.php
```

Then fill in real values. `server/.env` configures the Node/Express API;
`src/php/env.local.php` supplies MySQL and SMTP credentials to the PHP
endpoints under `src/php/`. Both are gitignored.

Real environment variables (Apache `SetEnv`, system env) take precedence over
`env.local.php`, so production can supply credentials without that file.

### `server/.env` keys

```env
PORT=3001
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_DATABASE=facilitate
AUDIT_OCR_ENABLED=false
AUDIT_MAX_UPLOAD_BYTES=20971520
AUDIT_MAX_TEXT_STORE=200000
```

Set `AUDIT_OCR_ENABLED=true` only if you want OCR fallback for image/scanned files.

## 3) Install Dependencies

From project root:

```bash
npm install
```

## 4) Run Client + Server Together

```bash
npm run dev
```

This starts:

- Frontend (Vite) on default Vite port (usually `5173`)
- Backend API on `http://localhost:3001`

Vite proxy is configured so frontend calls to `/api/*` route to the backend.

## 5) Audit API Endpoints

- `GET /api/health`
- `POST /api/audits` (multipart field: `document`)
  - body fields:
    - `clientName`
    - `month` (`YYYY-MM`)
    - `docTypePreferred` (`auto|visit|mar`)
    - `plannedMinutes` (optional)
- `GET /api/audits?clientName=&month=&docType=&status=&limit=&offset=`
- `GET /api/audits/:id`
- `GET /api/audits/:id/export/json`
- `GET /api/audits/:id/export/csv`
- `GET /api/audits/:id/export/html`

## 6) Frontend Route

Dashboard route name remains:

- `visitaudit`

It now loads:

- `src/pages/AuditUpload.vue`

## 7) Notes

- Uploads are saved to: `server/uploads`
- DB stores:
  - original file metadata
  - extracted text (truncated by `AUDIT_MAX_TEXT_STORE`)
  - parsed JSON
  - deterministic findings with evidence and line references
- Parsing is heuristic and never fabricates data; unclear parse results are surfaced as findings.

## 8) Local PHP Testing

When you open the Vue app on `localhost`, the PHP-backed forms and auth pages use the local PHP backend by default.

To test the real one.com PHP/email path from your local browser, open the app with:

```text
http://localhost:5173/?phpApi=live
```

To switch back to the local PHP backend:

```text
http://localhost:5173/?phpApi=local
```

The selected mode is remembered in `localStorage`. You can also set a default local mode with:

```env
VITE_LOCALHOST_PHP_API_MODE=live
```
