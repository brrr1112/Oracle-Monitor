<?php
// tests/Integration/ConnectionsHandlerTest.php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

// It's assumed encryption_helper.php and app_db_connection.php are available
// and app_db_connection.php can use a test DB via $GLOBALS['TEST_PDO_OVERRIDE']

class ConnectionsHandlerTest extends TestCase
{
    private $pdo;
    private $testUserId = 1;
    private $testUsername = 'testuserhandler';

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Load schema (users and database_connections tables)
        $schemaSql = "
        CREATE TABLE users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL, created_at TEXT NOT NULL
        );
        CREATE TABLE database_connections (
            conn_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL,
            profile_name TEXT NOT NULL, db_host TEXT NOT NULL, db_port TEXT NOT NULL,
            db_service_name TEXT NOT NULL, db_user TEXT NOT NULL,
            encrypted_db_password TEXT NOT NULL, created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );";
        $this->pdo->exec($schemaSql);

        // Insert a test application user
        $this->pdo->exec("INSERT INTO users (user_id, username, email, password_hash, created_at) VALUES ({$this->testUserId}, '{$this->testUsername}', 'handler@example.com', 'somehash', datetime('now'))");

        $GLOBALS['TEST_PDO_OVERRIDE'] = $this->pdo;

        // Simulate logged-in user
        $_SESSION['app_user_id'] = $this->testUserId;
        $_SESSION['app_username'] = $this->testUsername;

        $_POST = [];
        $_GET = [];

        // Ensure encryption helper is loaded for the handler
        if (!function_exists('encrypt_data')) {
            require_once __DIR__ . '/../../server/encryption_helper.php';
        }
         // Define ENCRYPTION_KEY if not already defined by a bootstrap or previous include
        // This is crucial for encryption_helper to work. Use the test key.
        if (!defined('ENCRYPTION_KEY')) {
            $testKeyFromEnv = getenv('TEST_ENCRYPTION_KEY') ?: 'TestDev_32Byte_EncryptionKey123';
            define('ENCRYPTION_KEY', $testKeyFromEnv);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TEST_PDO_OVERRIDE']);
        unset($_SESSION['app_user_id']);
        unset($_SESSION['app_username']);
    }

    private function captureHandlerOutput(string $action, string $method = 'GET', array $params = []): array
    {
        if (strtoupper($method) === 'POST') {
            $_POST = $params;
            $_GET['action'] = $action; // Action can also be in POST, but handler checks both
        } else { // GET
            $_GET = $params;
            $_GET['action'] = $action;
        }

        ob_start();
        require __DIR__ . '/../../server/connections_handler.php';
        $output = ob_get_clean();
        return json_decode($output, true);
    }

    public function testAddConnectionProfileSuccessful()
    {
        $params = [
            'profile_name' => 'Test Prod DB',
            'db_host' => 'prod-db.example.com',
            'db_port' => '1521',
            'db_service_name' => 'PRODSRVC',
            'db_user' => 'monitor',
            'db_password' => 'secretpass',
        ];
        $response = $this->captureHandlerOutput('add', 'POST', $params);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertStringContainsString('Connection profile added successfully', $response['message']);

        // Verify in DB
        $stmt = $this->pdo->prepare("SELECT * FROM database_connections WHERE user_id = ? AND profile_name = ?");
        $stmt->execute([$this->testUserId, 'Test Prod DB']);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($profile);
        $this->assertEquals('prod-db.example.com', $profile['db_host']);
        $this->assertNotEmpty($profile['encrypted_db_password']);

        // Test decryption if possible (requires the key to be consistent)
        if (function_exists('decrypt_data')) {
             $decryptedPass = decrypt_data($profile['encrypted_db_password']);
             $this->assertEquals('secretpass', $decryptedPass);
        }
    }

    public function testListConnectionProfiles()
    {
        // Add a profile first
        $this->testAddConnectionProfileSuccessful(); // Re-uses the add logic and its assertions

        // Now list
        $response = $this->captureHandlerOutput('list', 'GET');

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('connections', $response);
        $this->assertCount(1, $response['connections']);
        $this->assertEquals('Test Prod DB', $response['connections'][0]['profile_name']);
        $this->assertEquals('prod-db.example.com', $response['connections'][0]['db_host']);
        $this->assertArrayNotHasKey('encrypted_db_password', $response['connections'][0], "Encrypted password should not be listed.");
    }

    public function testAddConnectionProfileFailsIfRequiredFieldsMissing()
    {
        $params = [ // Missing db_host, db_service_name, db_user, db_password
            'profile_name' => 'Incomplete Profile',
            'db_port' => '1521',
        ];
        $response = $this->captureHandlerOutput('add', 'POST', $params);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('All fields except port are required', $response['message']);
    }

    public function testDeleteConnectionProfile()
    {
        // 1. Add a profile
        $addParams = [
            'profile_name' => 'ToDeleteDB', 'db_host' => 'delhost', 'db_port' => '1521',
            'db_service_name' => 'DELSRVC', 'db_user' => 'deluser', 'db_password' => 'delpass'
        ];
        $addResponse = $this->captureHandlerOutput('add', 'POST', $addParams);
        $this->assertEquals('success', $addResponse['status']);

        // Get its ID
        $stmt = $this->pdo->prepare("SELECT conn_id FROM database_connections WHERE profile_name = 'ToDeleteDB' AND user_id = ?");
        $stmt->execute([$this->testUserId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($profile, "Profile 'ToDeleteDB' should exist after adding.");
        $connIdToDelete = $profile['conn_id'];

        // 2. Delete the profile
        $deleteParams = ['conn_id' => $connIdToDelete];
        $deleteResponse = $this->captureHandlerOutput('delete', 'POST', $deleteParams);

        $this->assertArrayHasKey('status', $deleteResponse);
        $this->assertEquals('success', $deleteResponse['status'], "Deletion should succeed. Message: " . ($deleteResponse['message'] ?? 'N/A'));

        // 3. Verify it's gone from DB
        $stmt = $this->pdo->prepare("SELECT * FROM database_connections WHERE conn_id = ?");
        $stmt->execute([$connIdToDelete]);
        $this->assertFalse($stmt->fetch(), "Profile should be deleted from DB.");

        // 4. Try to delete again (should fail or report 0 rows affected)
        $deleteAgainResponse = $this->captureHandlerOutput('delete', 'POST', $deleteParams);
        $this->assertEquals('error', $deleteAgainResponse['status']); // Expecting error as it's not found
        $this->assertStringContainsString('not found or access denied', $deleteAgainResponse['message']);
    }

     public function testSetActiveConnection()
    {
        // 1. Add a profile
        $profileName = 'ActiveProfileTest';
        $addParams = [
            'profile_name' => $profileName, 'db_host' => 'activehost', 'db_port' => '1521',
            'db_service_name' => 'ACTIVESRVC', 'db_user' => 'activeuser', 'db_password' => 'activepass'
        ];
        $this->captureHandlerOutput('add', 'POST', $addParams);
        $stmt = $this->pdo->prepare("SELECT conn_id FROM database_connections WHERE profile_name = ?");
        $stmt->execute([$profileName]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        $connId = $profile['conn_id'];

        // 2. Set it as active
        $response = $this->captureHandlerOutput('set_active_connection', 'POST', ['conn_id' => $connId]);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals($profileName, $response['selected_profile_name']);
        $this->assertEquals($connId, $_SESSION['selected_oracle_conn_id']);
        $this->assertEquals($profileName, $_SESSION['selected_oracle_profile_name']);
    }

    public function testSetActiveConnectionFailsForInvalidId()
    {
        $response = $this->captureHandlerOutput('set_active_connection', 'POST', ['conn_id' => 99999]); // Non-existent
        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('not found or access denied', $response['message']);
        $this->assertArrayNotHasKey('selected_oracle_conn_id', $_SESSION);
    }

}

// Note on TEST_ENCRYPTION_KEY:
// The encryption_helper.php has a development fallback for ENCRYPTION_KEY if config.php is missing.
// For these tests to be fully robust regarding encryption/decryption, ensure that
// the key used by encryption_helper.php during the test run is known and consistent.
// Setting the TEST_ENCRYPTION_KEY via phpunit.xml and having encryption_helper.php
// prioritize an environment variable for the key would be a good approach.
// The current test setup defines ENCRYPTION_KEY if not set, which might interact with
// the helper's own fallback. The key is that *a* consistent 32-byte key must be used.
?>
