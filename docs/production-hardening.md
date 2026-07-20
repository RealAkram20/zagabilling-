# Production Hardening Checklist

Hand this to whoever manages the production server. It covers the security
items from the July 2026 audit that **cannot be fixed in application code** —
they live in the production `.env`, the web server config, or the database.

The application-code fixes are already merged on the
`portal-arrears-and-device-identity` branch. This document is the rest.

Legend: 🔴 do before the app is reachable from the internet · 🟠 soon after · 🟢 hardening.

> **Preflight gate:** after applying these, run `./scripts/go-live.sh --check-only`
> on the production box. It fails if any 🔴 security setting is still unsafe and
> lists what's left. Run it without `--check-only` to also build production
> caches and migrate. It never sets or prints secrets.

---

## 🔴 1. Turn off debug output

**Risk:** With `APP_DEBUG=true`, any unhandled error renders a stack-trace page
that dumps `APP_KEY`, DB credentials, the PesaPal secret, and the SMTP password.

In the production `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

Then:

```bash
php artisan config:cache
```

**Verify:** trigger a 404/500 and confirm you get a plain error page, not a
stack trace. (`.env.example` now ships these safe defaults.)

---

## 🔴 2. Stop running the database as root

**Risk:** `DB_USERNAME=root` with an empty `DB_PASSWORD` means any SQL foothold —
or a leaked stack trace — lands as database root over the entire billing ledger.

Create a least-privilege user scoped to the app's database:

```sql
CREATE USER 'zaga'@'localhost' IDENTIFIED BY '<a long random password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON zaga.* TO 'zaga'@'localhost';
FLUSH PRIVILEGES;
```

In the production `.env`:

```dotenv
DB_USERNAME=zaga
DB_PASSWORD=<the strong password>
```

**Verify:** the app runs; `SHOW GRANTS FOR 'zaga'@'localhost';` lists no
`ALL PRIVILEGES` and no `WITH GRANT OPTION`.

---

## 🔴 3. Confirm `APP_KEY` is set and the live PesaPal gateway is configured

- `php artisan key:generate` must have been run — an empty `APP_KEY` breaks
  encryption and lets sessions be forged.
- **The payment gateway must have live PesaPal credentials in production.** The
  app now *fails closed* when the gateway is unconfigured (no more free
  "simulated" unlocks), so an unconfigured production gateway means customers
  simply cannot pay. Set the consumer key/secret under Settings → Gateway and
  run the "Test connection" and "Register IPN" actions.

---

## 🟠 4. Serve from `public/`, not the app root

**Risk:** XAMPP serves `htdocs/zagatech` directly. Only a `mod_rewrite` rule
keeps `/.env`, `/.git`, `/storage/logs/…` off the web. If rewrite is ever
disabled, the whole app root is downloadable.

Point Apache's `DocumentRoot` at the `public/` directory:

```apache
DocumentRoot "C:/xampp/htdocs/zagatech/public"
<Directory "C:/xampp/htdocs/zagatech/public">
    AllowOverride All
    Require all granted
</Directory>
```

The root `.htaccess` has been hardened as a backstop (`Options -Indexes` and a
dotfile deny), but pointing the docroot at `public/` is the real fix.

**Verify:** `https://<host>/.env` and `https://<host>/composer.json` return
403/404, not file contents.

---

## 🟠 5. Force HTTPS and the Secure cookie flag

Serve the site over TLS, then in the production `.env`:

```dotenv
SESSION_SECURE_COOKIE=true
APP_URL=https://<your-domain>
```

**Verify:** the `zaga_session` cookie shows the `Secure` flag in browser dev
tools. (`HttpOnly` and `SameSite=Lax` are already set in `config/session.php`.)

---

## 🟠 6. Set the security allowlists

Now that these are env-driven, set them in production:

```dotenv
# Only these origins may call /api/* from a browser (native device clients are
# unaffected — CORS is browser-only).
CORS_ALLOWED_ORIGINS=https://<your-domain>

# Only these SMTP hosts may be configured from the settings UI.
MAIL_ALLOWED_HOSTS=smtp.your-provider.com
```

---

## 🟢 7. Rotate the seeded credentials

The database seeder creates `admin@zaga.local` / `password`. If any environment
was ever seeded:

- Change that password immediately (or delete the account once a real
  super-admin exists).
- Never run `db:seed` against production.

---

## Items that need a coordinated change (not a server setting)

These are tracked but deliberately **not** done yet because they reach beyond
the server config:

- **Upgrade off Laravel 9** — it is end-of-life (no security patches since Feb
  2024). Needs its own branch, dependency bumps, and a full regression pass.
- **Device API token expiry** (`SANCTUM_EXPIRATION`) and **HMAC-secret
  rotation** — both require the offline C++ device client to handle
  re-authentication / rotation first, or field devices will be cut off. Leave
  `SANCTUM_EXPIRATION` blank until the client is ready.
- **`AuthenticateSession` middleware** — needed for "log out other devices on
  password change" to fully take effect. Adding it logs everyone out once on
  deploy, so schedule it deliberately.

---

_Generated as part of the July 2026 security remediation. Application-code
fixes: see commits on `portal-arrears-and-device-identity`._
