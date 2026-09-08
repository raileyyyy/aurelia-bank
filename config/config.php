<?php
/**
 * Aurelia Bank — Application Configuration
 * -----------------------------------------------------------------------------
 * Central configuration loader.
 *
 * SECURITY: Real credentials must NEVER be committed in this file. Environment
 * specific values (DB password, production host, etc.) are supplied by:
 *   1. config/config.local.php  (gitignored — used on your machine / Hostinger)
 *   2. server environment variables (getenv)
 *   3. the safe local-development defaults defined below
 *
 * Precedence: a value already defined by config.local.php wins, then an
 * environment variable, then the default.
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Paths (filesystem)
// -----------------------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));          // absolute path to the app root
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// -----------------------------------------------------------------------------
// Environment: 'development' or 'production'
// -----------------------------------------------------------------------------
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// -----------------------------------------------------------------------------
// Load local overrides (never committed to version control)
// -----------------------------------------------------------------------------
$localConfig = __DIR__ . '/config.local.php';
if (is_readable($localConfig)) {
    require $localConfig;
}

// -----------------------------------------------------------------------------
// Database configuration
// -----------------------------------------------------------------------------
if (!defined('DB_HOST'))    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT'))    define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME'))    define('DB_NAME', getenv('DB_NAME') ?: 'aurelia_bank');
if (!defined('DB_USER'))    define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// Application constants
// -----------------------------------------------------------------------------
define('APP_NAME', 'Aurelia Bank');
define('APP_TAGLINE', 'Banking made brilliantly simple.');
define('SESSION_NAME', 'AURELIA_SESSION');

// Optional explicit web base path (e.g. '/aurelia-bank'). Leave undefined to let
// includes/functions.php auto-detect it from DOCUMENT_ROOT. Define it in
// config.local.php only if auto-detection does not match your setup.
// define('APP_BASE_URL', '/aurelia-bank');

// -----------------------------------------------------------------------------
// Controlled vulnerability toggles (academic Phase 7 — SECURE BY DEFAULT)
// -----------------------------------------------------------------------------
// Each flag arms ONE clearly-isolated demonstration vulnerability in the
// existing functionality. They are false unless explicitly enabled, so the
// production/normal build is safe. Enable them ONLY inside your isolated
// VirtualBox test VM, either here via config.local.php:
//
//     define('VULN_BRUTE_FORCE', true);   // login: no rate limiting
//     define('VULN_SQLI',        true);   // transaction search: unsafe query
//     define('VULN_CSRF',        true);   // profile update: no CSRF token
//
// ...or per request via an environment variable (VULN_SQLI=1, etc.).
// To revert a demonstration, set the flag back to false — no code changes.
if (!defined('VULN_BRUTE_FORCE')) {
    define('VULN_BRUTE_FORCE', getenv('VULN_BRUTE_FORCE') === '1');
}
if (!defined('VULN_SQLI')) {
    define('VULN_SQLI', getenv('VULN_SQLI') === '1');
}
if (!defined('VULN_CSRF')) {
    define('VULN_CSRF', getenv('VULN_CSRF') === '1');
}

// -----------------------------------------------------------------------------
// Error handling strategy (based on environment)
// -----------------------------------------------------------------------------
error_reporting(E_ALL);
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Use UTC internally; presentation-layer formatting can localise later.
date_default_timezone_set('UTC');
