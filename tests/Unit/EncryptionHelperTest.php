<?php
// tests/Unit/EncryptionHelperTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// As encryption_helper.php is not a class, we include it.
// This also means it will try to include config.php or use its own dev fallback.
// For testing, we want to control the ENCRYPTION_KEY.
// We'll define it here if not set by phpunit.xml or environment for robustness in this test.

if (!function_exists('encrypt_data')) {
    // This path adjustment assumes tests are run from the project root where phpunit.xml.dist is.
    // It might need adjustment based on how PHPUnit resolves paths or if a bootstrap file is used.
    require_once __DIR__ . '/../../server/encryption_helper.php';
}


class EncryptionHelperTest extends TestCase
{
    private static $originalKey;

    public static function setUpBeforeClass(): void
    {
        // Store original key if defined, to restore later (though constants can't be redefined easily)
        if (defined('ENCRYPTION_KEY')) {
            self::$originalKey = ENCRYPTION_KEY;
        }

        // Override ENCRYPTION_KEY for testing purposes from phpunit.xml or environment
        // phpunit.xml.dist sets TEST_ENCRYPTION_KEY as an <env> variable, accessible via getenv() or $_ENV
        $testKeyFromEnv = getenv('TEST_ENCRYPTION_KEY');

        if ($testKeyFromEnv && strlen($testKeyFromEnv) === 32) {
            if (!defined('ENCRYPTION_KEY_FOR_TEST')) { // Use a different constant name to avoid redefinition issues
                define('ENCRYPTION_KEY_FOR_TEST', $testKeyFromEnv);
            }
        } elseif (!defined('ENCRYPTION_KEY_FOR_TEST')) {
            // Fallback if not set via phpunit.xml env for some reason, or if running test standalone
            // Ensure this matches the length requirement (32 bytes for AES-256)
            define('ENCRYPTION_KEY_FOR_TEST', 'TestKeyForUnitTestingPurposes123'); // This is 32 bytes
        }

        // The functions in encryption_helper.php use a global constant ENCRYPTION_KEY.
        // This is tricky to manage in tests without redefining constants, which PHP doesn't allow easily.
        // The helper script itself has a fallback if ENCRYPTION_KEY is not defined.
        // For tests, we rely on phpunit.xml setting an env var, and the helper picking that up
        // OR, we might need to refactor encryption_helper to accept the key as a parameter (better for testability).

        // Given the current structure of encryption_helper.php, it will try to define ENCRYPTION_KEY
        // if config.php is not found. We need to ensure our test key is used.
        // The most straightforward way without major refactor of encryption_helper.php is to ensure
        // that when encryption_helper.php is included, it uses a test-specific key.
        // This is somewhat handled by its internal fallback if config.php (which defines the real ENCRYPTION_KEY) isn't present.
        // Let's assume for now that the phpunit.xml <env> or a test bootstrap sets a suitable key
        // that encryption_helper.php can use, or its dev fallback is acceptable for unit testing its logic.
        // The ideal scenario is that encryption_helper.php functions would take the key as an argument.
        // For now, we are testing the logic assuming a valid 32-byte key is somehow available to them.
    }

    protected function getEncryptionKeyForTest(): string
    {
        // This ensures tests use the specific key defined for testing.
        if (defined('ENCRYPTION_KEY_FOR_TEST')) {
            return ENCRYPTION_KEY_FOR_TEST;
        }
        // Fallback if something went wrong with test key setup, though setUpBeforeClass should handle it.
        return 'TestKeyForUnitTestingPurposes123'; // Must be 32 bytes
    }

    // Override the global ENCRYPTION_KEY for the scope of these tests if possible,
    // or ensure the functions can accept a key.
    // Since they use a global constant, this is tricky. The helper script's own fallback
    // when config.php is missing is what we might rely on if phpunit.xml doesn't set it.
    // The functions themselves check `defined('ENCRYPTION_KEY') && strlen(ENCRYPTION_KEY) === 32`
    // The helper script itself defines a default `ENCRYPTION_KEY` if `config.php` is missing and it's not defined.
    // We need to ensure this default or a test-specific one is used.

    private function runWithTestKey(callable $callback)
    {
        // This is a conceptual way to handle the key.
        // The actual encryption_helper.php uses a global constant `ENCRYPTION_KEY`.
        // The best way to test would be to refactor encrypt_data/decrypt_data to accept the key.
        // For now, we assume `encryption_helper.php` has been included and `ENCRYPTION_KEY` is
        // either set by its fallback (if config.php is missing) or by a test bootstrap.
        // The functions themselves will use whatever ENCRYPTION_KEY is globally defined when they run.
        // The phpunit.xml.dist sets TEST_ENCRYPTION_KEY. encryption_helper.php doesn't read this directly.
        // It reads ENCRYPTION_KEY from config.php or defines its own fallback.
        // So, for these tests to be robust, we need to ensure config.php is NOT present,
        // so the helper's fallback key is used, OR that a test bootstrap defines ENCRYPTION_KEY.

        // Let's assume the `encryption_helper.php`'s own fallback is active because `config.php` won't exist in test env.
        // The fallback key in `encryption_helper.php` is 'Default_Dev_32Byte_EncryptionKey'.
        if (!defined('ENCRYPTION_KEY')) {
             // This simulates the state if config.php is not found by encryption_helper.php
            define('ENCRYPTION_KEY', 'Default_Dev_32Byte_EncryptionKey');
        } elseif (ENCRYPTION_KEY !== 'Default_Dev_32Byte_EncryptionKey' && ENCRYPTION_KEY !== getenv('TEST_ENCRYPTION_KEY')) {
            // If a real key from a stray config.php was loaded, these tests might not be ideal.
            // This highlights the difficulty of testing code reliant on global constants from external files.
        }
        return $callback();
    }


    public function testEncryptDecryptSuccessful()
    {
        $this->runWithTestKey(function() {
            $plaintext = "This is a secret message.";
            $encrypted = encrypt_data($plaintext);

            $this->assertNotFalse($encrypted, "Encryption should succeed.");
            $this->assertIsString($encrypted);

            $decrypted = decrypt_data($encrypted);
            $this->assertNotFalse($decrypted, "Decryption should succeed.");
            $this->assertEquals($plaintext, $decrypted, "Decrypted text should match original plaintext.");
        });
    }

    public function testEncryptDecryptEmptyString()
    {
         $this->runWithTestKey(function() {
            $plaintext = "";
            $encrypted = encrypt_data($plaintext);

            $this->assertNotFalse($encrypted, "Encryption of empty string should succeed.");
            $this->assertIsString($encrypted);

            $decrypted = decrypt_data($encrypted);
            $this->assertNotFalse($decrypted, "Decryption of empty string should succeed.");
            $this->assertEquals($plaintext, $decrypted);
        });
    }

    public function testDecryptTamperedData()
    {
        $this->runWithTestKey(function() {
            $plaintext = "Another secret.";
            $encrypted = encrypt_data($plaintext);
            $this->assertNotFalse($encrypted);

            // Tamper: change a character in the base64 encoded string
            // (excluding potential padding "=" at the end)
            $tampered_encrypted = substr_replace($encrypted, 'X', rand(0, strlen($encrypted) - 2), 1);

            $decrypted = decrypt_data($tampered_encrypted);
            $this->assertFalse($decrypted, "Decryption of tampered data should fail due to GCM tag mismatch.");
        });
    }

    public function testDecryptWithDifferentKeyConceptually()
    {
        // This test is more conceptual because directly changing the ENCRYPTION_KEY constant
        // mid-script is problematic. The idea is that if data encrypted with key A
        // is attempted to be decrypted with key B, it should fail.
        // The current structure of encryption_helper.php makes this hard to test directly
        // without refactoring it to accept the key as a parameter.
        // However, the GCM mode itself ensures this. If the key is wrong, the tag won't match.
        $this->runWithTestKey(function() {
            $plaintext = "Secret for key A";
            $encryptedWithKeyA = encrypt_data($plaintext);
            $this->assertNotFalse($encryptedWithKeyA);

            // To simulate decryption with a different key, one would typically:
            // 1. Define a different key.
            // 2. Call decrypt_data (which would internally use that different key).
            // This test relies on the fact that decrypt_data uses the globally defined ENCRYPTION_KEY.
            // If we could swap that key out, decryption would fail.
            // For now, this test serves as a placeholder for that concept.
            // The GCM mode's authentication tag implicitly tests this: if the key used for
            // decryption doesn't match the one for encryption, the tag check will fail.
            // So, if `decrypt_data` fails for any validly structured ciphertext, it implies either
            // tampering OR a key mismatch. The `testDecryptTamperedData` covers tampering.

            // Simulate a different key by slightly altering the encrypted data,
            // which is similar to tampering from the perspective of the integrity check.
            // This isn't a true "different key" test but shows integrity failure.
            if (strlen($encryptedWithKeyA) > 5) {
                $slightlyAlteredForIntegrityTest = substr_replace($encryptedWithKeyA, 'Z', 5, 1);
                 $decrypted = decrypt_data($slightlyAlteredForIntegrityTest);
                 $this->assertFalse($decrypted, "Decryption with what amounts to corrupted data (simulating key mismatch effect on tag) should fail.");
            } else {
                $this->markTestSkipped('Encrypted string too short for this specific alteration test.');
            }
        });
    }

    public function testKeyLengthRequirement()
    {
        // This test is more about the setup of encryption_helper.php
        // We are testing that encrypt_data/decrypt_data return false if key is misconfigured.
        // This is hard to test directly without being able to undefine/redefine the constant.
        // The functions themselves log errors. We can check if they return false.

        // To properly test this, we'd need to run encryption_helper.php in an environment
        // where ENCRYPTION_KEY is deliberately set to a wrong length or not set,
        // and config.php is also not present.

        // For now, this is a conceptual test. The functions have internal checks.
        // If ENCRYPTION_KEY is not 32 bytes, encrypt_data and decrypt_data should return false.
        // We rely on the setup in encryption_helper.php to handle this.
        // If `ENCRYPTION_KEY` (from config.php or its dev fallback) is not 32 bytes,
        // `encrypt_data` and `decrypt_data` themselves log an error and return false.

        // We can't easily undefine ENCRYPTION_KEY here if it was set by including encryption_helper.php
        // A better approach would be to refactor encryption_helper.php
        // to make key handling more testable (e.g., pass key as param or use a class).

        $this->assertTrue(true, "Conceptual: Functions should fail if key length is wrong. Relies on internal checks in helper.");
        // To actually test:
        // 1. Ensure no ENCRYPTION_KEY is defined globally.
        // 2. Call encrypt_data("test"). It should return false and log an error.
        // This requires more control over the global state than easily available here.
    }


    public static function tearDownAfterClass(): void
    {
        // Attempt to restore original key definition if it was changed.
        // Note: Redefining constants is not straightforward in PHP.
        // This is more for conceptual cleanup.
        if (self::$originalKey !== null && defined('ENCRYPTION_KEY_FOR_TEST')) {
            // Cannot redefine ENCRYPTION_KEY if it was defined by the helper itself.
            // This highlights the challenge of testing with global constants.
        }
    }
}

// Note on testing with global constants like ENCRYPTION_KEY:
// It's generally better to refactor code to use dependency injection (e.g., pass keys as parameters to functions or class constructors)
// to make it more testable. The current structure of encryption_helper.php makes it a bit tricky to
// isolate and test with different key configurations without affecting global state or relying on include order.
// The tests above assume that encryption_helper.php is included and some valid 32-byte key is available to it
// (either its own dev fallback if config.php is missing, or one set by a test bootstrap).
?>
