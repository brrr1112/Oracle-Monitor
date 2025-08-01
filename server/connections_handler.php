<?php
/**
 * Handles CRUD operations for Oracle database connection profiles.
 * Ensures that users are authenticated and can only manage their own profiles.
 * Passwords for Oracle DB connections are encrypted before storage.
 *
 * Actions via GET/POST 'action' parameter:
 * - 'list': (GET) Lists all connection profiles for the logged-in user.
 * - 'add': (POST) Adds a new connection profile for the logged-in user.
 * - 'delete': (POST) Deletes a specified connection profile for the logged-in user.
 * - 'set_active_connection': (POST) Sets a connection profile as active in the user's session.
 */
session_start();

require_once 'app_db_connection.php';
require_once 'encryption_helper.php'; // For encrypt_data and decrypt_data

header('Content-Type: application/json');

// Ensure user is logged in for all actions handled by this script
if (!isset($_SESSION['app_user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please login.']);
    exit();
}
$app_user_id = (int)$_SESSION['app_user_id']; // Cast to int for safety

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = getAppDbConnection();

if (!$pdo) {
    error_log("connections_handler.php: Failed to get application database connection.");
    echo json_encode(['status' => 'error', 'message' => 'Application database service unavailable. Please try again later.']);
    exit();
}

switch ($action) {
    /**
     * Adds a new Oracle database connection profile.
     * Expects POST data: profile_name, db_host, db_port, db_service_name, db_user, db_password.
     */
    case 'add':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $profile_name = trim($_POST['profile_name'] ?? '');
            $db_host = trim($_POST['db_host'] ?? '');
            $db_port = trim($_POST['db_port'] ?? '1521');
            $db_service_name = trim($_POST['db_service_name'] ?? '');
            $db_user = trim($_POST['db_user'] ?? '');
            $db_password = $_POST['db_password'] ?? ''; // Plain text password from form

            if (empty($profile_name) || empty($db_host) || empty($db_service_name) || empty($db_user) || empty($db_password)) {
                echo json_encode(['status' => 'error', 'message' => 'All fields except port are required.']);
                exit();
            }
            if (empty($db_port)) $db_port = '1521';


            // Encrypt the password
            $encrypted_db_password = encrypt_data($db_password);
            if ($encrypted_db_password === false) {
                error_log("Encryption failed for user_id: {$app_user_id}, profile: {$profile_name}");
                echo json_encode(['status' => 'error', 'message' => 'Failed to secure database password. Please try again.']);
                exit();
            }

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO database_connections (user_id, profile_name, db_host, db_port, db_service_name, db_user, encrypted_db_password, created_at)
                     VALUES (:user_id, :profile_name, :db_host, :db_port, :db_service_name, :db_user, :encrypted_db_password, datetime('now'))"
                );
                $stmt->bindParam(':user_id', $app_user_id, PDO::PARAM_INT);
                $stmt->bindParam(':profile_name', $profile_name);
                $stmt->bindParam(':db_host', $db_host);
                $stmt->bindParam(':db_port', $db_port);
                $stmt->bindParam(':db_service_name', $db_service_name);
                $stmt->bindParam(':db_user', $db_user);
                $stmt->bindParam(':encrypted_db_password', $encrypted_db_password);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Connection profile added successfully.']);
                } else {
                    error_log("Failed to add connection profile for user_id: {$app_user_id} - " . implode(":", $stmt->errorInfo()));
                    echo json_encode(['status' => 'error', 'message' => 'Failed to add connection profile.']);
                }
            } catch (PDOException $e) {
                error_log("PDOException on add connection for user_id: {$app_user_id} - " . $e->getMessage());
                // Check for unique constraint violation on profile_name for the same user (if we add such a constraint)
                if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                     echo json_encode(['status' => 'error', 'message' => 'A profile with this name might already exist for your account.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error while adding profile.']);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method for add.']);
        }
        break;

    /**
     * Lists all Oracle database connection profiles for the logged-in user.
     * Does not return encrypted passwords.
     */
    case 'list':
        try {
            $stmt = $pdo->prepare(
                "SELECT conn_id, profile_name, db_host, db_port, db_service_name, db_user, created_at
                 FROM database_connections
                 WHERE user_id = :user_id ORDER BY profile_name ASC"
            );
            $stmt->bindParam(':user_id', $app_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'connections' => $connections]);
        } catch (PDOException $e) {
            error_log("PDOException on list connections for user_id: {$app_user_id} - " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve connection profiles.']);
        }
        break;

    /**
     * Deletes a specified Oracle database connection profile for the logged-in user.
     * Expects POST data: conn_id.
     */
    case 'delete':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $conn_id = $_POST['conn_id'] ?? null;
            if (empty($conn_id) || !is_numeric($conn_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid connection ID.']);
                exit();
            }

            try {
                $stmt = $pdo->prepare(
                    "DELETE FROM database_connections
                     WHERE conn_id = :conn_id AND user_id = :user_id"
                );
                $stmt->bindParam(':conn_id', $conn_id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $app_user_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['status' => 'success', 'message' => 'Connection profile deleted successfully.']);
                    } else {
                        // Either conn_id didn't exist or didn't belong to this user
                        echo json_encode(['status' => 'error', 'message' => 'Connection profile not found or access denied.']);
                    }
                } else {
                     error_log("Failed to delete connection profile conn_id: {$conn_id} for user_id: {$app_user_id} - " . implode(":", $stmt->errorInfo()));
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete connection profile.']);
                }
            } catch (PDOException $e) {
                error_log("PDOException on delete connection for user_id: {$app_user_id}, conn_id: {$conn_id} - " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Database error while deleting profile.']);
            }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Invalid request method for delete.']);
        }
        break;

    // case 'update': // Placeholder for future update functionality
    //     // Similar logic to add, but with UPDATE statement and conn_id
    //     break;

    /**
     * Sets the active Oracle database connection profile in the user's session.
     * Expects POST data: conn_id.
     * Verifies that the connection ID belongs to the logged-in user.
     */
    case 'set_active_connection':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $conn_id = $_POST['conn_id'] ?? null;

            if (empty($conn_id) || !is_numeric($conn_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid connection ID provided.']);
                exit();
            }

            try {
                // Verify this conn_id belongs to the logged-in user and get its name
                $stmt = $pdo->prepare(
                    "SELECT profile_name
                     FROM database_connections
                     WHERE conn_id = :conn_id AND user_id = :user_id"
                );
                $stmt->bindParam(':conn_id', $conn_id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $app_user_id, PDO::PARAM_INT);
                $stmt->execute();
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($profile) {
                    $_SESSION['selected_oracle_conn_id'] = (int)$conn_id;
                    $_SESSION['selected_oracle_profile_name'] = $profile['profile_name'];
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Active Oracle DB connection set to: ' . htmlspecialchars($profile['profile_name']),
                        'selected_profile_name' => htmlspecialchars($profile['profile_name'])
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Connection profile not found or access denied.']);
                }
            } catch (PDOException $e) {
                error_log("PDOException on set_active_connection for user_id: {$app_user_id}, conn_id: {$conn_id} - " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Database error while setting active connection.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method for set_active_connection.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}
?>
