# Oracle Monitor (Multi-Tenant)

The Oracle Database Monitoring Tool is a utility designed to help database administrators (DBAs) and system administrators monitor and manage various aspects of an Oracle database. This tool supports multiple users, each monitoring their own set of Oracle databases, and provides insights into database health and performance.

## Features

The tool offers a range of monitoring capabilities, including:

*   **User Accounts:** Secure registration and login for multiple users.
*   **Connection Management:** Users can securely store and manage connection profiles for multiple Oracle databases. Oracle passwords are stored encrypted.
*   **SGA Monitoring:** Track System Global Area (SGA) usage with graphical representation and alerts for high-consumption SQL.
*   **Tablespace Utilization:** Monitor space usage, daily growth (average), and estimated remaining days for tablespaces, with charts.
*   **RMAN Backup Monitoring:**
    *   View RMAN backup job history.
    *   Initiate RMAN backups (Full, Tablespace) through a secured mechanism using PL/SQL procedures.
*   **User SQL Monitoring:** View SQL statements executed by database users.
*   **Logs Monitor:** Check status of redo log groups, members, log mode, and average log switch time.
*   **Performance Dashboard:**
    *   Key Performance Ratios (Buffer Cache Hit, Library Cache Hit, Dictionary Cache Hit, Latch Hit) with gauge charts.
    *   Call Rates (User Calls/sec, Commits/sec, Rollbacks/sec).
    *   Top N SQL Summary with bar chart (configurable metric and N).
    *   Top Sessions by Resource with bar chart and table (configurable resource and N).
*   **Active Session Monitoring:** View currently active sessions in the selected Oracle database.
*   **System-Wide Wait Event Monitoring:** Display top N non-idle system wait events by time waited and by number of waits, with charts and a table.

*(The original deepwiki link may refer to an older, single-user version of this concept.)*

## Setup and Installation

This application is a PHP-based web tool that connects to Oracle databases. Here’s how to set it up:

**Prerequisites:**

1.  **Web Server:** A web server like Apache or Nginx with PHP support.
    *   PHP version 7.4 or higher recommended.
    *   Required PHP extensions:
        *   `pdo_sqlite` (for the application's own user database).
        *   `oci8` (for connecting to Oracle databases).
        *   `openssl` (for encrypting/decrypting Oracle passwords).
2.  **Oracle Instant Client (or full client):** Must be installed on the server where the PHP application is hosted. Ensure its `PATH` and `LD_LIBRARY_PATH` (on Linux) or system `PATH` (on Windows) are configured correctly so PHP can find the OCI libraries.
3.  **Writable `app_data` directory:** The application needs to create an SQLite database file. The `app_data/` directory (in the project root) must be writable by the web server user.
4.  **Git:** To clone the repository.

**Installation Steps:**

1.  **Clone the Repository:**
    ```bash
    git clone <repository_url>
    cd <repository_directory>
    ```

2.  **Configure Web Server:**
    *   Point your web server's document root to the `Frontend/` directory of the cloned repository.
    *   Ensure appropriate permissions are set for the web server user to read files.
    *   For Apache, ensure `AllowOverride All` is set for the directory if using `.htaccess` for security (e.g., to protect `app_data`).

3.  **Initialize Application Database:**
    *   The application uses an SQLite database to store user accounts and their encrypted Oracle database connection profiles.
    *   Run the initialization script from your server's command line:
        ```bash
        php server/initialize_app_db.php
        ```
    *   This will create `app_data/monitoring_tool.sqlite` and set up the necessary tables.
    *   **Security:** Ensure the `app_data/` directory and the `monitoring_tool.sqlite` file are NOT directly accessible via the web. The `initialize_app_db.php` script attempts to create an `.htaccess` file in `app_data/` for Apache servers to deny direct access. Verify this or implement equivalent protection for your web server.

4.  **Configure Encryption Key:**
    *   The application encrypts Oracle database passwords stored in its SQLite database. A secure encryption key is required.
    *   Rename `server/config.php.placeholder` to `server/config.php`.
    *   Edit `server/config.php` and set a strong, 32-byte `ENCRYPTION_KEY`. You can generate one using:
        ```php
        // Run this PHP code snippet once to generate a key:
        // echo base64_encode(random_bytes(32));
        ```
        Place the non-base64 encoded 32-byte string in the `define('ENCRYPTION_KEY', 'your-32-byte-secret-encryption-key');` line.
        Alternatively, use the base64 encoded key with `base64_decode()`:
        `define('ENCRYPTION_KEY', base64_decode('YOUR_BASE64_ENCODED_32_BYTE_KEY_HERE'));`
    *   **CRITICAL:** Protect `server/config.php`. Do NOT commit it to version control if it contains the actual key. Ensure it's readable by the web server user but not publicly accessible. In a production cloud environment (like AWS), this key should be managed via a secrets manager (e.g., AWS Secrets Manager) and injected as an environment variable.

5.  **Prepare Target Oracle Databases:**
    *   For each Oracle database you intend to monitor with this tool, you need to run the SQL scripts provided in `Database/Script.sql`.
    *   This script creates necessary PL/SQL functions, procedures, views, and one table (`job_SGA_Table`) that the monitoring tool uses to query Oracle performance data.
    *   Connect to your target Oracle database as a user with privileges to create these objects (e.g., a DBA account or a dedicated schema owner with appropriate grants).
    *   Execute all statements in `Database/Script.sql`.
    *   **Note on Privileges:** The Oracle user whose credentials will be used by the monitoring tool to connect to this target database needs `SELECT` privileges on the `V$` views, `DBA_` views, and other objects queried by the functions/views in `Script.sql`. It will also need `EXECUTE` privileges on the created procedures (like RMAN backup procedures). For simplicity, the tool often assumes SYSDBA-like privileges for the monitoring connection, but a dedicated, less-privileged monitoring user is highly recommended for production use. You would need to grant specific privileges to such a user.

6.  **Access the Application:**
    *   Open your web browser and navigate to the URL where you've hosted the `Frontend/` directory. You should see the application login page.

## How to Use

1.  **Register an Application Account:**
    *   Navigate to the application's main page (e.g., `http://yourserver/path/to/Frontend/`).
    *   Click on "Create an Account" (or navigate to `register.php`).
    *   Fill in your desired username, email, and a strong password for the *monitoring tool itself*. This is NOT your Oracle database password.
    *   Click "Register".

2.  **Login to the Application:**
    *   Go to the main page (`index.html` or `index.php`).
    *   Enter the application username and password you just registered.
    *   Click "Login". You will be redirected to the Menu page.

3.  **Manage Oracle Database Connection Profiles:**
    *   On the Menu page, you'll see a link "Manage Connections" (usually in the bar at the top). Click it, or navigate directly to `manage_connections.php`.
    *   This page allows you to add connection profiles for each Oracle database you want to monitor.
    *   **Add a Profile:**
        *   **Profile Name:** A friendly name for your reference (e.g., "Production CRM DB", "Dev Test Instance").
        *   **Hostname or IP Address:** The server where your Oracle database is running.
        *   **Port:** The TNS listener port (usually 1521).
        *   **Service Name (or SID):** The service name or SID of your Oracle database.
        *   **Oracle Username:** The username for connecting to *your Oracle database*. This user needs the privileges mentioned in the "Prepare Target Oracle Databases" setup step.
        *   **Oracle Password:** The password for the Oracle username. This will be encrypted before being stored by the application.
        *   Click "Add Connection".
    *   You can add multiple profiles. They will be listed on this page. You can also delete profiles from here.

4.  **Select Active Oracle Database for Monitoring:**
    *   Go to the Menu page (`menu.php`).
    *   At the top, you'll see a dropdown labeled "Active Oracle DB:".
    *   Select one of your saved connection profiles from this dropdown.
    *   The "Currently Monitoring:" status will update. This selection is stored in your session and will be used by all monitoring pages.

5.  **Use Monitoring Features:**
    *   From the Menu page, click on any of the available monitoring sections (e.g., "SGA Monitor", "Tablespace", "Performance Dashboard", "RMAN", etc.).
    *   The data displayed will be fetched from the Oracle database profile you currently have selected as active.
    *   If no profile is selected, or if there's an issue connecting to the selected Oracle database, the monitoring pages will display an error message prompting you to select a valid profile.

6.  **Logout:**
    *   When finished, click the "Logout" link (usually in the header on the Menu page or other pages) to securely end your application session.

## Running Tests

This project includes PHPUnit tests for backend functionality.

**Prerequisites for Testing:**

1.  **Composer:** Ensure Composer is installed globally or available in your PHP environment.
2.  **PHPUnit:** Install project development dependencies, including PHPUnit, by running:
    ```bash
    composer install
    ```
    This command should be run in the root directory of the project (where `composer.json` is located).

**Test Configuration:**

1.  **Encryption Key for Tests:**
    *   The tests, especially those involving encryption or database interaction that might trigger encryption (like `ConnectionsHandlerTest`), require an encryption key.
    *   A placeholder environment variable `TEST_ENCRYPTION_KEY` is defined in `phpunit.xml.dist`.
    *   **It is highly recommended to create a `phpunit.xml` file (a copy of `phpunit.xml.dist`) and set a specific, non-production 32-byte key for `TEST_ENCRYPTION_KEY` there.** `phpunit.xml` is typically gitignored.
        ```xml
        <!-- phpunit.xml -->
        <php>
            <env name="TEST_ENCRYPTION_KEY" value="YourDedicated32ByteTestKey12345"/>
        </php>
        ```
    *   Alternatively, you can set this as an actual environment variable in your testing system.
    *   The `encryption_helper.php` has a development fallback key, but tests are more reliable if a specific test key is configured.

2.  **Test Database (for Integration Tests):**
    *   Integration tests (like `RegistrationHandlerTest`, `LoginHandlerTest`, `ConnectionsHandlerTest`) are designed to run against an **in-memory SQLite database** to avoid affecting your development or production application database.
    *   For these tests to correctly use the in-memory database, the `server/app_db_connection.php` script needs to be aware of the testing environment. The tests attempt to use a global variable (`$GLOBALS['TEST_PDO_OVERRIDE']`) to inject the test PDO instance. This requires a modification to `server/app_db_connection.php` similar to this for the tests to fully work as intended with the database:
        ```php
        // server/app_db_connection.php (modified for testability)
        function getAppDbConnection() {
            if (isset($GLOBALS['TEST_PDO_OVERRIDE']) && $GLOBALS['TEST_PDO_OVERRIDE'] instanceof \PDO) {
                return $GLOBALS['TEST_PDO_OVERRIDE'];
            }
            // ... original SQLite connection logic ...
        }
        ```
    *   Without this modification, the integration tests might attempt to use the actual `app_data/monitoring_tool.sqlite` file, which could lead to test failures or data contamination if not handled carefully. The current tests are written assuming they can control the PDO instance.

**Executing Tests:**

*   **From the project root directory, run:**
    ```bash
    composer test
    ```
    (This uses the script defined in `composer.json`)
*   **Or directly using the PHPUnit executable (if in your PATH or from `vendor/bin`):**
    ```bash
    ./vendor/bin/phpunit
    ```
    Or:
    ```bash
    phpunit
    ```

**Interpreting Test Results:**

*   PHPUnit will output the results of the tests, showing successes, failures, errors, and skipped tests.
*   **Skipped Tests:** Some integration tests (for `RegistrationHandlerTest` and `LoginHandlerTest`) are currently marked as skipped (`$this->markTestSkipped(...)`). This is because fully testing procedural scripts that involve `header()` redirects and `exit()` calls directly within PHPUnit requires more advanced setup (like PHPUnit's `@runInSeparateProcess` annotation per test, or significant refactoring of those handler scripts into more testable units). The comments in those test files provide more details. The `ConnectionsHandlerTest` is more complete as it tests JSON API-like behavior.

**Code Coverage (Optional):**

*   If you have Xdebug or PCOV installed and configured for PHP, you can generate a code coverage report. The `phpunit.xml.dist` is configured to include files from the `server/` directory.
*   To generate an HTML coverage report, you might run:
    ```bash
    ./vendor/bin/phpunit --coverage-html coverage-report
    ```
    The report will be generated in the `coverage-report/` directory.

## Screenshots

*(Existing screenshots might be from an older version. New screenshots reflecting the multi-user interface and new features would be beneficial here.)*

![image](https://github.com/user-attachments/assets/44d95925-1d2d-4f70-beb1-184ddca9df2c)

![image](https://github.com/user-attachments/assets/d515210b-15ac-41c9-a5cf-19e66f73a983)
