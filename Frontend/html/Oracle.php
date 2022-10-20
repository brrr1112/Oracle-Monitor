<?php
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
   /* while(1)
    {
        
        $stid = oci_parse($conn, 'SELECT sum(value)/1024/1024 "TOTAL SGA (MB)" FROM v$sga');
        oci_execute($stid);
        
        echo "<table border='1'>\n";
       while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
            echo "<tr>\n";
            foreach ($row as $item) {
                echo "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        
    }
    */

?>  
















