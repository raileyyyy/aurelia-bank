<?php
/**
 * Aurelia Bank — Common Page Footer
 * -----------------------------------------------------------------------------
 * Closes the <main> element opened in header.php, renders the site footer, and
 * loads the shared JavaScript. Include this at the very end of every page.
 */

declare(strict_types=1);
?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__brand">
            <span class="brand__mark" aria-hidden="true">A</span>
            <span class="brand__name"><?= e(APP_NAME) ?></span>
            <p class="site-footer__tagline"><?= e(APP_TAGLINE) ?></p>
        </div>

        <nav class="site-footer__nav" aria-label="Footer">
            <div>
                <h4>Banking</h4>
                <ul>
                    <li><a href="<?= e(base_url('index.php#personal')) ?>">Personal</a></li>
                    <li><a href="<?= e(base_url('index.php#business')) ?>">Business</a></li>
                    <li><a href="<?= e(base_url('index.php#support')) ?>">Support</a></li>
                </ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="<?= e(base_url('index.php')) ?>">About</a></li>
                    <li><a href="<?= e(base_url('index.php')) ?>">Careers</a></li>
                    <li><a href="<?= e(base_url('index.php')) ?>">Contact</a></li>
                </ul>
            </div>
        </nav>
    </div>

    <div class="container site-footer__legal">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Fictional bank for academic use only. No real accounts or money.</p>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
