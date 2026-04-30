<?php
/**
 * Database Connection - PDO with prepared statements
 * Smart Student Management System
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management');
define('DB_USER', 'root');        // Change for production
define('DB_PASS', '');            // Change for production
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error instead of displaying it
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

/**
 * Log an activity to the activity_log table
 */
function logActivity(string $action, string $description, ?int $studentId = null): void {
    try {
        $pdo = getDB();
        $adminId = $_SESSION['admin_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare(
            "INSERT INTO activity_log (admin_id, action, description, student_id, ip_address)
             VALUES (:admin_id, :action, :desc, :student_id, :ip)"
        );
        $stmt->execute([
            ':admin_id'   => $adminId,
            ':action'     => $action,
            ':desc'       => $description,
            ':student_id' => $studentId,
            ':ip'         => $ip,
        ]);
    } catch (PDOException $e) {
        error_log("Activity log failed: " . $e->getMessage());
    }
}

/**
 * Sanitize string input
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Return JSON response and exit
 */
function jsonResponse(bool $success, string $message, array $data = []): never {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
