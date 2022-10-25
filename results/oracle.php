<?php
//header('Content-Type: application/json');
$username="sys";
$password="root";
$connection_string="localhost/xe";

$conn=oci_connect($username,$password,$connection_string, null, OCI_SYSDBA);


function getSGATable($conn){
  $query= oci_parse($conn,'select USED_MB, TIME, TOTAL_MB from sys.job_SGA_Table');
  oci_execute($query);
  
  $rows = array();
  while($r = oci_fetch_array($query, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $temp = array();
    $temp[] = (string) $r["TIME"]; 
    $temp[] = (float) $r["USED_MB"];
    $temp[] = (float) $r["TOTAL_MB"] * 0.85;
    $rows[] = $temp;
  }
  $var = json_encode($rows);

  oci_free_statement($query);
  oci_close($conn);

  echo $var;
}

function getTablespaceNames($conn){
  $query= oci_parse($conn,'select tablespace_name from Dba_data_files');
  oci_execute($query);
  
  $rows = array();
  while($r = oci_fetch_array($query, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $rows[] = (string) $r["TABLESPACE_NAME"];
  }
  $var = json_encode($rows);

  oci_free_statement($query);
  oci_close($conn);

  echo $var;
}

/*
function getTablespaceData($conn, $tsname){
  $p_cursor = oci_new_cursor($conn);
  $query = oci_parse($conn,'begin :cursor := sys.get_tablespace_info(); end;');

  //oci_bind_by_name($query, ':p', $p);
  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);
  
  oci_execute($query);
  oci_execute($p_cursor);
  $rows = array();
  //while($r = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) {
    //$rows[] = $r;
    //echo $r["BYTES_SIZE"];
  //}
  oci_fetch_all($p_cursor, $cursor, null, null, OCI_FETCHSTATEMENT_BY_ROW);
  print_r($p_cursor);
  $var = json_encode($rows);

  oci_free_statement($query);
  oci_close($conn);

  echo $var;
}
*/


function getSGAMaxSize($conn){
  $query= oci_parse($conn,'begin :result := sys.fun_sga_maxsize; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  oci_close($conn);
  echo json_encode($result);
}

//
function getTSBarInfo($conn){
  $query= oci_parse($conn,`    SELECT
    ts.tablespace_name,
    TRUNC("SIZE(B)", 2)                                  "BYTES_SIZE",
    TRUNC(fr."FREE(B)", 2)                               "BYTES_FREE",
    TRUNC("SIZE(B)" - "FREE(B)", 2)                      "BYTES_USED",
    TRUNC((1 - (fr."FREE(B)" / df."SIZE(B)")) * 100, 10) "PCT_USED"
    FROM (SELECT tablespace_name, SUM(bytes) "FREE(B)" FROM dba_free_space GROUP BY tablespace_name) fr,
      (SELECT tablespace_name, SUM(bytes) "SIZE(B)", SUM(maxbytes) "MAX_EXT" FROM dba_data_files GROUP BY tablespace_name) df,
      (SELECT tablespace_name FROM dba_tablespaces where tablespace_name = Ptsname ) ts
    WHERE fr.tablespace_name = df.tablespace_name AND fr.tablespace_name = ts.tablespace_name`);
  
  oci_execute($query);

  $rows = array();
  while($r = oci_fetch_array($query, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $temp = array();
    $temp[] = (float) $r["TABLESPACE_NAME"]; 
    $temp[] = (float) $r["BYTES"];
    $temp[] = (float) $r["TOTAL_MB"] * 0.85;
    $rows[] = $temp;
  }
  $var = json_encode($rows);
    
  oci_free_statement($query);
  oci_close($conn);
    
  echo $var;
}

//

function getSwitchMinutes($conn){
  $query= oci_parse($conn,'begin :result := sys.switch_minutes_avg; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  oci_close($conn);
  echo json_encode($result);
}

switch($_GET['q']){
  case 'sga':
    getSGATable($conn);
  break;

  case 'sgasize':
      getSGAMaxSize($conn);
  break; 
  case 'tsnames':
    getTablespaceNames($conn);
  break;

  case 'tsbar':
    getTSBarInfo($conn);
  break;

  case '1':
     getSwitchMinutes($conn);
  break;

}

?>