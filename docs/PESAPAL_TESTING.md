# PesaPal Testing Guide

This portal integrates PesaPal API 3.0 for online installment payments. The full flow is implemented and works in two modes:

- **Unconfigured (default):** no consumer key/secret set → payments are *simulated* and clear instantly. Good for UI/UX testing.
- **Configured:** real PesaPal sandbox or live credentials → real redirect + confirmation flow.

## Where it lives

| Concern | File |
| --- | --- |
| API client (auth, IPN, order, status) | `app/Services/PesapalClient.php` |
| Payment orchestration | `app/Services/PaymentService.php` |
| Gateway settings (env, key, secret, currency, IPN) | `app/Services/SettingsService.php` + Settings page |
| Portal flow (lookup → pay → callback → code) | `app/Http/Controllers/Client/UnlockController.php` |
| Config fallback | `config/services.php` (`services.pesapal.*`) |

## How verification is secured

PesaPal v3 does **not** sign its callback/IPN. The secure pattern — which this app follows — is to treat the inbound `OrderTrackingId` only as a *hint*, then re-query PesaPal server-side with `GetTransactionStatus` and act only on `status_code == 1`. See `PaymentService::verifyByTracking()`. A spoofed callback cannot mark a payment paid because the tracking id must already exist in our DB **and** PesaPal must independently report it as paid.

## Sandbox test checklist

1. Create a PesaPal developer/sandbox account and get a **consumer key** and **consumer secret**.
2. In the portal: **Settings → Payment gateway · PesaPal**
   - Environment: **Sandbox**
   - Currency: e.g. **KES**
   - Paste consumer key + secret → **Save gateway settings**
3. Click **Test connection** → expect “connection succeeded — access token acquired.” (Failures are logged to `storage/logs/laravel.log` with the reason, never the secret.)
4. Click **Register IPN** → an IPN id is stored and shown.
5. Public IPN reachability: PesaPal must be able to `GET`/`POST` your IPN URL. Locally, expose it with a tunnel (e.g. ngrok) and set the tunnel URL as the **IPN URL**, or rely on the callback return for manual testing.
6. Run a payment:
   - Client portal → **Unlock** → enter a device account number → **Summary → Pay**
   - Choose how many periods to pay → **Pay** → you are redirected to PesaPal sandbox
   - Complete the sandbox payment
   - PesaPal redirects back to `portal.callback`, which verifies via `GetTransactionStatus`; on success the unlock token is shown
7. Confirm: the `payments` row is `paid`, `next_due_at` advanced by `periods × cadence`, `progress()` credited the periods, and a signed unlock token was issued.

## Going live

- Switch Environment to **Live**, paste live credentials, **Save**, **Test connection**, **Register IPN** again (IPN ids are per-environment; the app clears the stored id when the environment changes).
- First live test: use a **small amount** and watch `storage/logs/laravel.log`.

## Admin-side collection (cash / mobile money)

The device page **Collect payment** action collects a chosen number of periods:
- **Cash** clears immediately and issues a token.
- **Mobile Money** uses PesaPal; when configured it shows the PesaPal prompt in an iframe and polls `paymentStatus` until it clears; when unconfigured it simulates.
