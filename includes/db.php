<?php
/**
 * Aurelia Bank — Database Connection (PDO)
 * -----------------------------------------------------------------------------
 * Provides a single, lazily-created PDO connection via db().
 *
 * Configured with:
 *   - Exceptions on error (so failures are never silently ignored)
 *   - Associative fetch by default
 *   - Real prepared statements (EMULATE_PREPARES = false)
 *
 * These defaults make it natural to use parameterised queries throughout the
 * app during normal development. (The controlled SQL-injection demonstration in
 * a later phase will be an intentional, clearly isolated deviation.)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * Return the shared PDO connection, creating it on first use.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Always log the real reason.
        error_log('[Aurelia Bank] Database connection failed: ' . $e->getMessage());

        // Re-throw so the caller decides how to react:
        //   - pages that need the DB let it bubble to the global handler
        //     (includes/errors.php), which shows a friendly message.
        //   - pages that can degrade gracefully (e.g. the homepage status
        //     panel) catch it locally and continue rendering.
        throw $e;
    }

    return $pdo;
}
