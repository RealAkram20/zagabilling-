# Offline Lock Client — Build & Protocol Guide

Companion to `DEVELOPMENT_STANDARDS.md`. This document specifies how to build the **Offline Device Client** (native Windows app, C++/ATL/COM, Windows 10–11 x64) so that it interoperates exactly with the **Online Billing Portal** (this Laravel app).

The offline client verifies unlock tokens **locally, with no internet**. It shares one secret per device with the portal (the device `hmac_secret`) and validates every token against it. If any check cannot complete, the device **stays locked** (fail closed).

---

## 1. Two apps, one secret

- **Portal** enrolls the device, takes payment, and **issues** a signed 20-character unlock token.
- **Offline client** gates the Windows login screen, shows the device account number, and **verifies** the token locally.

The only shared material is the per-device **HMAC secret** (`hmac_secret`, 32 bytes, stored encrypted at rest in the portal). The portal signs; the client verifies. Neither app ever transmits or logs the secret or a valid token.

---

## 2. Provisioning (install time)

Two pieces move in **opposite** directions at install:

**Portal → device (the provisioning bundle):**
- `account_number` (e.g. `ZG-40000`) — shown on the lock screen.
- `hmac_secret` (64 hex chars = 32 bytes) — the verification key.

Retrieve it from the portal device page → **Provisioning bundle** (super-admin only, audit-logged). Load it into the client's **local encrypted store** (Windows DPAPI or platform key store). Never write the secret to disk in plaintext; never log it.

**Device → portal (the uninstall code):**
- The offline client **generates its own uninstall code on the device** at install and displays it.
- The operator **records that code in the portal** (device **Edit** page → *Uninstall code (from the device app)*).
- Later, when the plan is complete, the operator reveals the recorded code (device page → **Uninstall authorization**) and enters it in the app to permit removal.

The BIOS password and BitLocker recovery key are captured into the portal vault (encrypted) and can also be pre-loaded into the client's encrypted store if the client needs to surface them locally.

---

## 3. Local encrypted state (on the device)

Store, protected by the platform key store:
- `account_number`, device display fields (`serial`, `model`, `name`) for the lock screen.
- `hmac_secret` (never plaintext).
- `last_counter` (unsigned, starts at 0) — the highest token counter already consumed. This is the single-use / anti-replay guard.
- `lock_deadline` (local date) — when the device must lock again.
- current status (active / grace / overdue / locked).
- optionally BIOS password and BitLocker recovery key (encrypted).

---

## 4. Token wire format (normative)

A token is **100 bits** rendered as **20 Base32 (Crockford) characters**, shown grouped in 4×5 with dashes:

```
XXXXX-XXXXX-XXXXX-XXXXX
```

### 4.1 Payload (40 bits)

Big-endian bit packing into a 40-bit integer:

```
payload = (VERSION << 36) | (counter << 16) | (duration_days << 4) | flags
```

| Field          | Bits | Range         | Meaning                                              |
| -------------- | ---- | ------------- | ---------------------------------------------------- |
| `VERSION`      | 4    | 1             | Format version (currently 1)                         |
| `counter`      | 20   | 0..1,048,575  | Per-device monotonic counter (single-use)            |
| `duration_days`| 12   | 0..4,095      | Days to keep the device unlocked once accepted       |
| `flags`        | 4    | bit0          | bit0 = 1 → grace token, 0 → full token; rest reserved |

`payload_bytes` = the 40-bit integer as **5 bytes, big-endian** (shifts 32, 24, 16, 8, 0).

### 4.2 Signature (60 bits)

```
key    = hex_decode(hmac_secret)            // 32 bytes
digest = HMAC_SHA256(payload_bytes, key)    // 32 bytes
sig    = the top 60 bits of digest          // first 7 bytes + high 4 bits of the 8th byte
```

### 4.3 Assembly

```
bits  = payload_bits(40) ++ sig_bits(60)    // 100 bits, MSB-first
token = base32_crockford(bits)              // 20 chars, 5 bits each, MSB-first
```

**Crockford alphabet (index 0..31):**

```
0123456789ABCDEFGHJKMNPQRSTVWXYZ
```

(Excludes `I L O U`.) Group the 20 chars as `5-5-5-5` with `-`.

### 4.4 Decoding / normalization

Before decoding: uppercase, strip spaces and `-`, then map ambiguous input `O→0`, `I→1`, `L→1`, `U→V`. Require exactly 20 symbols; reject anything else.

---

## 5. Verification algorithm (fail closed)

```
1. normalize + Base32-decode token → 100 bits; if invalid → REJECT
2. payload_bits = bits[0..40), sig_bits = bits[40..100)
3. parse VERSION, counter, duration_days, flags from payload_bits
   - if VERSION != 1 → REJECT
4. recompute expected_sig = top60( HMAC_SHA256(payload_bytes, key) )
   - constant-time compare expected_sig vs sig_bits; if not equal → REJECT   (authenticity + tamper)
5. if counter <= last_counter → REJECT                                        (single-use / replay)
6. accept:
   - last_counter = counter                                                   (persist)
   - base = max(today, lock_deadline)
   - lock_deadline = base + duration_days
   - status = active
   - is_grace = (flags & 1)
```

Any failure → the device remains locked. Never log the token or the secret. Compare signatures in constant time.

> Note on windows: the portal also stamps a display-lifecycle `expires_at` (24 h full / 72 h grace) on the token record so a code shown to a client doesn't linger indefinitely in the portal UI. Offline, the **counter** is the authoritative anti-replay control; a device with no reliable clock still cannot reuse or forge a token.

---

## 6. Published test vectors

Use these to prove the C++ implementation matches the portal's `app/Services/TokenCodec.php` bit-for-bit. Secret is a fixed 32-byte key in hex:

```
hmac_secret = 0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef
```

| counter   | duration_days | type  | token                     |
| --------- | ------------- | ----- | ------------------------- |
| 1         | 30            | full  | `20002-0F04J-CEBMA-PNWJB` |
| 7         | 14            | grace | `2000E-071GE-DSY1A-5H56P` |
| 1048575   | 4095          | full  | `3ZZZZ-ZZGGE-62K9J-6J9M1` |
| 42        | 7             | full  | `2002M-03GSD-AESAV-R12RE` |

Round-trip must recover the exact `counter`, `duration_days`, and `type`. Tampering with any character, or verifying with a different secret, must **REJECT** (the portal's unit test `tests/Unit/TokenCodecTest.php` asserts all of this).

The reference encoder/decoder is `App\Services\TokenCodec` (`encode()` / `decode()`), which the portal uses in `UnlockCodeService::issue()`.

---

## 7. Duration and multiple installments

The portal lets the client pay for several plan periods at once (weekly → N weeks, bi-weekly → N fortnights, monthly → N months). It sets:

```
duration_days = installments × cadence_days      (weekly 7, biweekly 14, monthly 30)
```

The client simply extends `lock_deadline` by `duration_days` on acceptance — it does not need to know the plan cadence.

---

## 8. Features the offline client implements

- **Lock screen**: gate Windows login; show `account_number`, `serial`, `model`, `name`, current status, and `lock_deadline`.
- **Enter unlock code**: accept a 20-char token, verify per §5, unlock on success.
- **Self-purchase**: the client can buy/receive a token from the portal (online, on another device or phone) and type it into the offline app — no internet needed on the locked machine.
- **Uninstall authorization**: the app **generates** its uninstall code at install and displays it (operator records it in the portal). To uninstall/disable the guard, the operator/technician must enter the code recorded in the portal. Protects against unauthorized removal.
- **Enable / disable lock**: toggle enforcement (guarded by the uninstall code / technician auth).
- **Reveal recovery**: surface BIOS password and BitLocker recovery key from the local encrypted store when authorized.

---

## 9. Security rules (from `DEVELOPMENT_STANDARDS.md`)

- **Never store the shared HMAC secret in plain text on disk.** Protect it with the platform key store (§5.3).
- **Do not log the shared secret or a valid unlock code, ever** (§5.5, §9).
- **Every unlock code has a defined validity window. Expired/invalid codes fail closed, never open** (§7).
- **Fail closed everywhere. If a check cannot complete, the device stays locked** (§7).
- Store BIOS passwords and BitLocker recovery keys encrypted, tied to the device serial (§7).
- Separate the COM credential-provider layer from plain C++ verification logic so the token logic is unit-testable outside the Windows login screen (§5.1, §10).

---

## 10. Testing parity

Write C++ unit tests for the verifier using the vectors in §6 (encode → exact token; decode → exact fields; tamper/wrong-secret → reject; `counter <= last_counter` → reject). Per standards §10, **a change to code generation or verification is not merged without a passing test** on both sides (PHP `TokenCodecTest` and the C++ verifier).
