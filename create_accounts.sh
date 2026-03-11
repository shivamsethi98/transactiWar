#!/bin/bash
# Creates test accounts for evaluators using PHP CLI

php -r '
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";dbname=" . getenv("DB_NAME") . ";charset=utf8mb4",
    getenv("DB_USER"),
    getenv("DB_PASS"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$accounts = [
    ["alice_sharma",   "alice.sharma@iith.ac.in",   "Alice@Secure#2026",  "Alice Sharma"],
    ["bob_kumar",      "bob.kumar@iith.ac.in",      "Bob#Strong@Pass9",   "Bob Kumar"],
    ["charlie_reddy",  "charlie.reddy@iith.ac.in",  "Charlie#War@2026",   "Charlie Reddy"],
    ["diana_patel",    "diana.patel@iith.ac.in",     "Diana@Transact#7",   "Diana Patel"],
    ["eve_gupta",      "eve.gupta@iith.ac.in",       "Eve#Guard@2026",     "Eve Gupta"],
];

$stmt = $pdo->prepare(
    "INSERT IGNORE INTO users (username, email, password, full_name, balance) VALUES (?, ?, ?, ?, 100.00)"
);

foreach ($accounts as $a) {
    $hash = password_hash($a[2], PASSWORD_BCRYPT, ["cost" => 12]);
    $stmt->execute([$a[0], $a[1], $hash, $a[3]]);
    echo "Account ready: " . $a[0] . PHP_EOL;
}

echo "All test accounts created." . PHP_EOL;
'
