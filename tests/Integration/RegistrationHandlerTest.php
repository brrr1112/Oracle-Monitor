<?php
// tests/Integration/RegistrationHandlerTest.php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

// It's assumed that app_db_connection.php can be configured for a test DB,
// e.g., by checking an environment variable like APP_ENV === 'testing'.
// For this example, we'll manually set up an in-memory SQLite DB for each test method.

class RegistrationHandlerTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Setup in-memory SQLite database for each test
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Run schema from initialize_app_db.php content
        $schemaSql = "
        CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS database_connections (
            conn_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL,
            profile_name TEXT NOT NULL, db_host TEXT NOT NULL, db_port TEXT NOT NULL,
            db_service_name TEXT NOT NULL, db_user TEXT NOT NULL,
            encrypted_db_password TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
        CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
        ";
        $this->pdo->exec($schemaSql);

        // Make this PDO instance available to app_db_connection.php when it's included by the handler.
        // This is the tricky part with procedural code. We can define a global or a static property.
        // Or, modify app_db_connection.php to check for a test environment variable.
        // For simplicity here, we'll rely on a global that the test-included handler can see.
        $GLOBALS['TEST_PDO_OVERRIDE'] = $this->pdo;

        // Reset session for each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TEST_PDO_OVERRIDE']);
        $_POST = []; // Clean up POST
        // session_destroy(); // If session was started by handler
    }

    private function includeRegisterHandler()
    {
        // This function will simulate including and running the handler.
        // It needs to be able to catch header() calls.
        // PHPUnit can do this with @runInSeparateProcess and annotations, or by overriding header().
        // For now, we'll capture output and check session messages.

        // Override app_db_connection.php for testing
        // Create a temporary app_db_connection.php that uses $GLOBALS['TEST_PDO_OVERRIDE']
        $testDbConnectionContent = '<?php
            function getAppDbConnection() {
                if (isset($GLOBALS["TEST_PDO_OVERRIDE"])) {
                    return $GLOBALS["TEST_PDO_OVERRIDE"];
                }
                // Fallback to original if not in test override context (should not happen in this test)
                $db_file = __DIR__ . "/../../app_data/monitoring_tool.sqlite";
                $pdo = new PDO("sqlite:" . $db_file);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $pdo;
            } ?>';
        file_put_contents(__DIR__ . '/../../server/app_db_connection.php.test_override.php', $testDbConnectionContent);

        // The actual handler will include the original app_db_connection.php.
        // This is where true DI or a service container would be very helpful.
        // A more advanced setup would use a bootstrap file for tests that defines such overrides.

        // We can't easily test redirects without running in separate processes or more complex setup.
        // What we can do is check $_SESSION messages or if the script exits.
        // For this example, let's assume we can check session messages or direct output if header() is mocked/disabled.

        // To capture header() calls, we would need a more advanced setup or run tests in separate processes.
        // For now, we'll assume the redirect_with_message function in the handler will set a message
        // that we can inspect if we could prevent exit() and header() calls.

        // This is a simplified execution. Real testing of scripts with headers/exit is harder.
        ob_start();
        // Temporarily rename original, include test version, then restore
        // This is getting very hacky due to procedural includes.
        $original_db_conn_path = __DIR__ . '/../../server/app_db_connection.php';
        $test_db_conn_path_override = __DIR__ . '/../../server/app_db_connection.php.test_override.php';

        // For the test, we need register_handler to use our overridden getAppDbConnection
        // One way: rename original, put test version in place, include handler, then restore.
        // This is complex and error-prone.

        // A simpler (but still not ideal) approach for this context:
        // The handler script calls getAppDbConnection(). If we can ensure our test PDO is returned, it's a start.
        // The $GLOBALS['TEST_PDO_OVERRIDE'] combined with a slight modification in a
        // *test-specific version* of app_db_connection.php is the path taken here.
        // The actual `require_once 'app_db_connection.php';` in the handler will pick up the *original* file.
        // This means the global override method is the most viable without changing the SUT.

        // To make $GLOBALS['TEST_PDO_OVERRIDE'] work, app_db_connection.php needs to be aware of it.
        // Let's assume for a moment app_db_connection.php was modified like:
        // function getAppDbConnection() {
        //     if (isset($GLOBALS['TEST_PDO_OVERRIDE'])) return $GLOBALS['TEST_PDO_OVERRIDE'];
        //     // ... original code ...
        // }
        // If not, these tests for DB interaction won't work as intended without more setup.
        // For now, let's proceed as if it can pick up the global PDO for testing the logic flow.

        require __DIR__ . '/../../server/register_handler.php';
        return ob_get_clean();
    }

    public function testRegistrationSuccessful()
    {
        $_POST = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
        ];

        // This test will likely fail to assert correctly without proper header/exit mocking
        // and without app_db_connection.php being test-aware.
        // We expect a redirect. PHPUnit can test for `expectOutputRegex` or `header()` calls if run in separate process.

        // For now, let's check the database state if we assume the handler ran with our test DB.
        // This requires app_db_connection.php to be modified to use $GLOBALS['TEST_PDO_OVERRIDE']
        // if it exists. Let's make that modification conceptually for this test.

        // **Conceptual modification to app_db_connection.php for testing:**
        // <?php
        // function getAppDbConnection() {
        //     if (isset($GLOBALS['TEST_PDO_OVERRIDE'])) { return $GLOBALS['TEST_PDO_OVERRIDE']; }
        //     // ... original code ...
        // }
        // ?>
    }

        // Assuming the above modification is made (or a similar test strategy for PDO injection):
        $this->includeRegisterHandler(); // This will redirect and exit.
                                   // To test DB state, we'd need to prevent exit or run in separate process.

        // This assertion would run *after* the handler script has exited due to redirect.
        // So, we cannot directly test DB state this way unless we prevent the exit.
        // For this example, we'll mark it as a placeholder for what *should* be tested.

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = 'testuser'");
        $stmt->execute();
        $user = $stmt->fetch();

        $this->assertNotFalse($user, "User should be inserted into the database.");
        if ($user) {
            $this->assertEquals('test@example.com', $user['email']);
            $this->assertTrue(password_verify('password123', $user['password_hash']));
        }
        // We also need to assert the redirect. PHPUnit has ways to do this,
        // but it's more complex than simple unit tests.
        // $this->expectOutputRegex('/Location: ..\/Frontend\/index.html\?message=.*type=success/');
        // Requires @runInSeparateProcess and careful output buffer handling.
        $this->markTestSkipped('Full test requires header/exit mocking or running in separate process, and test-aware app_db_connection.php. DB state check is conceptual.');
    }

    public function testRegistrationFailsIfUsernameExists()
    {
        // Pre-populate user
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $this->pdo->exec("INSERT INTO users (username, email, password_hash, created_at) VALUES ('existinguser', 'exists@example.com', '$hash', datetime('now'))");

        $_POST = [
            'username' => 'existinguser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
        ];

        // We would expect a redirect back to register.php with an error.
        // Capturing this redirect and message is key.
        // For now, checking if a new user was NOT added with the new email.
        $this->includeRegisterHandler();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = 'new@example.com'");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertEquals(0, $result['count'], "User with new email should not have been created if username exists.");
        $this->markTestSkipped('Full test requires header/exit mocking and test-aware app_db_connection.php. Conceptual check.');
    }

    public function testRegistrationFailsIfPasswordsDoNotMatch()
    {
        $_POST = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password456', // Mismatch
        ];
        // Expect redirect to register.php with error "Passwords do not match."
        // This typically involves checking $_SESSION['message'] or similar if not using direct output.
        // Or, if redirect_with_message is modified for testing to return its URL instead of redirecting.
        $this->includeRegisterHandler();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'testuser'");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertEquals(0, $result['count'], "User should not be created if passwords do not match.");
        $this->markTestSkipped('Full test requires header/exit mocking and test-aware app_db_connection.php. Conceptual check.');
    }

    // Other tests:
    // - Empty fields
    // - Invalid email format
    // - Password too short
    // - Email already exists
}

// Note: Testing procedural scripts that issue headers and exit calls is complex with PHPUnit
// without running tests in separate processes (@runInSeparateProcess) or significantly refactoring
// the scripts into testable units (classes/functions that return values instead of redirecting).
// The above tests are structured to highlight these challenges and provide a conceptual path.
// A critical part is making app_db_connection.php return the test PDO instance during tests.
// One way is to modify app_db_connection.php to check for $GLOBALS['TEST_PDO_OVERRIDE'].
// Example modification for app_db_connection.php for these tests to work better:
/*
<?php
function getAppDbConnection() {
    if (isset($GLOBALS['TEST_PDO_OVERRIDE']) && $GLOBALS['TEST_PDO_OVERRIDE'] instanceof \PDO) {
        return $GLOBALS['TEST_PDO_OVERRIDE'];
    }
    // Original connection logic
    $db_file = __DIR__ . '/../app_data/monitoring_tool.sqlite';
    // ... rest of original code
    try {
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("App DB Connection Error: " . $e->getMessage());
        return null;
    }
}
?>
*/
// This change to app_db_connection.php would allow the tests to inject the in-memory PDO.
// The tests also need a way to assert redirects and messages, often by mocking header() and exit()
// or by using PHPUnit features for testing HTTP output.
?>
*/
