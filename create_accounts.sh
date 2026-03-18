#!/bin/bash
# Creates evaluator and Phase2 accounts using PHP CLI

php -r '
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";dbname=" . getenv("DB_NAME") . ";charset=utf8mb4",
    getenv("DB_USER"),
    getenv("DB_PASS"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare(
    "INSERT INTO users (username, email, password, full_name, balance)
     VALUES (?, ?, ?, ?, 100.00)
     ON DUPLICATE KEY UPDATE
        password = VALUES(password),
        full_name = COALESCE(VALUES(full_name), full_name)"
);

$inserted = 0;

$defaultAccounts = [
    ["alice_sharma",   "alice.sharma@iith.ac.in",   "Alice@Secure#2026",  "Alice Sharma"],
    ["bob_kumar",      "bob.kumar@iith.ac.in",      "Bob#Strong@Pass9",   "Bob Kumar"],
    ["charlie_reddy",  "charlie.reddy@iith.ac.in",  "Charlie#War@2026",   "Charlie Reddy"],
    ["diana_patel",    "diana.patel@iith.ac.in",    "Diana@Transact#7",   "Diana Patel"],
    ["eve_gupta",      "eve.gupta@iith.ac.in",      "Eve#Guard@2026",     "Eve Gupta"],
];

foreach ($defaultAccounts as $account) {
    $hash = password_hash($account[2], PASSWORD_BCRYPT, ["cost" => 12]);
    $stmt->execute([$account[0], $account[1], $hash, $account[3]]);
    $inserted += $stmt->rowCount();
}

$csvPath = getenv("ACCOUNTS_CSV");
if (!$csvPath) {
    $candidatePaths = [
        "/tmp/phase2.csv",
        "/var/www/html/Phase2.csv",
        "/phase2.csv",
    ];

    foreach ($candidatePaths as $candidatePath) {
        if (is_readable($candidatePath)) {
            $csvPath = $candidatePath;
            break;
        }
    }

    if (!$csvPath) {
        $csvPath = "/tmp/phase2.csv";
    }
}

$csvInserted = 0;

if (is_readable($csvPath)) {
    $handle = fopen($csvPath, "r");
    if ($handle !== false) {
        $header = fgetcsv($handle);
        if (is_array($header)) {
            $headerMap = [];
            foreach ($header as $index => $column) {
                $normalized = preg_replace("/^\xEF\xBB\xBF/", "", (string) $column);
                $headerMap[strtolower(trim($normalized))] = $index;
            }

            $usernameIdx = $headerMap["username"] ?? null;
            $emailIdx = $headerMap["email"] ?? null;
            $passwordIdx = $headerMap["password"] ?? null;
            $nameIdx = $headerMap["name"] ?? ($headerMap["full_name"] ?? null);

            if ($usernameIdx !== null && $emailIdx !== null && $passwordIdx !== null) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (!isset($row[$usernameIdx], $row[$emailIdx], $row[$passwordIdx])) {
                        continue;
                    }

                    $username = trim($row[$usernameIdx]);
                    $email = trim($row[$emailIdx]);
                    $password = rtrim((string) $row[$passwordIdx], "\r\n");
                    $fullName = $nameIdx !== null && isset($row[$nameIdx]) ? trim($row[$nameIdx]) : null;

                    if ($username === "" || $email === "" || $password === "") {
                        continue;
                    }

                    $hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);
                    $stmt->execute([$username, $email, $hash, $fullName]);
                    $rows = $stmt->rowCount();
                    $csvInserted += $rows;
                    $inserted += $rows;
                }
            } else {
                echo "Phase2 CSV import skipped: missing required headers." . PHP_EOL;
            }
        }

        fclose($handle);
    }
} else {
    echo "Phase2 CSV not found at " . $csvPath . ". Skipping CSV import." . PHP_EOL;
}

echo "Accounts ensured. Inserted this run: " . $inserted . " (Phase2 CSV: " . $csvInserted . ")" . PHP_EOL;
'
