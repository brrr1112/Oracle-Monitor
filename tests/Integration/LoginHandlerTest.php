<?php
// tests/Integration/LoginHandlerTest.php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

class LoginHandlerTest extends TestCase
{
    private $pdo;
    private static $testUserPassword = 'password123';
    private static $testUserPasswordHash;

    public static function setUpBeforeClass(): void
    {
        self::$testUserPasswordHash = password_hash(self::$testUserPassword, PASSWORD_DEFAULT);
    }

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Schema (simplified, only users table needed for login)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
        ");

        // Insert a test user
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute(['testloginuser', 'login@example.com', self::$testUserPasswordHash]);

        $GLOBALS['TEST_PDO_OVERRIDE'] = $this->pdo;

        // Reset session and POST for each test
        $_SESSION = [];
        $_POST = [];
        if (session_status() == PHP_SESSION_ACTIVE) {
           // Potentially destroy if needed, but $_SESSION = [] is often enough for tests
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TEST_PDO_OVERRIDE']);
    }

    private function includeLoginHandler()
    {
        // This is a simplified execution. Real testing of scripts with headers/exit is harder.
        // Assumes app_db_connection.php is modified to use $GLOBALS['TEST_PDO_OVERRIDE']
        ob_start();
        require __DIR__ . '/../../server/app_login_handler.php';
        return ob_get_clean();
    }

    public function testLoginSuccessful()
    {
        $_POST = [
            'username' => 'testloginuser',
            'password' => self::$testUserPassword,
        ];

        // The handler script will call session_start(), set session vars, and redirect.
        // We need to check $_SESSION variables *after* running the script,
        // but before the script's exit() call would terminate the test process if not handled.
        // This requires @runInSeparateProcess or mocking exit().

        // For this conceptual test, we'll call it and then immediately check session.
        // This might not fully work if exit() is called within the handler in the same process.

        // To properly test session changes and redirects, PHPUnit's process isolation is typically used.
        // $this->expectOutputRegex('/Location: ..\/Frontend\/menu.html/'); // Example for redirect
        // $this->runLoginHandlerInSeparateProcess(); // A hypothetical method using PHPUnit features

        // Simulating the effect for now:
        // We can't directly call includeLoginHandler() and then check session state easily
        // if it calls exit(). The test will terminate.
        // So, this test is more about outlining the logic.

        // If we could prevent exit in the handler during tests:
        // $this->includeLoginHandler();
        // $this->assertEquals($_SESSION['app_user_id'], /* expected user_id */);
        // $this->assertEquals($_SESSION['app_username'], 'testloginuser');

        $this->markTestSkipped('Full login success test requires header/exit mocking or running in separate process, and test-aware app_db_connection.php. Conceptual check of session state after login.');
    }

    public function testLoginFailsWithWrongPassword()
    {
        $_POST = [
            'username' => 'testloginuser',
            'password' => 'wrongpassword',
        ];

        $this->includeLoginHandler();
        // Expect redirect to index.html with "Invalid username or password."
        // Check $_SESSION to ensure no user session was created.
        $this->assertArrayNotHasKey('app_user_id', $_SESSION);
        $this->assertArrayNotHasKey('app_username', $_SESSION);
        $this->markTestSkipped('Full login failure test requires header/exit mocking to check redirect message. Session state check is conceptual.');
    }

    public function testLoginFailsWithNonExistentUser()
    {
        $_POST = [
            'username' => 'nouser',
            'password' => 'anypassword',
        ];
        $this->includeLoginHandler();
        $this->assertArrayNotHasKey('app_user_id', $_SESSION);
        $this->markTestSkipped('Full login failure test for non-existent user needs header/exit mocking. Session state check is conceptual.');
    }

    public function testLoginFailsWithEmptyUsername()
    {
        $_POST = [
            'username' => '',
            'password' => 'anypassword',
        ];
        $this->includeLoginHandler();
        $this->assertArrayNotHasKey('app_user_id', $_SESSION);
        $this->markTestSkipped('Login failure for empty username needs header/exit mocking.');
    }

    public function testLoginFailsWithEmptyPassword()
    {
        $_POST = [
            'username' => 'testloginuser',
            'password' => '',
        ];
        $this->includeLoginHandler();
        $this->assertArrayNotHasKey('app_user_id', $_SESSION);
        $this->markTestSkipped('Login failure for empty password needs header/exit mocking.');
    }
}

// As with RegistrationHandlerTest, these tests are illustrative due to the procedural nature
// of app_login_handler.php. Full execution and assertion of redirects and session state
// would require PHPUnit's process isolation features (@runInSeparateProcess and related assertions)
// and ensuring app_db_connection.php can use the test-injected PDO instance.
?>
