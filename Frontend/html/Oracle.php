<?php
$username="sys";
$password="system";
$connection_string="localhost/xe";
$conn=oci_connect($username,$password,$connection_string,"",OCI_SYSDBA);
   
    if($conn)
    {
        
    }
    else
    {
        echo"Not connected";
    }

?>  
