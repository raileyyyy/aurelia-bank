<?php
/**
 * Aurelia Bank — 404 Not Found handler
 * Referenced by the root .htaccess (ErrorDocument 404).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);

$pageTitle = 'Page not found';
require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="empty-state">
            <div class="empty-state__icon" aria-hidden="true">🔍</div>
            <h1>We couldn't find that page</h1>
            <p class="text-muted">The page you were looking for may have moved or no longer exists.</p>
            <a class="btn btn--primary mt-2" href="<?= e(base_url('index.php')) ?>">Back to home</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
