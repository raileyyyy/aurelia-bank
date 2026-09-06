<?php
/**
 * Aurelia Bank — Transfer Money (Phase 6+)
 * -----------------------------------------------------------------------------
 * Transfer funds from one of the signed-in customer's accounts to another
 * account (their own or another customer's) by account number. Creates a
 * matched debit + credit pair atomically (see transfer_money()).
 *
 * This is a sensitive, state-changing action, so it is CSRF-protected with a
 * synchroniser token and only acts on POST.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/banking.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

require_role('customer');

$user     = current_user();
$userId   = $user['id'];
$accounts = array_filter(get_accounts_for_user($userId), static fn ($a) => $a['status'] === 'active');

$error = '';
// Sticky form values.
$fromId   = (int) ($_POST['from_account_id'] ?? 0);
$toNumber = trim((string) ($_POST['to_account_number'] ?? ''));
$amountIn = trim((string) ($_POST['amount'] ?? ''));
$note     = trim((string) ($_POST['note'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Your session has expired. Please try again.';
    } else {
        $amount = parse_amount($amountIn);

        // Validate the source account belongs to the user and is active.
        $validSource = false;
        foreach ($accounts as $a) {
            if ((int) $a['id'] === $fromId) {
                $validSource = true;
                break;
            }
        }

        if (!$validSource) {
            $error = 'Please choose a valid account to transfer from.';
        } elseif ($toNumber === '') {
            $error = 'Please enter the recipient account number.';
        } elseif ($amount === null) {
            $error = 'Please enter a valid amount greater than zero.';
        } else {
            $result = transfer_money($userId, $fromId, $toNumber, $amount, $note !== '' ? $note : null);
            if ($result['ok']) {
                log_audit($userId, 'money.transfer',
                    sprintf('Transferred %s from account #%d to %s (ref %s)',
                        format_money($amount), $fromId, $toNumber, $result['reference']));
                set_flash('success', sprintf('%s sent to %s. Reference %s.',
                    format_money($amount), $result['recipient'], $result['reference']));
                redirect('customer/dashboard.php');
            }
            $error = $result['error'] ?? 'The transfer could not be completed.';
        }
    }
}

$pageTitle = 'Transfer money';
$activeNav = 'transfer';

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="mt-0">Transfer money</h1>
                <p class="text-muted mb-0">Send money to another Aurelia account.</p>
            </div>
            <div class="action-bar">
                <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/deposit.php')) ?>">Deposit</a>
                <a class="btn btn--outline btn--sm" href="<?= e(base_url('customer/withdraw.php')) ?>">Withdraw</a>
            </div>
        </div>

        <?php if (!$accounts): ?>
            <div class="card" style="margin-top:1.25rem;">
                <div class="empty-state">
                    <div class="empty-state__icon" aria-hidden="true">🏦</div>
                    <p>You have no active accounts to transfer from.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card form-narrow" style="margin-top:1.25rem;">
                <?php if ($error !== ''): ?>
                    <div class="alert alert--error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('customer/transfer.php')) ?>" novalidate data-validate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="label" for="from_account_id">From account</label>
                        <select class="select" id="from_account_id" name="from_account_id" required>
                            <?php foreach ($accounts as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $fromId === (int) $a['id'] ? 'selected' : '' ?>>
                                    <?= e(ucfirst($a['account_type']) . ' · ' . mask_account($a['account_number'])
                                        . ' · ' . format_money($a['balance'], $a['currency'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label" for="to_account_number">Recipient account number</label>
                        <input class="input" type="text" id="to_account_number" name="to_account_number"
                               value="<?= e($toNumber) ?>" placeholder="e.g. AUREL1000000037" required>
                        <p class="field-hint">Enter the full account number of the account you want to pay.</p>
                    </div>

                    <div class="form-group">
                        <label class="label" for="amount">Amount</label>
                        <input class="input" type="text" inputmode="decimal" id="amount" name="amount"
                               value="<?= e($amountIn) ?>" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label class="label" for="note">Reference / note <span class="text-muted">(optional)</span></label>
                        <input class="input" type="text" id="note" name="note" value="<?= e($note) ?>" maxlength="120">
                    </div>

                    <button type="submit" class="btn btn--primary">Send transfer</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
