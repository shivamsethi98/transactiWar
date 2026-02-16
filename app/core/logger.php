<?php
/**
 * Activity logging.
 * Logs: page, username, timestamp, client IP (as required by spec).
 */

function log_activity(string $page): void
{
    try {
        $pdo = get_db();

        $stmt = $pdo->prepare(
            'INSERT INTO activity_log (user_id, username, page, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            current_user_id(),
            current_username(),
            sanitize_string($page, 255),
            get_client_ip(),
            sanitize_string($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 500),
        ]);
    } catch (PDOException $ex) {
        // Logging should never break the application
        error_log('Activity log failed: ' . $ex->getMessage());
    }
}
