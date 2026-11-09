<?php
/**
 * config.php
 * Database configuration and connection file
 */

// Error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Database credentials
$host     = 'localhost';
$dbname   = 'reli446436_ReliefWaveDB';
$username = 'reli446436_ReliefWaveDB';
$password = 'ReliefWaveDB';

// PDO connection
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please check configuration.");
}

// Safe query function
function safeQuery($query, $params = []) {
    global $pdo;
    if (!$pdo) return null;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        return null;
    }
}
?>
