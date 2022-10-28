<?php
$username="sys";
$password="root";
$connection_string="localhost/xe";
$conn=oci_connect($username,$password,$connection_string,null,OCI_SYSDBA);
   
    if($conn)
    {
        
    }
    else
    {
        echo"Not connected";
    }

?>  
