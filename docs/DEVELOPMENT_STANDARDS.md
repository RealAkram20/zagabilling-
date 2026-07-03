# Zaga Device Lock — Development Standards

Version 1.0
Owner: ArmGenius
Applies to: Offline Device Client and Online Billing Portal

---

> **Hard rule.** Keep code clean and comment free by default. Do not fill this project with comments. Comment only when a rule in Section 3 demands it. Dense, obvious, or auto generated comments are rejected in review.

---

## 1. Scope

This project ships two separate applications that share one secret and one purpose.

**Offline Device Client.** A native Windows application preinstalled on each installment computer before it ships. It gates the Windows login screen, generates and displays the device account number on the lock screen, verifies unlock codes locally, and resists removal.

**Online Billing Portal.** A PHP and MySQL web application. It tracks devices and plans, takes payment through PesaPal, and generates the unlock code for a device once payment clears.

Both applications follow the standards in this document. Where a rule is language specific, the section says so.

---

## 2. Guiding Principles

1. Code reads like it was written by one careful engineer, not assembled by a tool.
2. A new hire should understand any file without a walkthrough.
3. Clarity beats cleverness. A longer clear name beats a short cryptic one.
4. Every function does one thing. If a function needs a paragraph to explain, split it.
5. Security is a first class concern, not a later pass. Secrets never touch source control.
6. Small commits, clear history. The git log is documentation.

---

## 3. Comment Policy

This is the most important rule in this document. Read it twice.

**Write code that does not need comments. Then comment only what the code cannot say.**

Most code in this project should carry no comments at all. A clean function with a clear name and clear variables explains itself. If you feel the urge to add a comment, first ask whether a better name or a smaller function would remove the need. It usually will.

Heavy commenting is not thoroughness. It is noise. It clutters the file, it goes stale the moment the code changes, and it is the clearest signal that code was machine generated. This project may be handed to another engineer later, and dense filler comments make that handover harder, not easier. Keep the code clean and let it speak.

### 3.1 Directive for any code assistant

If you generate code for this project, whether a person or an AI tool, strip the comments back to the minimum before the code is committed. Do not auto document every function. Do not annotate obvious lines. Do not add section banners. Produce the same clean, quiet code a senior engineer would write by hand. Aim for zero comments, and add one only when a rule below demands it.

### 3.2 Do not comment

- Do not restate what a line does. `count = count + 1` needs no comment.
- Do not narrate control flow. No `// loop through the users`.
- Do not label obvious blocks. No `// start of function`.
- Do not leave placeholder or filler comments.
- Do not write a docstring for a function whose name already explains it.
- Do not auto generate a comment for every method just because the tool offers to.

Example of noise to delete:

```
// get the device
$device = $this->devices->find($id);
// check if the device is locked
if ($device->isLocked()) {
    // return true
    return true;
}
```

The same code, clean:

```
$device = $this->devices->find($id);

if ($device->isLocked()) {
    return true;
}
```

### 3.3 Do comment

- Do not restate what a line does. `count = count + 1` needs no comment.
- Do not narrate control flow. No `// loop through the users`.
- Do not label obvious blocks. No `// start of function`.
- Do not leave placeholder or filler comments.
- Do not write a docstring for a function whose name already explains it.

Do comment.

- Explain why, not what. Document the reason behind a non obvious decision.
- Warn about a gotcha. Note a race condition, a platform quirk, or an order that must not change.
- Cite a source. Reference a spec section, a ticket number, or a standard when the code implements one.
- Flag intentional strangeness. If code looks wrong but is correct, say why in one line.

### 3.4 Style for the comments that survive

- Keep them short and direct. One line where one line works.
- Write full, plain sentences. No trailing fragments.
- Place the comment on its own line above the code, not trailing at the end of a long line.
- Remove a comment the moment the code it describes changes and makes it false.
- No author tags, no dates, no change logs inside files. Git holds that history.

The test. If a comment could be deleted and a competent engineer would still understand the code, delete it.

---

## 4. General Conventions

### 4.1 Naming

- Names describe intent. `remainingBalance`, not `rb` or `temp`.
- Booleans read as a yes or no question. `isLocked`, `hasPaid`, `canUninstall`.
- Functions are verbs. `generateUnlockCode`, `verifyPayment`, `lockSession`.
- Constants are upper case with underscores. `MAX_CODE_ATTEMPTS`.
- No abbreviations unless they are universal in the domain. `id`, `url`, `pin` are fine. `usr`, `pmt`, `dev` are not.

### 4.2 Formatting

- One statement per line.
- Indent with the language default. Spaces in PHP, the project style file in C++.
- Keep lines under 120 characters.
- Group related code with a single blank line. Never stack blank lines.
- No trailing whitespace. No tabs mixed with spaces.

### 4.3 File and folder structure

- One class per file. The file name matches the class name.
- Folders group by feature, not by type, once a feature grows past a few files.
- No dead code. Delete unused files rather than commenting them out. Git remembers.

### 4.4 Functions

- A function fits on one screen. If it does not, split it.
- Return early. Guard clauses at the top beat deep nesting.
- No magic numbers. Name the value as a constant.
- No hidden side effects. A function named `getBalance` does not also write to the database.

---

## 5. Offline Device Client Standards

Language: C++ with ATL. Component model: COM. Target: Windows 10 and 11, 64 bit.

### 5.1 Structure

- Separate the credential provider COM layer from the business logic. The COM classes handle the Windows contract. Plain C++ classes handle code verification, state, and storage. This keeps the logic testable outside the login screen.
- One class per credential provider interface implementation.
- Wrap all Win32 and COM handles in RAII types. No raw `HANDLE` or `CoTaskMemAlloc` left to manual cleanup.

### 5.2 Memory and resources

- Use smart pointers. No raw `new` and `delete` in application code.
- Every acquired resource has a defined owner and a defined release point.
- Check every COM `HRESULT`. Never ignore a failed call.

### 5.3 State and storage

- Store device state in a local encrypted store. Device account number, plan state, current code window, and last known state live here.
- Never store the shared HMAC secret in plain text on disk. Protect it with the platform key store.
- The client assumes no internet. All verification runs locally against the stored secret.

### 5.4 Resilience

- The lock service restarts itself if stopped.
- The client restores its registry keys and files if they are altered.
- Tampering attempts are logged locally with a timestamp for later sync.

### 5.5 Do not

- Do not hardcode secrets, PINs, or master passwords in source.
- Do not log the shared secret or a valid unlock code, ever.
- Do not block the login UI thread on any long operation.

---

## 6. Online Billing Portal Standards

Language: PHP 8.1 or later. Database: MySQL 8. Standard: PSR-12.

### 6.1 Structure

- Follow PSR-12 for style and PSR-4 for autoloading.
- Separate layers. Controllers handle requests. Services hold logic. Repositories handle data. Views hold markup only.
- No business logic in views. No SQL in controllers.

### 6.2 Database access

- Use PDO with prepared statements for every query. No string concatenation into SQL, ever.
- Name tables and columns in snake case. `device_accounts`, `unlock_codes`, `payment_status`.
- Every table has a primary key and the timestamps it needs. Add indexes for the columns you filter on.

### 6.3 Input and output

- Validate and sanitize every input at the boundary. Trust nothing from the client or from PesaPal until verified.
- Escape all output in views to prevent injection.
- The PesaPal IPN is verified by calling PesaPal for transaction status. The callback redirect is never trusted to activate anything on its own.

### 6.4 Configuration

- All secrets and keys live in environment variables or a config file outside the web root. Never in the repository.
- Keep separate config for sandbox and production. The environment decides which loads.

### 6.5 Do not

- Do not commit `.env`, keys, or credentials.
- Do not echo raw errors or stack traces to the client in production.
- Do not store the HMAC secret, BIOS passwords, or recovery keys in plain text. Encrypt at rest.

---

## 7. Security Standards

The value of both applications is their integrity. Treat every secret as if a client will try to extract it.

- Secrets never enter source control. Use a secret manager or protected config.
- The shared HMAC secret is unique per deployment, not shared across all devices where avoidable.
- BIOS passwords and BitLocker recovery keys are stored in the portal, encrypted, and tied to the device serial.
- Log security events. Never log the secrets themselves.
- Every unlock code has a defined validity window. Expired codes fail closed, never open.
- Fail closed everywhere. If a check cannot complete, the device stays locked.

---

## 8. Version Control

- One repository per application. The offline client and the portal do not share a repository.
- Branch from `main`. Feature branches use `feature/short-name`. Fixes use `fix/short-name`.
- Commit messages state what changed and why in the imperative. `Add unlock code expiry check`, not `changes` or `update`.
- One logical change per commit. Do not mix a feature and a formatting sweep in one commit.
- No secrets, binaries, or generated files in the repository. Maintain a proper ignore file from day one.
- Open a pull request for review before merging to `main`, even as a solo developer. It creates the paper trail a future hire will read.

---

## 9. Error Handling and Logging

- Handle errors where you can act on them. Do not swallow an error silently.
- Log at the right level. Info for normal events, warning for recoverable problems, error for failures.
- Log messages are plain and specific. `Payment verification failed for device 4021, PesaPal returned INVALID`, not `error happened`.
- Never log secrets, unlock codes, or full payment card data.
- The portal keeps an audit trail for every lock, unlock, payment, and admin action.

---

## 10. Testing

- Write tests for the logic that matters. Code generation, code verification, expiry, and payment state transitions get tests first.
- The offline verification logic is tested as plain C++ classes, outside the credential provider, so tests run without a Windows login screen.
- The portal has tests for the payment flow against the PesaPal sandbox before any production key is used.
- A change to code generation or verification is not merged without a passing test.

---

## 11. Documentation

- Project documentation lives in the repository as markdown, not scattered in code comments.
- Each repository has a README that explains what the application does, how to set it up, and how to run it.
- Architecture decisions are recorded briefly in a `docs` folder, one short file per decision.
- The code stays clean and self explanatory. The documents carry the context. Together they let a new engineer take over without a meeting.
