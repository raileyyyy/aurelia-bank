<?php
/**
 * Aurelia Bank — LAB Configuration TEMPLATE (isolated test VM ONLY)
 * -----------------------------------------------------------------------------
 * This template arms the three controlled vulnerabilities so you can run the
 * attack simulations. Use it ONLY inside your isolated VirtualBox environment,
 * never on a public/production host.
 *
 * HOW TO USE
 *   1. On the victim VM, copy this file to  config/config.local.php
 *   2. Adjust the DB credentials to match that machine (XAMPP defaults below).
 *   3. Enable ONLY the vulnerability you are currently demonstrating by leaving
 *      its flag `true` and setting the others to `false`. Running one at a time
 *      keeps your evidence clean and matches the per-attack procedure in
 *      docs/ATTACK-SIMULATION.md.
 *   4. To "remediate" for the retest, set the relevant flag back to `false`.
 *
 * NEVER commit config.local.php (it is gitignored).
 */

declare(strict_types=1);

// Development so the dev seeders run and errors are visible for evidence.
define('APP_ENV', 'development');

// --- Database (XAMPP defaults) ----------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'aurelia_bank');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Web base path (XAMPP: app in htdocs/aurelia-bank) ----------------------
define('APP_BASE_URL', '/aurelia-bank');

// --- Controlled vulnerability toggles ---------------------------------------
// Enable one at a time for a clean, isolated demonstration.
define('VULN_BRUTE_FORCE', true);   // Attack 1 — login brute force
define('VULN_SQLI',        false);   // Attack 2 — SQL injection (transaction search)
define('VULN_CSRF',        true);   // Attack 3 — CSRF (profile update)
