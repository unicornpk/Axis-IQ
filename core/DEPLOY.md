# Deploying Axis IQ Core to shared hosting (no shell access)

This covers deploying to `unicornaxis.com/axisiq`, a cPanel/LiteSpeed host with **no SSH/terminal access** (confirmed with the owner) — FTP/File Manager upload only, and `php artisan` commands can't be run directly.

## Why the files are split into two folders

A Laravel app normally has one `public/` folder as the web root and everything else (`app/`, `vendor/`, `.env`, …) one level above it, outside the web root. On a host where you can't set a custom document root, "one level above a web-accessible folder" doesn't exist — so this package splits into:

- **`axisiq/`** — goes at `unicornaxis.com/axisiq`. This is what `public/` used to be: `index.php`, `.htaccess`, and (temporarily) `install.php`. Web-accessible, on purpose.
- **`axisiq-app/`** — goes as a **sibling** of `axisiq/` (same parent directory, same level — NOT inside it). Everything else: `app/`, `bootstrap/`, `config/`, `vendor/`, `.env`. Must **never** be reachable by URL — it has its own `.htaccess` denying all requests as a backstop, but the real protection is that it simply shouldn't be inside any web-accessible directory.

`axisiq/index.php` is the one file changed from Laravel's default — its `require` paths point at `../axisiq-app/vendor/...` and `../axisiq-app/bootstrap/...` instead of the default `../vendor`/`../bootstrap`.

## 1. Upload

Upload **both** `axisiq/` and `axisiq-app/` so they end up as siblings — e.g. if `axisiq/` is at `/home/<cpaneluser>/public_html/axisiq/`, then `axisiq-app/` goes at `/home/<cpaneluser>/public_html/axisiq-app/`, next to it, not inside it.

**Upload via a zip + cPanel File Manager "Extract" is strongly preferred over plain FTP file-by-file** — several directories (`storage/framework/sessions`, `bootstrap/cache`, etc.) are empty except for a `.gitkeep` placeholder, and plain FTP sometimes drops empty folders; zip extraction doesn't.

## 2. Configure `.env`

`axisiq-app/.env` ships with some values pre-filled (see `deploy/.env.production.example` for the annotated template) — fill in anything left blank:

| Variable | Where it comes from |
|---|---|
| `IQ_ANTHROPIC_API_KEY` | console.anthropic.com |
| `IQ_FIELD_API_TOKEN` | AxisField → Settings → "Axis IQ API token" |
| `APP_URL` | should already read `https://unicornaxis.com/axisiq` |

`APP_KEY`, `IQ_CALLER_TOKEN`, and `IQ_INSTALL_CODE` are pre-generated per deployment — don't reuse them across environments. **`IQ_CALLER_TOKEN`'s value also needs to go into AxisField's own Settings → "Axis IQ assistant" → "Access secret"**, or AxisField's proxy will get `401`s.

## 3. Set the PHP version and extensions

In cPanel → MultiPHP Manager (or Select PHP Version), set **PHP 8.3+** for the `axisiq` (and `axisiq-app`, if it lets you set it per-folder) path. Laravel 13 requires it.

**If the per-domain selector is locked** ("Site Isolation denied" or similar — happens on some cPanel configs), the account-wide default PHP version applies instead, which may be older. Fix: add this line at the very top of `axisiq/.htaccess` (adjust the version suffix to whatever's actually available — check cPanel's PHP selector for the exact handler name):
```apache
AddHandler application/x-httpd-alt-php83___lsphp .php
```
Confirm it worked by temporarily visiting `axisiq/install.php` (it'll fail fast with a clear PHP-version error if still on the wrong version) rather than guessing.

**Required extensions** (cPanel → Select PHP Version → Extensions, account-wide): `pdo_sqlite` (we default to SQLite — `pdo_mysql` instead if you switch, see `.env`), `mbstring`, `xml`, `fileinfo`, `openssl`, `tokenizer`, `ctype`, `curl`.

## 4. Run the one-time install (migrate the database)

Visit:
```
https://unicornaxis.com/axisiq/install.php?code=<the IQ_INSTALL_CODE value from .env>
```
It bootstraps Laravel and runs `php artisan migrate --force` over HTTP, since there's no shell to run it directly. You should see `Migration exit code: 0` and a list of tables created.

**Then delete `axisiq/install.php` from the server immediately.** It's code-gated, but there's no reason to leave a migration-runner reachable by URL once you've used it.

## 5. Verify

```
curl https://unicornaxis.com/axisiq/up
```
should return `200`. Then a real query:
```
curl -X POST https://unicornaxis.com/axisiq/api/query \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <IQ_CALLER_TOKEN>" \
  -d '{"query":"how many technicians are working today","product":"field"}'
```
should return a real answer, not an error.

## 6. Point AxisField at it

In AxisField → Settings → "Axis IQ assistant": set the address to `https://unicornaxis.com/axisiq` and the Access secret to the same `IQ_CALLER_TOKEN` value from step 2.

## Notes on the package itself

- **`axisiq-app/` placement.** It ships as a sibling of `axisiq/` with its own deny-all `.htaccess` as a backstop. If your account lets you, it's strictly safer to place it **entirely outside the web root** instead (e.g. `/home/<cpaneluser>/axisiq-core`, not under `public_html/` at all) — the `.htaccess` deny-all still applies either way, this just removes the "what if `.htaccess` doesn't get honored" risk entirely. Either placement works with the package as shipped; `axisiq/index.php`'s `../axisiq-app/...` paths assume sibling placement, so if you move it outside `public_html/` entirely, update those two paths in `index.php` to match (e.g. `../../axisiq-core/vendor/autoload.php`).
- **`axisiq/index.php` calls `$app->usePublicPath(__DIR__)`** after bootstrapping — without it, any Laravel helper that resolves `public_path()` (e.g. `filemtime(public_path(...))` inside the framework) looks for a `public/` folder that doesn't exist in this split layout and fails.
- **`axisiq/.user.ini` raises `max_execution_time` to 180s.** Claude's tool-calling loop can make several round trips (Anthropic + a connector call, each with their own timeout) before answering — a shared host's default 30-60s limit can kill a legitimate slow request mid-flight.
- **Don't name a file or folder under `axisiq/` the same as a route** (e.g. a literal folder called `api`). Apache's rewrite skips existing real files/directories, so it would shadow the route instead of reaching `index.php`.
- **PHP-FPM/LSAPI session note:** `install.php`'s install route needs no session and works even before the `sessions` table (if you ever switch `SESSION_DRIVER` to `database`) exists — keep `SESSION_DRIVER=file` (the shipped default) unless you have a specific reason to change it.

## Troubleshooting

- **500 error, nothing else to go on** → check `axisiq-app/storage/logs/laravel.log`. Usually a file-permission issue: `storage/` and `bootstrap/cache/` need to be writable by the web server (normally automatic on cPanel since PHP runs as the account owner via LSAPI — if not, chmod them 775 via File Manager).
- **`install.php` says "Forbidden"** → the `?code=` doesn't match `IQ_INSTALL_CODE` in `.env` exactly, or `.env` isn't being read (check it's at `axisiq-app/.env`, not `axisiq-app/.env.example` or similarly misnamed).
- **AxisField gets 401 from Core** → `IQ_CALLER_TOKEN` in Core's `.env` doesn't match the "Access secret" value in AxisField Settings. They must be byte-for-byte identical.
- **Database errors after install** → confirm `axisiq-app/database/database.sqlite` exists and `axisiq-app/database/` is writable (SQLite needs to write to the directory, not just the file, to create its journal).

## Rebuilding the package after a code change

From a dev checkout with Composer available:
```
./build-deploy.sh /path/to/some/output/dir
```
Produces fresh `axisiq/` and `axisiq-app/` folders (see `build-deploy.sh` — it runs `composer install --no-dev --optimize-autoloader`, strips `.git` dirs composer sometimes leaves in `vendor/`, and wires up the split-folder paths). Zip and upload the same way as above — `.env` on the server is untouched by a redeploy unless you overwrite it, so don't re-upload `axisiq-app/.env` unless you mean to reset config.
