<?php


    // extract all the form fields and store them in variables
    $dbuser=$_POST['user'];
    $dbpass=$_POST['pass'];
    $hostName=$_POST['hostName'];
    $port=$_POST['port'];
    $db=$_POST['db'];

    $localdb=$hostName.'/'.$db;

    
    //Connect to DB
    $conn=oci_connect($dbuser, $dbpass, $localdb);
    if($conn)
    {
        echo"connected";
        header("Location: hola.html");
    }
    else
    {
        echo"Not connected";
    }



    /*
    $username="system";
    $password="119988";
    $connection_string="localhost/xe";
    $conn=oci_connect($username,$password,$connection_string);
   
    if($conn)
    {
        echo"connected";
    }
    else
    {
        echo"Not connected";
    }
    */


    
?> 