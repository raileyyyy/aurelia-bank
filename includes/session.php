<?php
/**
 * Aurelia Bank — Session Bootstrap
 * -----------------------------------------------------------------------------
 * Starts a PHP session with secure cookie parameters.
 *
 * Secure defaults used during normal development:
 *   - HttpOnly  : JavaScript cannot read the session cookie
 *   - Secure    : cookie only sent over HTTPS (auto-detected)
 *   - SameSite  : 'Lax' — sensible default that still allows normal navigation
 *
 * (The controlled CSRF demonstration in a later phase will revisit SameSite and
 * token protection; this file establishes the clean baseline.)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps =
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $isHttps,
        'samesite' => 'Lax',
    ]);

    session_name(SESSION_NAME);
    session_start();
}
