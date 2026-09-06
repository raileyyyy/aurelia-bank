<?php
/**
 * Aurelia Bank — Admin: Transaction Overview (Phase 6)
 * -----------------------------------------------------------------------------
 * Bank-wide transaction browser with the same search/filter/pagination engine
 * used by the customer view, but WITHOUT the per-user scope, so administrators
 * can review activity across all accounts. Reuses search_transactions().
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';

require_role('admin');

$categories = get_transaction_categories();

$q        = trim((string) ($_GET['q'] ?? ''));
$type     = (string) ($_GET['type'] ?? '');
$category = trim((string) ($_GET['category'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo   = trim((string) ($_GET['date_to'] ?? ''));
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 20;

$result = search_transactions([
    'q'         => $q,
    'type'      => $type,
    'category'  => $category,
    'date_from' => ($dateFrom !== '' && is_valid_date($dateFrom)) ? $dateFrom : '',
    'date_to'   => ($dateTo   !== '' && is_valid_date($dateTo))   ? $dateTo   : '',
    'limit'     => $perPage,
    'offset'    => ($page - 1) * $perPage,
]);

$rows       = $result['rows'];
$total      = $result['total'];
$totalPages = (int) ceil($total / $perPage);

$preserve = array_filter([
    'q' => $q, 'type' => $type, 'category' => $category,
    'date_from' => $dateFrom, 'date_to' => $dateTo,
], static fn ($v) => $v !== '');

$pageTitle = 'All transactions';
$activeNav = 'transactions';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">All transactions</h1>
                <p class="text-muted mb-0">Bank-wide transaction activity.</p>
            </div>
        </div>

        <form class="card filters" method="get" action="<?= e(base_url('admin/transactions.php')) ?>">
            <div class="filters__grid">
                <div class="form-group filters__search">
                    <label class="label" for="q">Search</label>
                    <input class="input" type="search" id="q" name="q"
                           value="<?= e($q) ?>" placeholder="Description, counterparty or reference">
                </div>
                <div class="form-group">
                    <label class="label" for="type">Type</label>
                    <select class="select" id="type" name="type">
                        <option value="">All</option>
                        <option value="credit" <?= $type === 'credit' ? 'selected' : '' ?>>Money in</option>
                        <option value="debit"  <?= $type === 'debit'  ? 'selected' : '' ?>>Money out</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="category">Category</label>
                    <select class="select" id="category" name="category">
                        <option value="">All</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e(ucfirst($cat)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="date_from">From</label>
                    <input class="input" type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
                </div>
                <div class="form-group">
                    <label class="label" for="date_to">To</label>
                    <input class="input" type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">
                </div>
            </div>
            <div class="filters__actions">
                <button type="submit" class="btn btn--primary">Apply</button>
                <a class="btn btn--outline" href="<?= e(base_url('admin/transactions.php')) ?>">Reset</a>
            </div>
        </form>

        <p class="text-muted" style="margin:1.25rem 0 .5rem;">
            <?= (int) $total ?> transaction<?= $total === 1 ? '' : 's' ?> found.
        </p>

        <?php if (!$rows): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">🔎</div>
                    <p>No transactions match your filters.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Account</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $t): ?>
                            <tr>
                                <td><?= e(format_date($t['transacted_at'], 'M j, Y')) ?></td>
                                <td><a href="<?= e(base_url('admin/customer.php?id=' . (int) $t['owner_id'])) ?>"><?= e($t['owner_name']) ?></a></td>
                                <td><?= e($t['description']) ?></td>
                                <td><?= e(ucfirst($t['category'])) ?></td>
                                <td class="text-muted"><?= e(mask_account($t['account_number'])) ?></td>
                                <td class="num <?= $t['type'] === 'credit' ? 'amount-credit' : 'amount-debit' ?>">
                                    <?= $t['type'] === 'credit' ? '+' : '−' ?><?= e(format_money($t['amount'], $t['currency'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= render_pagination($page, $totalPages, $preserve, 'transactions.php') ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
