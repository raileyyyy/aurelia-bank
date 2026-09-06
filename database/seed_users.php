<?php
/**
 * Aurelia Bank — User Seeder (development)
 * -----------------------------------------------------------------------------
 * Creates a small set of FICTIONAL test users (one admin, two customers) with
 * securely hashed passwords, so the Phase 2 authentication flow can be tested.
 *
 * Why a PHP script and not plain SQL?
 *   Passwords must never be stored in plaintext. This script uses PHP's
 *   password_hash() to generate a proper bcrypt hash for each account, then
 *   stores only the hash — exactly as the live registration flow would.
 *
 * It is idempotent: running it again resets these accounts to the known test
 * passwords (handy while developing). It refuses to run in production.
 *
 * HOW TO RUN
 *   From the project root, using the XAMPP PHP binary:
 *     "C:\xampp\php\php.exe" database\seed_users.php
 *   Or, if php is on your PATH:
 *     php database/seed_users.php
 *
 * This file lives under database/, which .htaccess blocks from web access.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

// Safety: never seed a production database with known test passwords.
if (APP_ENV !== 'development') {
    fwrite(STDERR, "Refusing to run: APP_ENV is not 'development'.\n");
    exit(1);
}

/**
 * Fictional test accounts. Passwords are placeholders for an academic demo —
 * they are documented in the README and are not real credentials.
 */
$users = [
    [
        'username'  => 'admin',
        'email'     => 'admin@aurelia.test',
        'full_name' => 'Sofia Reyes',
        'role'      => 'admin',
        'phone'     => '+1-202-555-0110',
        'address'   => '1 Aurelia Plaza, Metro City',
        'password'  => 'Admin@12345',
    ],
    [
        'username'  => 'jdoe',
        'email'     => 'john.doe@example.test',
        'full_name' => 'John Doe',
        'role'      => 'customer',
        'phone'     => '+1-202-555-0182',
        'address'   => '42 Maple Avenue, Metro City',
        'password'  => 'Password@123',
    ],
    [
        'username'  => 'msanders',
        'email'     => 'maria.sanders@example.test',
        'full_name' => 'Maria Sanders',
        'role'      => 'customer',
        'phone'     => '+1-202-555-0143',
        'address'   => '9 Birch Lane, Metro City',
        'password'  => 'Password@123',
    ],
];

// Insert or update on a unique-key (username/email) conflict, so re-running the
// seeder restores these accounts to the known test state.
$sql = 'INSERT INTO users (username, email, password_hash, full_name, role, phone, address, status)
        VALUES (:username, :email, :password_hash, :full_name, :role, :phone, :address, \'active\')
        ON DUPLICATE KEY UPDATE
            email         = VALUES(email),
            password_hash = VALUES(password_hash),
            full_name     = VALUES(full_name),
            role          = VALUES(role),
            phone         = VALUES(phone),
            address       = VALUES(address),
            status        = \'active\'';

try {
    $pdo  = db();
    $stmt = $pdo->prepare($sql);

    foreach ($users as $u) {
        $stmt->execute([
            ':username'      => $u['username'],
            ':email'         => $u['email'],
            ':password_hash' => password_hash($u['password'], PASSWORD_DEFAULT),
            ':full_name'     => $u['full_name'],
            ':role'          => $u['role'],
            ':phone'         => $u['phone'],
            ':address'       => $u['address'],
        ]);
        printf("  seeded %-10s (%s)\n", $u['username'], $u['role']);
    }

    echo "\nDone. " . count($users) . " test user(s) are ready.\n";
    echo "Test credentials:\n";
    echo "  admin     / Admin@12345   (administrator)\n";
    echo "  jdoe      / Password@123  (customer)\n";
    echo "  msanders  / Password@123  (customer)\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seeding failed: ' . $e->getMessage() . "\n");
    exit(1);
}
