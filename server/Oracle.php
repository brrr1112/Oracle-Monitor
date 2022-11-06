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
    $_SESSION['username'] = $username = $_REQUEST['user'];
    $_SESSION['password'] = $password = $_REQUEST['pass'];
    $_SESSION['connection_string'] = $connection_string = $_REQUEST['server'] . '/' . $_REQUEST['db'];
    $port = $_REQUEST['port'];

    $conn = oci_connect($username, $password, $connection_string, null, OCI_SYSDBA);

    if ($conn) {
        //echo "connected";
        header('Location: ../Frontend/menu.html');
    } else {
        //header('Location: ../Frontend/index.html');
    }

} elseif (session_status() == 2) {
    //echo $_SESSION['username'];
    $conn = oci_connect($_SESSION['username'], $_SESSION['password'], $_SESSION['connection_string'], null, OCI_SYSDBA);
}

?>