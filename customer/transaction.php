<?php
/**
 * Aurelia Bank — Transaction Detail (Phase 4)
 * -----------------------------------------------------------------------------
 * Shows the full detail of a single transaction. The lookup is scoped to the
 * signed-in user: requesting an id that belongs to someone else returns "not
 * found" (authorization enforced in the query, not just hidden in the UI).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';

require_role('customer');

$user = current_user();
$txId = (int) ($_GET['id'] ?? 0);
$tx   = $txId > 0 ? get_transaction_for_user($txId, $user['id']) : null;

$pageTitle = 'Transaction';
$activeNav = 'transactions';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <p><a href="<?= e(base_url('customer/transactions.php')) ?>">&larr; Back to transactions</a></p>

        <?php if ($tx === null): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">🔍</div>
                    <p>That transaction could not be found.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="page-header">
                <div>
                    <h1 class="mt-0"><?= e($tx['description']) ?></h1>
                    <p class="text-muted mb-0"><?= e(format_date($tx['transacted_at'], 'l, F j, Y \a\t g:i A')) ?></p>
                </div>
                <div>
                    <span class="tx-amount <?= $tx['type'] === 'credit' ? 'amount-credit' : 'amount-debit' ?>">
                        <?= $tx['type'] === 'credit' ? '+' : '−' ?><?= e(format_money($tx['amount'], $tx['currency'])) ?>
                    </span>
                </div>
            </div>

            <div class="card" style="margin-top:1.25rem;">
                <dl class="detail-list">
                    <div><dt>Reference</dt><dd><?= e($tx['reference']) ?></dd></div>
                    <div><dt>Type</dt><dd><?= $tx['type'] === 'credit' ? 'Money in (credit)' : 'Money out (debit)' ?></dd></div>
                    <div><dt>Category</dt><dd><?= e(ucfirst($tx['category'])) ?></dd></div>
                    <div><dt>Counterparty</dt><dd><?= e($tx['counterparty'] ?? '—') ?></dd></div>
                    <div><dt>Status</dt><dd><span class="badge <?= e(status_badge_class($tx['status'])) ?>"><?= e(ucfirst($tx['status'])) ?></span></dd></div>
                    <div><dt>Account</dt><dd><?= e(ucfirst($tx['account_type']) . ' · ' . $tx['account_number']) ?></dd></div>
                    <div><dt>Balance after</dt><dd><?= $tx['balance_after'] !== null ? e(format_money($tx['balance_after'], $tx['currency'])) : '—' ?></dd></div>
                    <div><dt>Recorded</dt><dd><?= e(format_date($tx['created_at'], 'M j, Y g:i A')) ?></dd></div>
                </dl>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
