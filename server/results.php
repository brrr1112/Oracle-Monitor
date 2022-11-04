<?php

use LDAP\Result;

header('Content-Type: application/json');
$username = "sys";
$password = "root";
$connection_string = "localhost/xe";

$conn = oci_connect($username, $password, $connection_string, null, OCI_SYSDBA);

$HWM = 0.95;

/*
 Users section
*/

function getUsernames($conn){
  $query = oci_parse($conn,'begin :cursor := sys.fun_get_usernames; end;');
  $p_cursor = oci_new_cursor($conn);
  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);
  
  oci_execute($query);
  oci_execute($p_cursor, OCI_DEFAULT);

  $users = array();

  while ($r = oci_fetch_array($p_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $users[] = (string) $r['USERNAME'];

  }

  oci_free_statement($query);
  oci_close($conn);
  
  echo json_encode($users);
}

function getUsersSQL($conn){
  //$query =  oci_parse($conn, 'begin :result := sys.fun_');
  $where = "";
  $username = null;
  if(!empty($_GET['user'])){
    $username = $_GET['user'];
    $where = "where PARSEUSER ='$username'";
  }

  $query = oci_parse($conn,"Select PARSEUSER, COMMAND_TYPE, FIRST_LOAD_TIME, SQL_TEXT from sys.view_table_user_SQL $where");
  oci_execute($query);
  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)){
    $temp = array();
    $temp[] = (string) $r['PARSEUSER'];
    $temp[] = (string) $r['FIRST_LOAD_TIME'];
    $temp[] = (string) $r['COMMAND_TYPE'];
    $temp[] = (string) $r['SQL_TEXT'];
    $rows[] = $temp;
  }

  echo json_encode($rows);
  
  oci_free_statement($query);
  oci_close($conn);
}

/*
SGA Section
*/

function getSGATable($conn)
{
  $query = oci_parse($conn, 'select USED_MB, TIME, TOTAL_MB from sys.job_SGA_Table');
  oci_execute($query);

  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
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

function getSGAMaxSize($conn)
{
  $query = oci_parse($conn, 'begin :result := sys.fun_sga_maxsize; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  oci_close($conn);
  echo json_encode($result);
}

/*
Tablespace Section
*/

function getTablespaceNames($conn)
{
  $query = oci_parse($conn, 'select tablespace_name from Dba_data_files');
  oci_execute($query);

  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $rows[] = (string) $r["TABLESPACE_NAME"];
  }
  $var = json_encode($rows);

  oci_free_statement($query);
  oci_close($conn);

  echo $var;
}

function getTSPieInfo($conn)
{
  $query = oci_parse($conn, 'select tablespace_name, sum(bytes) total from Dba_data_files Group by tablespace_name');
  oci_execute($query);

  $rows = array();
  $table = array();
  $table['cols'] = array(
    array('label' => 'tablespace_name', 'type' => 'string'),
    array('label' => 'USED_MB Total', 'type' => 'number'),
  );

  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $temp = array();
    $temp[] = array('v' => (string) $r["TABLESPACE_NAME"]);
    $temp[] = array('v' => (int) $r["TOTAL"]);
    $rows[] = array('c' => $temp);
  }


  $table['rows'] = $rows;
  echo json_encode($table);
}

function getTSBarInfo($conn, $HWM)
{
  $query = oci_parse($conn, 'SELECT * FROM sys.rep_tsinfo');
  oci_execute($query);

  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $Free1 = 0;
    $Free2 = 0;
    $temp = array();
    $temp[] = (string) $r["NAME"];
    $used = (float) $r["USED_MB"];
    $temp[] = $used;
    $total = (float) $r["TOTAL_SIZE"];

    $HWMsize = $total * $HWM;

    if ($used > $HWMsize) {
      $Free1 = $total - $HWMsize;
      $temp[] = $Free1;
      $temp[] = $Free2;
    } elseif ($used < $HWMsize) {
      $Free1 = $HWMsize - $used;
      $Free2 = $total - $HWMsize;
      $temp[] = $Free1;
      $temp[] = $Free2;
    }
    $rows[] = $temp;
  }
  $var = json_encode($rows);

  oci_free_statement($query);
  oci_close($conn);

  echo $var;
}

/*
LOGS SECTION
*/

function getLogsInfo($conn){
  $query = oci_parse($conn,'begin :cursor := sys.fun_get_logsinfo; end;');
  $p_cursor = oci_new_cursor($conn);
  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);
  
  oci_execute($query);
  oci_execute($p_cursor, OCI_DEFAULT);

  $info = array();
  $rows = array();
  while ($r = oci_fetch_array($p_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $info = array();
    $info[] = (string) $r['GROUP#'];
    $info[] = (string) $r['MEMBERS'];
    $info[] = (string) $r['STATUS'];
    $rows[] = $info;
  }

  oci_free_statement($query);
  oci_close($conn);
  
  echo json_encode($rows);
}

function getSwitchMinutes($conn){
  $query = oci_parse($conn, 'begin :result := sys.switch_minutes_avg; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  oci_close($conn);
  echo json_encode($result);
}

function getLogMode($conn){
  $query =  oci_parse($conn, 'begin :mode := sys.fun_get_logMode; end;');
  oci_bind_by_name($query, ':mode', $result,16);
  oci_execute($query);
  oci_free_statement($query);
  oci_close($conn);
  echo json_encode($result);
}

//CONTROLLERR
switch ($_GET['q']) {
  /*
  USER SQL
  */
  case 'usernames':
    getUsernames($conn);
    break;
  
  case 'usersql':
    getUsersSQL($conn);
    break;
  /*
  SGA
  */
  case 'sga':
    getSGATable($conn);
    break;

  case 'sgasize':
    getSGAMaxSize($conn);
    break;
  /*
  TABLESPACE
  */
  case 'tspie':
    getTSPieInfo($conn);
    break;
  
  case 'tsnames':
    getTablespaceNames($conn);
    break;

  case 'tsbar':
    getTSBarInfo($conn, $HWM);
    break;
  /*
  LOGS
  */
  case 'logsinfo':
    getLogsInfo($conn);
    break;

  case 'logsswitch':
    getSwitchMinutes($conn);
    break;
  
  case 'logmode':
    getLogMode($conn);
    break;
}
