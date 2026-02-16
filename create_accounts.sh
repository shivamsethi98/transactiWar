#!/bin/bash
# Creates test accounts for evaluators using PHP CLI

php -r "
\$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

\$accounts = [
    ['testuser1', 'test1@transactiwar.com', 'TestPass1!'],
    ['testuser2', 'test2@transactiwar.com', 'TestPass2!'],
    ['testuser3', 'test3@transactiwar.com', 'TestPass3!'],
    ['testuser4', 'test4@transactiwar.com', 'TestPass4!'],
    ['testuser5', 'test5@transactiwar.com', 'TestPass5!'],
];

\$stmt = \$pdo->prepare(
    'INSERT IGNORE INTO users (username, email, password, balance) VALUES (?, ?, ?, 100.00)'
);

foreach (\$accounts as \$a) {
    \$hash = password_hash(\$a[2], PASSWORD_BCRYPT, ['cost' => 12]);
    \$stmt->execute([\$a[0], \$a[1], \$hash]);
    echo 'Account ready: ' . \$a[0] . PHP_EOL;
}

echo 'All test accounts created.' . PHP_EOL;
"
