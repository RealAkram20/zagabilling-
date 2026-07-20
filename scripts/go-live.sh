#!/usr/bin/env bash
#
# go-live.sh — production preflight gate + build for the Zaga portal.
#
# It refuses to build for production while any security blocker from the
# July 2026 audit is still present in .env, then runs the safe production
# build steps. It NEVER sets or prints secrets — you configure those yourself.
#
# Usage:
#   ./scripts/go-live.sh              # check, then build if all checks pass
#   ./scripts/go-live.sh --check-only # only run the preflight checks
#
# Override the PHP/Composer binaries if they aren't on PATH:
#   PHP_BIN=/c/xampp/php/php.exe COMPOSER_BIN=composer ./scripts/go-live.sh
#
set -uo pipefail
cd "$(dirname "$0")/.." || exit 2

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
CHECK_ONLY=0
[ "${1:-}" = "--check-only" ] && CHECK_ONLY=1

red()   { printf '\033[31m%s\033[0m\n' "$1"; }
green() { printf '\033[32m%s\033[0m\n' "$1"; }
yellow(){ printf '\033[33m%s\033[0m\n' "$1"; }
bold()  { printf '\033[1m%s\033[0m\n' "$1"; }

ERRORS=0
WARNINGS=0
fail() { red   "  ✗ $1"; ERRORS=$((ERRORS + 1)); }
warn() { yellow "  ! $1"; WARNINGS=$((WARNINGS + 1)); }
ok()   { green "  ✓ $1"; }

if [ ! -f .env ]; then
    red "No .env file found. Copy .env.example and configure it first."
    exit 2
fi

# Read a raw value from .env (strips surrounding quotes; empty if unset).
env_val() {
    grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//; s/[[:space:]]*$//'
}

bold "Zaga portal — production preflight"
echo

# ---- Hard security gates (block the build) --------------------------------
bold "Security gates"

[ -n "$(env_val APP_KEY)" ] && ok "APP_KEY is set" \
    || fail "APP_KEY is empty — run: $PHP_BIN artisan key:generate"

[ "$(env_val APP_ENV)" = "production" ] && ok "APP_ENV=production" \
    || fail "APP_ENV is not 'production' (found '$(env_val APP_ENV)')"

case "$(env_val APP_DEBUG)" in
    false|0|"") ok "APP_DEBUG is off" ;;
    *) fail "APP_DEBUG must be false in production (found '$(env_val APP_DEBUG)')" ;;
esac

[ "$(env_val DB_USERNAME)" != "root" ] && ok "DB user is not root" \
    || fail "DB_USERNAME is 'root' — use a least-privilege database user"

[ -n "$(env_val DB_PASSWORD)" ] && ok "DB password is set" \
    || fail "DB_PASSWORD is empty — set a strong database password"

[ "$(env_val SESSION_SECURE_COOKIE)" = "true" ] && ok "Session cookie is Secure-only" \
    || fail "SESSION_SECURE_COOKIE must be true (site must be served over HTTPS)"

case "$(env_val APP_URL)" in
    https://*) ok "APP_URL uses HTTPS" ;;
    *) fail "APP_URL must be https:// in production (found '$(env_val APP_URL)')" ;;
esac

echo

# ---- Warnings (don't block, but verify) -----------------------------------
bold "Verify manually (set in-app under Settings, or via env)"

if [ -n "$(env_val PESAPAL_CONSUMER_KEY)" ] && [ -n "$(env_val PESAPAL_CONSUMER_SECRET)" ]; then
    ok "PesaPal credentials present in env"
else
    warn "PesaPal creds not in env — confirm they're saved under Settings → Gateway, then use 'Test connection' + 'Register IPN'. The app fails closed without them (customers can't pay)."
fi

if [ "$(env_val PESAPAL_ENV)" = "live" ] || [ "$(env_val PESAPAL_ENV)" = "" ]; then
    warn "Confirm the gateway environment is set to LIVE (not sandbox) under Settings → Gateway."
fi

if [ -n "$(env_val MAIL_HOST)" ] && [ "$(env_val MAIL_HOST)" != "mailpit" ]; then
    ok "MAIL_HOST is set"
else
    warn "SMTP host not in env — confirm it's saved under Settings → Mail and use 'Send test email'."
fi

[ -n "$(env_val MAIL_ALLOWED_HOSTS)" ] && ok "SMTP allowlist (MAIL_ALLOWED_HOSTS) is set" \
    || warn "MAIL_ALLOWED_HOSTS is empty — set it so a compromised session can't reroute outbound mail."

case "$(env_val CORS_ALLOWED_ORIGINS)" in
    ""|"*") warn "CORS_ALLOWED_ORIGINS is open ('*') — restrict it to your domain." ;;
    *) ok "CORS origins are restricted" ;;
esac

# Reminder for the server-level item this script can't see.
warn "Confirm Apache DocumentRoot points at the public/ directory (not the app root)."

echo
bold "Preflight summary"
echo "  errors: $ERRORS   warnings: $WARNINGS"
echo

if [ "$ERRORS" -gt 0 ]; then
    red "BLOCKED — fix the $ERRORS error(s) above before going live. See docs/production-hardening.md."
    exit 1
fi

green "All security gates passed."

if [ "$CHECK_ONLY" -eq 1 ]; then
    [ "$WARNINGS" -gt 0 ] && yellow "($WARNINGS warning(s) to verify — see above.)"
    exit 0
fi

# ---- Production build ------------------------------------------------------
echo
bold "Building for production"

run() { echo "  \$ $*"; "$@" || { red "Step failed: $*"; exit 1; }; }

run "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction
run "$PHP_BIN" artisan migrate --force
run "$PHP_BIN" artisan config:cache
run "$PHP_BIN" artisan route:cache
run "$PHP_BIN" artisan view:cache
run "$PHP_BIN" artisan storage:link

echo
green "Build complete."
if [ "$WARNINGS" -gt 0 ]; then
    yellow "Before announcing go-live, clear the $WARNINGS warning(s) above and run one real end-to-end test payment."
fi
