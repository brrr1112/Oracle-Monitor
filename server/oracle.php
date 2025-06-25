<?php
session_start();
function getsessionstatus(){
    switch (session_status()) {
        case 0:
            echo "session desactivada\n";
            break;
        case 1:
            echo "ninguna sesion\n";
            break;
        case 2:
            echo "sesion activa\n";
            break;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //getsessionstatus();
    session_reset();
    $_SESSION['username'] = $username = isset($_REQUEST['user']) ? $_REQUEST['user'] : '';
    $_SESSION['password'] = $password = isset($_REQUEST['pass']) ? $_REQUEST['pass'] : '';

    $host = isset($_REQUEST['server']) ? trim($_REQUEST['server']) : '';
    $port = isset($_REQUEST['port']) ? trim($_REQUEST['port']) : '1521'; // Default Oracle port
    $db_name = isset($_REQUEST['db']) ? trim($_REQUEST['db']) : '';

    if (empty($port)) {
        $port = '1521'; // Ensure default port if user submits an empty string
    }

    // Construct the connection string using Oracle Easy Connect syntax
    // Example: //hostname:port/service_name or //hostname/service_name if port is 1521
    // Or for SID: //hostname:port:SID (less common for Easy Connect, but oci_connect might handle it)
    // We will prefer service_name. If user provides SID, they might need to use full TNS entry or configure TNS_ADMIN.
    // For simplicity, we'll construct as //host:port/db_name
    // A more robust solution might involve asking user if db_name is a Service Name or SID.
    if (!empty($host) && !empty($db_name)) {
        $connection_string = "//{$host}:{$port}/{$db_name}";
    } else {
        // Handle error: host or db_name is missing
        // For now, redirect back or show error. Let's try to display an error.
        // This part would ideally be handled better with a proper error display on the login page.
        header('Location: ../Frontend/index.html?error=missing_connection_details');
        exit;
    }

    $_SESSION['connection_string'] = $connection_string;

    // Attempt connection
    // Note: OCI_SYSDBA is a powerful privilege. Ensure it's necessary.
    // For general monitoring, a less privileged user is often preferred.
    $conn = oci_connect($username, $password, $connection_string, '', OCI_SYSDBA);

    if ($conn) {
        //echo "connected";
        header('Location: ../Frontend/menu.html');
        exit;
    } else {
        $e = oci_error();
        $error_message = $e ? htmlentities($e['message']) : "Unknown connection error.";
        // Redirect back to login page with error message
        // This is a basic way to show errors. A more integrated approach would be better.
        error_log("Oracle Connection Error: " . $error_message . " | User: " . $username . " | CS: " . $connection_string);
        header('Location: ../Frontend/index.html?error=' . urlencode("Connection Failed: " . $error_message));
        exit;
    }

} elseif (session_status() == 2 && isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['connection_string'])) {
    //echo $_SESSION['username'];
    $conn = oci_connect($_SESSION['username'], $_SESSION['password'], $_SESSION['connection_string'],'', OCI_SYSDBA);
    if (!$conn) {
        // Handle potential session connection failure, e.g., if DB was available before but not now
        error_log("Oracle Session Re-Connection Error: " . (oci_error() ? htmlentities(oci_error()['message']) : "Unknown error") . " | User: " . $_SESSION['username'] . " | CS: " . $_SESSION['connection_string']);
        // Optionally, destroy session and redirect to login
        // session_destroy();
        // header('Location: ../Frontend/index.html?error=session_reconnect_failed');
        // exit;
    }
}

?>
