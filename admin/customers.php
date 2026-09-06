<?php
/**
 * Aurelia Bank — Admin: Customer List (Phase 6)
 * -----------------------------------------------------------------------------
 * Searchable, paginated list of customers with a summary of their accounts and
 * total balance, linking through to each customer's detail page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';

require_role('admin');

$q       = trim((string) ($_GET['q'] ?? ''));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$result     = get_customers($q, $perPage, ($page - 1) * $perPage);
$rows       = $result['rows'];
$total      = $result['total'];
$totalPages = (int) ceil($total / $perPage);

$preserve = array_filter(['q' => $q], static fn ($v) => $v !== '');

$pageTitle = 'Customers';
$activeNav = 'customers';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">Customers</h1>
                <p class="text-muted mb-0"><?= (int) $total ?> customer<?= $total === 1 ? '' : 's' ?> total.</p>
            </div>
        </div>

        <form class="card filters" method="get" action="<?= e(base_url('admin/customers.php')) ?>">
            <div class="filters__grid">
                <div class="form-group filters__search">
                    <label class="label" for="q">Search customers</label>
                    <input class="input" type="search" id="q" name="q"
                           value="<?= e($q) ?>" placeholder="Name, username or email">
                </div>
            </div>
            <div class="filters__actions">
                <button type="submit" class="btn btn--primary">Search</button>
                <a class="btn btn--outline" href="<?= e(base_url('admin/customers.php')) ?>">Reset</a>
            </div>
        </form>

        <?php if (!$rows): ?>
            <div class="card" style="margin-top:1.25rem;">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">👤</div>
                    <p>No customers match your search.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap" style="margin-top:1.25rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th class="num">Accounts</th>
                            <th class="num">Total balance</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $c): ?>
                            <tr>
                                <td><?= e($c['full_name']) ?></td>
                                <td class="text-muted"><?= e($c['username']) ?></td>
                                <td class="text-muted"><?= e($c['email']) ?></td>
                                <td class="num"><?= (int) $c['account_count'] ?></td>
                                <td class="num"><?= e(format_money($c['total_balance'])) ?></td>
                                <td><span class="badge <?= e(status_badge_class($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?></span></td>
                                <td><a class="btn btn--outline btn--sm" href="<?= e(base_url('admin/customer.php?id=' . (int) $c['id'])) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= render_pagination($page, $totalPages, $preserve, 'customers.php') ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
