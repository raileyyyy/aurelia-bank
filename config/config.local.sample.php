<?php
/**
 * Aurelia Bank — Local / Deployment Configuration TEMPLATE
 * -----------------------------------------------------------------------------
 * HOW TO USE
 *   1. Copy this file to  config/config.local.php
 *   2. Fill in the values for your environment
 *   3. NEVER commit config.local.php (it is listed in .gitignore)
 *
 * Anything you define here overrides the defaults in config/config.php.
 */

declare(strict_types=1);

// Environment: 'development' on your machine, 'production' on Hostinger.
define('APP_ENV', 'development');

// -----------------------------------------------------------------------------
// Database credentials
// -----------------------------------------------------------------------------
define('DB_HOST', '127.0.0.1');   // Hostinger: usually 'localhost'
define('DB_PORT', '3306');
define('DB_NAME', 'aurelia_bank'); // Hostinger: e.g. u123456789_aurelia
define('DB_USER', 'root');         // Hostinger: e.g. u123456789_admin
define('DB_PASS', '');             // Hostinger: the DB user's password

// -----------------------------------------------------------------------------
// Optional: force the web base path if auto-detection is wrong.
// Local XAMPP example (app in htdocs/aurelia-bank): '/aurelia-bank'
// Hostinger root deployment: '' (empty string)
// -----------------------------------------------------------------------------
// define('APP_BASE_URL', '/aurelia-bank');

// -----------------------------------------------------------------------------
// CONTROLLED VULNERABILITY TOGGLES  (academic Phase 7)
// -----------------------------------------------------------------------------
// Leave ALL of these false for a normal, secure bank. Enable a flag ONLY inside
// your isolated VirtualBox test VM to arm that one demonstration, run the
// attack, then set it back to false to "remediate". Never enable on a public
// or production host. See docs/ATTACK-SIMULATION.md for the full procedure.
//
// define('VULN_BRUTE_FORCE', false);  // true → login has NO rate limiting
// define('VULN_SQLI',        false);  // true → transaction search is injectable
// define('VULN_CSRF',        false);  // true → profile update has NO CSRF token
