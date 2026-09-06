<?php
/**
 * Aurelia Bank — Banking Data Seeder (development)
 * -----------------------------------------------------------------------------
 * Creates fictional ACCOUNTS and a realistic TRANSACTION ledger for the test
 * customers created by seed_users.php, so Phases 3–6 (dashboard, transaction
 * search/filter, admin overview) have meaningful data to display.
 *
 * Idempotent: accounts are upserted by account_number; each account's
 * transactions are cleared and regenerated, and the account balance is set to
 * the resulting ledger balance. Refuses to run outside development.
 *
 * HOW TO RUN (after seed_users.php):
 *   "C:\xampp\php\php.exe" database\seed_banking.php
 *   php database/seed_banking.php        # if php is on your PATH
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (APP_ENV !== 'development') {
    fwrite(STDERR, "Refusing to run: APP_ENV is not 'development'.\n");
    exit(1);
}

/** Accounts to create per username. */
$accountsByUser = [
    'jdoe' => [
        ['number' => 'AUREL1000000011', 'type' => 'checking', 'opened' => '2023-03-14', 'opening' => 1850.00, 'count' => 34],
        ['number' => 'AUREL1000000029', 'type' => 'savings',  'opened' => '2023-05-02', 'opening' => 8200.00, 'count' => 16],
    ],
    'msanders' => [
        ['number' => 'AUREL1000000037', 'type' => 'checking', 'opened' => '2024-01-20', 'opening' => 1200.00, 'count' => 28],
    ],
];

/** Transaction templates: [description, counterparty, category, type, minAmt, maxAmt]. */
$credits = [
    ['Salary payment',        'Nimbus Corp',    'salary',   2000, 3500],
    ['Incoming transfer',     'J. Alvarez',     'transfer',   50,  600],
    ['Refund',                'Zappa Store',    'refund',     10,  150],
    ['Interest earned',       'Aurelia Bank',   'interest',    1,   25],
    ['Cash deposit',          'Branch deposit', 'deposit',   100,  900],
];
$debits = [
    ['Grocery purchase',      'FreshMart',      'groceries',    15, 180],
    ['Restaurant',            'The Copper Pot', 'dining',       12,  95],
    ['Electricity bill',      'MetroPower',     'utilities',    40, 160],
    ['Streaming subscription','StreamFlix',     'subscription',  8,  20],
    ['Online purchase',       'Zappa Store',    'payment',      20, 300],
    ['ATM withdrawal',        'ATM #1188',      'withdrawal',   20, 300],
    ['Card payment',          'City Transit',   'payment',       5, 120],
    ['Service fee',           'Aurelia Bank',   'fee',           1,  15],
];

try {
    $pdo = db();

    $findUser = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');

    $upsertAccount = $pdo->prepare(
        'INSERT INTO accounts (user_id, account_number, account_type, currency, status, opened_at)
         VALUES (:uid, :num, :type, \'USD\', \'active\', :opened)
         ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id), account_type = VALUES(account_type),
            currency = VALUES(currency), status = \'active\', opened_at = VALUES(opened_at)'
    );
    $findAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_number = :num LIMIT 1');
    $clearTx     = $pdo->prepare('DELETE FROM transactions WHERE account_id = :aid');
    $insertTx    = $pdo->prepare(
        'INSERT INTO transactions
            (account_id, reference, type, category, amount, balance_after, description, counterparty, status, transacted_at)
         VALUES
            (:aid, :ref, :type, :cat, :amt, :bal, :desc, :cp, \'completed\', :at)'
    );
    $setBalance  = $pdo->prepare('UPDATE accounts SET balance = :bal WHERE id = :aid');

    $totalTx = 0;

    foreach ($accountsByUser as $username => $accounts) {
        $findUser->execute([':u' => $username]);
        $userId = $findUser->fetchColumn();
        if ($userId === false) {
            fwrite(STDERR, "  skip: user '$username' not found — run seed_users.php first.\n");
            continue;
        }

        foreach ($accounts as $acc) {
            $upsertAccount->execute([
                ':uid'    => $userId,
                ':num'    => $acc['number'],
                ':type'   => $acc['type'],
                ':opened' => $acc['opened'],
            ]);
            $findAccount->execute([':num' => $acc['number']]);
            $accountId = (int) $findAccount->fetchColumn();

            // Fresh ledger every run.
            $clearTx->execute([':aid' => $accountId]);

            // Deterministic-ish generation so re-runs look stable per account.
            mt_srand(1000 + $accountId);

            $balance = (float) $acc['opening'];
            $date    = new DateTimeImmutable('-120 days');
            $now     = new DateTimeImmutable('now');
            $seq     = 0;

            for ($i = 0; $i < $acc['count']; $i++) {
                $date = $date->modify('+' . mt_rand(1, 7) . ' days')
                             ->setTime(mt_rand(7, 21), mt_rand(0, 59), mt_rand(0, 59));
                if ($date > $now) {
                    break;
                }

                // ~40% credits, 60% debits — a realistic everyday-account mix.
                $isCredit = mt_rand(1, 100) <= 40;
                $tpl      = $isCredit
                    ? $credits[array_rand($credits)]
                    : $debits[array_rand($debits)];

                [$desc, $cp, $cat, $min, $max] = $tpl;
                $amount = round(mt_rand($min * 100, $max * 100) / 100, 2);

                if ($isCredit) {
                    $balance += $amount;
                    $type = 'credit';
                } else {
                    // Keep the account from going negative on a debit.
                    if ($amount > $balance) {
                        $amount = round(max(1, $balance * 0.3), 2);
                    }
                    $balance -= $amount;
                    $type = 'debit';
                }

                $seq++;
                $insertTx->execute([
                    ':aid'  => $accountId,
                    ':ref'  => sprintf('TXN%05d%04d', $accountId, $seq),
                    ':type' => $type,
                    ':cat'  => $cat,
                    ':amt'  => number_format($amount, 2, '.', ''),
                    ':bal'  => number_format($balance, 2, '.', ''),
                    ':desc' => $desc,
                    ':cp'   => $cp,
                    ':at'   => $date->format('Y-m-d H:i:s'),
                ]);
                $totalTx++;
            }

            $setBalance->execute([
                ':bal' => number_format($balance, 2, '.', ''),
                ':aid' => $accountId,
            ]);

            printf("  %-10s %s (%s) -> %d tx, balance %.2f\n",
                $username, $acc['number'], $acc['type'], $seq, $balance);
        }
    }

    echo "\nDone. Seeded banking data ({$totalTx} transactions).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seeding failed: ' . $e->getMessage() . "\n");
    exit(1);
}
