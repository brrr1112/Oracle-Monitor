<?php
// server/oracle.php
// This file is now responsible for establishing an Oracle DB connection
// based on a logged-in application user's selected connection profile.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/app_db_connection.php';
require_once __DIR__ . '/encryption_helper.php';

/**
 * Establishes a connection to a user's target Oracle database.
 *
 * @param int $app_user_id The ID of the logged-in application user.
 * @param int $connection_profile_id The ID of the database_connections profile to use.
 * @return resource|false The OCI connection resource on success, or false on failure.
 *                        Sets a session error message on failure.
 */
function establish_oracle_connection(int $app_user_id, int $connection_profile_id) {
    $app_pdo = getAppDbConnection();
    if (!$app_pdo) {
        $_SESSION['oracle_connection_error'] = "Failed to connect to application database.";
        error_log("establish_oracle_connection: Failed to get App DB connection for user {$app_user_id}, profile {$connection_profile_id}");
        return false;
    }

    $oracle_conn_details = null;
    try {
        $stmt = $app_pdo->prepare(
            "SELECT db_host, db_port, db_service_name, db_user, encrypted_db_password
             FROM database_connections
             WHERE user_id = :user_id AND conn_id = :conn_id"
        );
        $stmt->bindParam(':user_id', $app_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':conn_id', $connection_profile_id, PDO::PARAM_INT);
        $stmt->execute();
        $oracle_conn_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oracle_conn_details) {
            $_SESSION['oracle_connection_error'] = "Selected Oracle connection profile not found or access denied.";
            error_log("establish_oracle_connection: Profile not found for user {$app_user_id}, profile {$connection_profile_id}");
            return false;
        }
    } catch (PDOException $e) {
        $_SESSION['oracle_connection_error'] = "Error fetching Oracle connection profile: " . $e->getMessage();
        error_log("establish_oracle_connection: PDOException fetching profile for user {$app_user_id}, profile {$connection_profile_id} - " . $e->getMessage());
        return false;
    }

    // Decrypt the Oracle DB password
    $db_password_plain = decrypt_data($oracle_conn_details['encrypted_db_password']);
    if ($db_password_plain === false) {
        $_SESSION['oracle_connection_error'] = "Failed to decrypt database credentials. Configuration issue?";
        error_log("establish_oracle_connection: Decryption failed for user {$app_user_id}, profile {$connection_profile_id}");
        return false;
    }

    $db_user = $oracle_conn_details['db_user'];
    $host = $oracle_conn_details['db_host'];
    $port = $oracle_conn_details['db_port'];
    $service_name = $oracle_conn_details['db_service_name'];

    $connection_string = "//{$host}:{$port}/{$service_name}";

    // Determine connection mode (e.g., OCI_SYSDBA). This might need to be stored per-profile too.
    // For now, assuming a default. If the user needs SYSDBA, they must provide such credentials.
    // Using an empty string for mode for default connection.
    // If specific roles like SYSDBA are needed, the user must provide credentials with that privilege.
    // It's generally better if the monitoring user is a dedicated, less-privileged user.
    $connection_mode = OCI_DEFAULT; // Default mode
    // Example: if ($oracle_conn_details['connect_as_sysdba'] == 1) $connection_mode = OCI_SYSDBA;


    // Suppress OCI errors temporarily to handle them manually
    $original_error_reporting = error_reporting();
    error_reporting(0); // Turn off error reporting
    $conn = @oci_connect($db_user, $db_password_plain, $connection_string, '', $connection_mode);
    error_reporting($original_error_reporting); // Restore error reporting

    if (!$conn) {
        $e = oci_error();
        $error_message = $e ? htmlentities($e['message']) : "Unknown Oracle connection error.";
        $_SESSION['oracle_connection_error'] = "Oracle Connection Failed: " . $error_message;
        error_log("establish_oracle_connection: OCI Connect Error for user {$app_user_id}, profile {$connection_profile_id} to {$connection_string} as {$db_user} - " . $error_message);
        return false;
    }

    // Clear any previous error message if connection is successful
    unset($_SESSION['oracle_connection_error']);
    return $conn;
}


// The old direct POST handling logic and session-based re-connection logic are removed.
// This file will now primarily be included by other scripts that need an Oracle connection
// after the application user has logged in and selected a target DB profile.

// Example of how results.php might use this:
/*
if (!isset($_SESSION['app_user_id'])) {
    // Handle not logged in to app
    echo json_encode(['error' => 'Application login required.']);
    exit;
}
if (!isset($_SESSION['selected_oracle_conn_id'])) {
    // Handle no Oracle DB profile selected by the user
    echo json_encode(['error' => 'No Oracle database profile selected for monitoring.']);
    exit;
}

$app_user_id = $_SESSION['app_user_id'];
$selected_oracle_conn_id = $_SESSION['selected_oracle_conn_id'];

$conn = establish_oracle_connection($app_user_id, $selected_oracle_conn_id);

if (!$conn) {
    $error_message = $_SESSION['oracle_connection_error'] ?? 'Failed to connect to Oracle database.';
    echo json_encode(['error' => $error_message]);
    exit;
}

// ... proceed with using $conn for Oracle queries ...
// oci_close($conn); // Close when done with operations for this request.
*/

?>
