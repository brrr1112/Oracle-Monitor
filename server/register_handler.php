<?php
// server/register_handler.php
session_start(); // Not strictly needed for registration but good practice if you set session messages

require_once 'app_db_connection.php';

function redirect_with_message($url, $message, $type = 'error') {
    $message = urlencode($message);
    header("Location: {$url}?message={$message}&type={$type}");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $registration_page = '../Frontend/register.php';

    // Basic Validations
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        redirect_with_message($registration_page, 'All fields are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_message($registration_page, 'Invalid email format.');
    }

    if (strlen($password) < 6) {
        redirect_with_message($registration_page, 'Password must be at least 6 characters long.');
    }

    if ($password !== $confirm_password) {
        redirect_with_message($registration_page, 'Passwords do not match.');
    }

    $pdo = getAppDbConnection();
    if (!$pdo) {
        // This error is more for the server admin, but we can show a generic message
        redirect_with_message($registration_page, 'Registration failed due to a server error. Please try again later.');
    }

    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        if ($stmt->fetch()) {
            redirect_with_message($registration_page, 'Username already taken.');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            redirect_with_message($registration_page, 'Email already registered.');
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($password_hash === false) {
            error_log("Password hashing failed for user: " . $username);
            redirect_with_message($registration_page, 'Registration failed due to a security error. Please try again.');
        }

        // Insert the new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (:username, :email, :password_hash, datetime('now'))");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);

        if ($stmt->execute()) {
            // Redirect to login page with a success message
            redirect_with_message('../Frontend/index.html', 'Registration successful! Please login.', 'success');
        } else {
            error_log("Failed to insert user: " . $username . " - " . implode(":", $stmt->errorInfo()));
            redirect_with_message($registration_page, 'Registration failed. Please try again.');
        }

    } catch (PDOException $e) {
        error_log("Registration PDOException: " . $e->getMessage());
        redirect_with_message($registration_page, 'An error occurred during registration. Please try again later.');
    }

} else {
    // Not a POST request, redirect to registration page or show an error
    header('Location: ../Frontend/register.php');
    exit();
}
?>
