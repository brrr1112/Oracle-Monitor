<?php
//header('Content-Type: application/json');
$username="sys";
$password="system";
$connection_string="localhost/xe";

$conn=oci_connect($username,$password,$connection_string,"", OCI_SYSDBA);

$HWM=0.95;

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

function getSGAMaxSize($conn){
  $query= oci_parse($conn,'begin :result := sys.fun_sga_maxsize; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  oci_close($conn);
  echo json_encode($result);
}

//['Used Mb', 'Free #1 Mb', 'Free #2 Mb'
function getTSBarInfo($conn,$HWM){
  $query= oci_parse($conn,'SELECT * FROM sys.rep_tsinfo');
  oci_execute($query);

  $rows = array();
  while($r = oci_fetch_array($query, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $Free1=0;
    $Free2=0;
    $temp = array();
    $temp[] = (string) $r["NAME"]; 
    $used = (float) $r["USED_MB"];
    $temp[] = $used;
    $total = (float) $r["TOTAL_SIZE"];
    
    $HWMsize = $total * $HWM;

    if($used > $HWMsize){
      $Free1=$total-$HWMsize;
      $temp[]=$Free1;
      $temp[]=$Free2;
    }
    elseif ($used < $HWMsize){
      $Free1=$HWMsize - $used;
      $Free2=$total-$HWMsize;
      $temp[]=$Free1;
      $temp[]=$Free2;
    }
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
    getTSBarInfo($conn,$HWM);
  break;

  case '1':
     getSwitchMinutes($conn);
  break;

}

?>