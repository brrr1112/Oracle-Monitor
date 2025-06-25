<?php
// server/app_db_connection.php

function getAppDbConnection() {
    $db_file = __DIR__ . '/../app_data/monitoring_tool.sqlite';
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
