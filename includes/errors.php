<?php
/**
 * Aurelia Bank — Global Error / Exception Handling
 * -----------------------------------------------------------------------------
 * Establishes the application's error-handling strategy:
 *
 *   - Uncaught exceptions are logged and turned into a friendly response.
 *   - In development the real error is shown; in production a generic message
 *     is shown and details are only written to the server log.
 *
 * Individual pages may still catch specific exceptions locally when they want
 * to degrade gracefully (e.g. the homepage system-status panel catches a
 * database failure so the rest of the page still renders).
 */

declare(strict_types=1);

/**
 * Render a minimal, dependency-free error response.
 * Kept intentionally self-contained so it works even if the database or other
 * subsystems are unavailable.
 */
function render_fatal(string $devDetail = ''): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    $showDetail = (defined('APP_ENV') && APP_ENV === 'development' && $devDetail !== '');
    $safeDetail = htmlspecialchars($devDetail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Something went wrong</title>';
    echo '<style>body{font-family:"Segoe UI",system-ui,sans-serif;background:#eef2f7;color:#14202e;';
    echo 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}';
    echo '.box{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:2rem;max-width:560px;';
    echo 'box-shadow:0 4px 12px rgba(16,33,54,.08)}h1{color:#0f2540;margin-top:0}';
    echo 'pre{background:#f5f7fa;border:1px solid #e3e8ef;border-radius:8px;padding:1rem;overflow:auto;';
    echo 'font-size:.85rem;color:#922419;white-space:pre-wrap}</style></head><body><div class="box">';
    echo '<h1>Something went wrong</h1>';
    echo '<p>We are unable to process your request right now. Please try again later.</p>';
    if ($showDetail) {
        echo '<pre>' . $safeDetail . '</pre>';
    }
    echo '</div></body></html>';
}

/**
 * Handle any exception that escapes to the top level.
 */
set_exception_handler(static function (Throwable $e): void {
    error_log('[Aurelia Bank] Uncaught ' . get_class($e) . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());
    render_fatal($e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine());
});

/**
 * Catch fatal errors (which are not exceptions) on shutdown so users never see
 * a blank white page in production.
 */
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[Aurelia Bank] Fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        render_fatal($err['message'] . "\n\n" . $err['file'] . ':' . $err['line']);
    }
});
