<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_REQUEST['user'];
    $password = $_REQUEST['pass'];
    $connection_string = $_REQUEST['server'].'/'.$_REQUEST['db'];
    $port = $_REQUEST['port'];
    echo $connection_string;
    $conn = oci_connect($username,$password,$connection_string,null,OCI_SYSDBA);
    if($conn){
        header('Location: ../Frontend/menu.html');
    }
    else{
        //header('Location: ../Frontend/index.html');
    }

}

?>  
