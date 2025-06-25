<?php
// server/initialize_app_db.php

// Define path for the SQLite database
$db_dir = __DIR__ . '/../app_data';
$db_file = $db_dir . '/monitoring_tool.sqlite';

// Create app_data directory if it doesn't exist
if (!is_dir($db_dir)) {
    if (mkdir($db_dir, 0755, true)) {
        echo "Directory {$db_dir} created.\n";
    } else {
        die("Failed to create directory {$db_dir}. Check permissions.\n");
    }
} else {
    echo "Directory {$db_dir} already exists.\n";
}

// Attempt to create/connect to the SQLite database
try {
    $pdo = new PDO('sqlite:' . $db_file);
    // Set error mode to exceptions for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Successfully connected to/created database at {$db_file}.\n";

    // SQL to create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS database_connections (
        conn_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        profile_name TEXT NOT NULL,
        db_host TEXT NOT NULL,
        db_port TEXT NOT NULL,
        db_service_name TEXT NOT NULL,
        db_user TEXT NOT NULL,
        encrypted_db_password TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );

    -- Optional: Indexes for performance
    CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
    CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
    CREATE INDEX IF NOT EXISTS idx_database_connections_user_id ON database_connections(user_id);
    ";

    // Execute the SQL
    $pdo->exec($sql);
    echo "Tables created successfully (if they didn't already exist).\n";

    // Example: Protect the app_data directory with .htaccess if on Apache
    if (is_dir($db_dir) && strpos(strtolower(php_sapi_name()), 'apache') !== false) {
        $htaccess_content = "Deny from all\n";
        if (!file_exists($db_dir . '/.htaccess')) {
            if (file_put_contents($db_dir . '/.htaccess', $htaccess_content)) {
                echo ".htaccess file created in {$db_dir} to protect database files.\n";
            } else {
                echo "Warning: Could not create .htaccess file in {$db_dir}. Ensure this directory is not web-accessible.\n";
            }
        }
    }


} catch (PDOException $e) {
    die("Database connection or table creation failed: " . $e->getMessage() . "\n");
}

echo "Application database initialization script complete.\n";
echo "To use, ensure this script is run once (e.g., via CLI: php server/initialize_app_db.php) or the logic is integrated into app startup.\n";
echo "Make sure the web server has write permissions to the 'app_data' directory if the DB is created/modified by web requests initially.\n";

?>
