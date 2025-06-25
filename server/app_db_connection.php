<?php
/**
 * Provides a centralized way to get a PDO connection to the application's SQLite database.
 * The database file is expected at app_data/monitoring_tool.sqlite relative to the project root.
 */

/**
 * Establishes and returns a PDO connection object to the SQLite database.
 * Configures PDO to throw exceptions on error and fetch associative arrays by default.
 *
 * @return PDO|null A PDO connection object on success, or null on failure.
 *                  Errors are logged if connection fails.
 */
function getAppDbConnection() {
    $db_file = __DIR__ . '/../app_data/monitoring_tool.sqlite'; // Path to the SQLite database file
    $pdo = null;

    try {
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Optional: fetch as associative arrays
    } catch (PDOException $e) {
        // In a real app, log this error and handle it gracefully
        // For now, we can die or return null, and the caller should check.
        error_log("App DB Connection Error: " . $e->getMessage());
        // Depending on context, you might throw the exception or return null
        // For a handler script, it might be better to throw and let a global error handler catch it,
        // or handle it directly in the handler.
        // For now, returning null. Caller MUST check.
        return null;
    }
    return $pdo;
}
?>
