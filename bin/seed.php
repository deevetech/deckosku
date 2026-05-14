<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Env;
use App\Models\User;

$email    = (string) Env::get('ADMIN_EMAIL', 'admin@decko.local');
$password = (string) Env::get('ADMIN_PASSWORD', 'ChangeMe-on-first-login!');

if (User::findByEmail($email) !== null) {
    echo "✓ Admin user already exists ({$email}). Nothing to do.\n";
    exit(0);
}

$id = User::create('Administrator', $email, $password, 'admin');

echo "✓ Admin user created.\n";
echo "  id:       {$id}\n";
echo "  email:    {$email}\n";
echo "  password: {$password}\n";
echo "  → CHANGE THIS PASSWORD AFTER FIRST LOGIN.\n";
