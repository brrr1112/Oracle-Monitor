<?php
// server/encryption_helper.php

// Attempt to load the configuration file.
// In a real application, you'd have a more robust way to ensure config is loaded,
// or a clear error if it's missing.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Fallback or error if config.php is not found.
    // This is critical for encryption to work.
    // For this exercise, we'll define a default key IF NOT DEFINED,
    // but in production, this should halt with an error.
    if (!defined('ENCRYPTION_KEY')) {
        // THIS IS INSECURE FOR PRODUCTION.
        // It's a fallback for the development environment where config.php might not be set up from placeholder.
        define('ENCRYPTION_KEY', 'Default_Dev_32Byte_EncryptionKey'); // Ensure this is 32 bytes for AES-256
        error_log("Warning: ENCRYPTION_KEY not defined in config.php. Using a default, insecure key for development purposes ONLY.");
    }
}

if (!defined('ENCRYPTION_KEY') || strlen(ENCRYPTION_KEY) !== 32) {
     // If still not defined or wrong length after fallback (which shouldn't happen with the above dev default)
     // or if the user created config.php but with a wrong key length.
    error_log("CRITICAL: ENCRYPTION_KEY is not defined or is not 32 bytes long. Encryption functions will fail or be insecure.");
    // In a real app, you might throw an exception or die here.
}


define('ENCRYPTION_CIPHER', 'aes-256-gcm');

/**
 * Encrypts a plaintext string using AES-256-GCM.
 * The ENCRYPTION_KEY constant must be defined and be 32 bytes long.
 * The ENCRYPTION_CIPHER constant defines the cipher method (e.g., 'aes-256-gcm').
 *
 * @param string $plaintext The plaintext string to encrypt.
 * @return string|false Returns a base64 encoded string formatted as "iv.tag.ciphertext" on success,
 *                      or false on failure (e.g., key misconfiguration, openssl error).
 *                      Errors are logged.
 */
function encrypt_data(string $plaintext): string|false {
    if (!defined('ENCRYPTION_KEY') || strlen(ENCRYPTION_KEY) !== 32) {
        error_log("Encryption failed: ENCRYPTION_KEY is not properly configured (must be defined and 32 bytes long).");
        return false;
    }
    try {
        $iv_length = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
        if ($iv_length === false) {
            error_log("Encryption failed: Could not get IV length for cipher " . ENCRYPTION_CIPHER);
            return false;
        }
        $iv = openssl_random_pseudo_bytes($iv_length);
        $tag = ""; // GCM tag will be appended by openssl_encrypt

        $ciphertext = openssl_encrypt(
            $plaintext,
            ENCRYPTION_CIPHER,
            ENCRYPTION_KEY,
            OPENSSL_RAW_DATA, // Output raw binary
            $iv,
            $tag, // Pass by reference, OpenSSL fills it for GCM
            '',   // AAD - Additional Associated Data (optional)
            16    // Tag length for GCM, 16 bytes (128 bits) is common
        );

        if ($ciphertext === false) {
            error_log("Encryption failed: openssl_encrypt returned false. OpenSSL errors: " . openssl_error_string());
            return false;
        }

        // Prepend IV and tag to ciphertext, then base64 encode.
        // Storing IV and tag with ciphertext is standard practice.
        return base64_encode($iv . $tag . $ciphertext);

    } catch (Exception $e) {
        error_log("Encryption exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Decrypts a base64 encoded string that was encrypted with `encrypt_data()`.
 * It expects the input format "iv.tag.ciphertext" (base64 encoded).
 * The ENCRYPTION_KEY constant must be defined and be 32 bytes long.
 * The ENCRYPTION_CIPHER constant defines the cipher method.
 *
 * @param string $base64_ciphertext The base64 encoded string containing IV, tag, and ciphertext.
 * @return string|false The original plaintext string on successful decryption and authentication,
 *                      or false on failure (e.g., key misconfiguration, invalid format, decryption error, tag mismatch).
 *                      Errors are logged.
 */
function decrypt_data(string $base64_ciphertext): string|false {
    if (!defined('ENCRYPTION_KEY') || strlen(ENCRYPTION_KEY) !== 32) {
        error_log("Decryption failed: ENCRYPTION_KEY is not properly configured (must be defined and 32 bytes long).");
        return false;
    }
    try {
        $decoded_ciphertext = base64_decode($base64_ciphertext);
        if ($decoded_ciphertext === false) {
            error_log("Decryption failed: base64_decode returned false.");
            return false;
        }

        $iv_length = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
        if ($iv_length === false) {
            error_log("Decryption failed: Could not get IV length for cipher " . ENCRYPTION_CIPHER);
            return false;
        }
        $tag_length = 16; // GCM tag length used during encryption

        if (strlen($decoded_ciphertext) < ($iv_length + $tag_length)) {
            error_log("Decryption failed: Ciphertext is too short to contain IV and tag.");
            return false;
        }

        $iv = substr($decoded_ciphertext, 0, $iv_length);
        $tag = substr($decoded_ciphertext, $iv_length, $tag_length);
        $ciphertext_actual = substr($decoded_ciphertext, $iv_length + $tag_length);

        $plaintext = openssl_decrypt(
            $ciphertext_actual,
            ENCRYPTION_CIPHER,
            ENCRYPTION_KEY,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            error_log("Decryption failed: openssl_decrypt returned false. Possible tampering, incorrect key, or OpenSSL error. OpenSSL errors: " . openssl_error_string());
            return false;
        }
        return $plaintext;

    } catch (Exception $e) {
        error_log("Decryption exception: " . $e->getMessage());
        return false;
    }
}
?>
