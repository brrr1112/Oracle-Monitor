<?php
/**
 * Handles application user login requests.
 * Verifies credentials against the 'users' table in the application's SQLite database.
 * Manages PHP session creation and regeneration upon successful login.
 * Redirects user to menu or back to login page with messages.
 */
session_start();

require_once 'app_db_connection.php'; // Provides getAppDbConnection()

/**
 * Redirects the user to a specified URL with a message and type in query parameters.
 * Exits script execution after sending the header.
 *
 * @param string $url The URL to redirect to.
 * @param string $message The message to display to the user.
 * @param string $type The type of message ('success' or 'error'), affects display.
 */
function redirect_with_message($url, $message, $type = 'error') {
    $message = urlencode($message);
    header("Location: {$url}?message={$message}&type={$type}");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $login_page = '../Frontend/index.html'; // This is now the app login page

    if (empty($username) || empty($password)) {
        redirect_with_message($login_page, 'Username and password are required.');
    }

    $pdo = getAppDbConnection();
    if (!$pdo) {
        redirect_with_message($login_page, 'Login failed due to a server error. Please try again later.');
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, start session
            $_SESSION['app_user_id'] = $user['user_id'];
            $_SESSION['app_username'] = $user['username'];

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Redirect to the main menu or a new dashboard page
            // This menu.html will later need a way to select which Oracle DB to connect to
            header('Location: ../Frontend/menu.html');
            exit();
        } else {
            redirect_with_message($login_page, 'Invalid username or password.');
        }

    } catch (PDOException $e) {
        error_log("Login PDOException: " . $e->getMessage());
        redirect_with_message($login_page, 'An error occurred during login. Please try again later.');
    }

} else {
    // Not a POST request
    header('Location: ../Frontend/index.html');
    exit();
}
?>
