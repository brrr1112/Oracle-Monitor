<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Essential: Check for application user login and selected Oracle DB profile
if (!isset($_SESSION['app_user_id'])) {
    echo json_encode(['error' => 'Application authentication required. Please login to the monitoring tool.']);
    exit;
}
// This 'selected_oracle_conn_id' will be set by the UI in Phase 4.
// For now, to allow testing of results.php structure, we might need a placeholder or default if not set.
// However, in a live system, this MUST be set by user action.
if (!isset($_SESSION['selected_oracle_conn_id'])) {
    echo json_encode(['error' => 'No Oracle database connection profile selected for monitoring. Please select a profile.']);
    exit;
}

require_once __DIR__ . '/oracle.php'; // Makes establish_oracle_connection() available

$app_user_id = $_SESSION['app_user_id'];
$selected_oracle_conn_id = $_SESSION['selected_oracle_conn_id'];

$conn = establish_oracle_connection($app_user_id, $selected_oracle_conn_id);

if (!$conn) {
    // establish_oracle_connection sets a session error message.
    $error_message = $_SESSION['oracle_connection_error'] ?? 'Failed to connect to the selected Oracle database. Please check profile settings or database status.';
    unset($_SESSION['oracle_connection_error']); // Clear it after displaying
    echo json_encode(['error' => $error_message, 'detail_from_oracle_php' => true]);
    exit;
}

// If connection is successful, proceed with existing logic using $conn

$HWM_percentage = 0.95; // Renamed from HWM for clarity, as used in previous steps

function setTimeFormat($conn)
{
  $query1 = oci_parse($conn, 'alter SESSION set NLS_TIMESTAMP_FORMAT = "yyyy-mm-dd/hh24:mi:ss"');
  $query2 = oci_parse($conn, 'alter SESSION set NLS_DATE_FORMAT = "yyyy-mm-dd/hh24:mi:ss"');
  oci_execute($query1);
  oci_execute($query2);
  oci_free_statement($query1);
  oci_free_statement($query2);
}

/*
 Users section
*/

function getUsernames($conn)
{
  $query = oci_parse($conn, 'begin :cursor := sys.fun_get_usernames; end;');
  $p_cursor = oci_new_cursor($conn);
  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);

  oci_execute($query);
  oci_execute($p_cursor, OCI_DEFAULT);

  $users = array();

  while ($r = oci_fetch_array($p_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $users[] = (string) $r['USERNAME'];
  }

  oci_free_statement($query);
  echo json_encode($users);
}

function getUsersSQL($conn)
{
  $where = "";
  $username = null;
  if (!empty($_GET['user'])) {
    $username = $_GET['user'];
    $where = "where PARSEUSER ='$username'";
  }

  $query = oci_parse($conn, "Select PARSEUSER, COMMAND_TYPE, FIRST_LOAD_TIME, SQL_TEXT from sys.view_table_user_SQL $where");
  oci_execute($query);
  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $temp = array();
    $temp[] = (string) $r['PARSEUSER'];
    $temp[] = (string) $r['FIRST_LOAD_TIME'];
    $temp[] = (string) $r['COMMAND_TYPE'];
    $temp[] = (string) $r['SQL_TEXT'];
    $rows[] = $temp;
  }

  echo json_encode($rows);
  oci_free_statement($query);
}

/*
SGA Section
*/

function getSGATable($conn, $HWM)
{
  $query = oci_parse($conn, 'select USED_MB, TIME, TOTAL_MB from sys.job_SGA_Table');
  oci_execute($query);

  $rows = array();
  while ($r = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $temp = array();
    $temp[] = (string) $r["TIME"];
    $temp[] = (float) $r["USED_MB"];
    $temp[] = (float) $r["TOTAL_MB"] * $HWM;
    $rows[] = $temp;
  }
  $var = json_encode($rows);

  oci_free_statement($query);
  echo $var;
}

function getSGAMaxSize($conn)
{
  $query = oci_parse($conn, 'begin :result := sys.fun_sga_maxsize; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  echo json_encode($result);
}

function isSGAGreatherHWM($conn, $HWM)
{
  $query = oci_parse($conn, 'begin :result := sys.fun_sga_usedsize; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);

  $sql = oci_parse($conn, 'begin :result := sys.fun_get_sga_maxsize; end;');
  oci_bind_by_name($sql, ':result', $max, 20);
  oci_execute($sql);
  oci_free_statement($sql);
  if ($result >= ($max * $HWM)) {
    echo json_decode(1);
  } else {
    echo json_decode(0);
  }
}

function writeAlertCSV($alertArray)
{
  $file = fopen($_SESSION['username'] . '.csv', 'a+');
  foreach ($alertArray as $fields) {
    fputcsv($file, $fields);
  }
  fclose($file);
}

function getSGAAlerts($conn)
{

  setTimeFormat($conn);

  $query = oci_parse($conn, 'begin :cursor := sys.fun_get_sgaAlerts; end;');
  $p_cursor = oci_new_cursor($conn);

  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);
  oci_execute($query);
  oci_execute($p_cursor, OCI_DEFAULT);

  $alertArray = array();

  if ($p_cursor != null) {
    while ($r = oci_fetch_array($p_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {
      $temp = array();
      $temp[] = (string) $r['USERNAME'];
      $temp[] = (string) $r['LOAD_TIME'];
      $temp[] = (string) $r['SQL'];
      $temp[] = (string) $r['STATUS'];
      $alertArray[] = $temp;
    }

    writeAlertCSV($alertArray);
  }

  echo json_encode($alertArray);
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
  echo $var;
}

function getTSAllInfo($conn, $HWM_percentage){ // Renamed $HWM to $HWM_percentage for clarity
  $query = oci_parse($conn, 'begin :cursor := sys.fun_get_TS_allInfo; end;');
  $p_cursor = oci_new_cursor($conn);

  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);
  oci_execute($query);
  oci_execute($p_cursor, OCI_DEFAULT);

  $tsinfo = array();

  if ($p_cursor != null) {
    while ($r = oci_fetch_array($p_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {
      $temp = array();
      $temp[] = (string) $r['NAME'];
      // Use ACTUAL_USED_MB for "Used (MB)" column as it's more representative of data volume
      $temp[] = (float) $r['ACTUAL_USED_MB'];
      $temp[] = (float) $r['FREE_MB'];
      $total_mb = (float) $r['TOTAL_MB']; // This is effective total
      $temp[] = $total_mb;
      // HWM calculated as a percentage of the total effective size
      $temp[] = round($total_mb * $HWM_percentage, 2);
      $temp[] = (float) $r['DAILY_GROWTH_MB'];
      $temp[] = (int) $r['REMAINING_TIME_DAYS']; // This is now in days
      // $temp[] = (string) $r['USED_MB']; // Original allocated space, could be added as an extra column if needed
      $tsinfo[] = $temp;
    }
    oci_free_statement($query);
    oci_free_cursor($p_cursor);
  }

  echo json_encode($tsinfo);
}

/*
LOGS SECTION
*/

function getLogsInfo($conn)
{
  $query = oci_parse($conn, 'begin :cursor := sys.fun_get_logsinfo; end;');
  $p_cursor = oci_new_cursor($conn);
  oci_bind_by_name($query, ':cursor', $p_cursor, -1, OCI_B_CURSOR);

  oci_execute($query);
  oci_execute($p_cursor, OCI_DEFAULT);

  $rows = array();
  while ($r = oci_fetch_array($p_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $info = array();
    $info[] = (string) $r['GROUP#'];
    $info[] = (string) $r['MEMBERS'];
    $info[] = (string) $r['STATUS'];
    $rows[] = $info;
  }

  oci_free_statement($query);
  echo json_encode($rows);
}

function getSwitchMinutes($conn)
{
  $query = oci_parse($conn, 'begin :result := sys.switch_minutes_avg; end;');
  oci_bind_by_name($query, ':result', $result, 20);
  oci_execute($query);
  oci_free_statement($query);
  echo json_encode($result);
}

function getLogMode($conn)
{
  $query =  oci_parse($conn, 'begin :mode := sys.fun_get_logMode; end;');
  oci_bind_by_name($query, ':mode', $result, 16);
  oci_execute($query);
  oci_free_statement($query);
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
    getSGATable($conn, $HWM);
    break;

  case 'sgasize':
    getSGAMaxSize($conn);
    break;
  case 'sgastatus':
    isSGAGreatherHWM($conn, $HWM);
    break;
  case 'sgaalerts':
    getSGAAlerts($conn);
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
  case 'tsinfo':
    getTSAllInfo($conn, $HWM);
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

  case 'rmanjobs':
    $curs = oci_new_cursor($conn);
    // Ensure the PL/SQL function name matches what was created in Script.sql
    $stid = oci_parse($conn, "begin :curs := fun_get_rman_backup_jobs(); end;");
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);
    oci_execute($stid);
    oci_execute($curs);
    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        $data[] = $row;
    }
    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
    break;

  oci_close($conn);
}

/*
TOP N SQL Monitoring
*/
function getTopNSQL($conn) {
    $metric = isset($_GET['metric']) ? strtoupper($_GET['metric']) : 'CPU_TIME';
    $top_n = isset($_GET['top_n']) ? intval($_GET['top_n']) : 10;

    // Basic validation for top_n to ensure it's a reasonable positive integer
    if ($top_n <= 0 || $top_n > 100) { // Cap at 100 for sanity
        $top_n = 10;
    }

    // Metric validation is handled by the PL/SQL function's CASE statement

    $curs = oci_new_cursor($conn);
    $stid = oci_parse($conn, "begin :curs := fun_get_top_sql(:p_metric, :p_top_n); end;");
    oci_bind_by_name($stid, ":p_metric", $metric);
    oci_bind_by_name($stid, ":p_top_n", $top_n);
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);

    oci_execute($stid);
    oci_execute($curs);

    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        // Ensure SQL_TEXT_SNIPPET is properly handled if it's a LOB or long string
        if (is_object($row['SQL_TEXT_SNIPPET'])) { // Check if it's a LOB object
            $row['SQL_TEXT_SNIPPET'] = $row['SQL_TEXT_SNIPPET']->load();
        }
        $data[] = $row;
    }

    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
}

/*
System-Wide Wait Event Monitoring
*/
function getSystemWaitSummary($conn) {
    $top_n = isset($_GET['top_n']) ? intval($_GET['top_n']) : 10;
    // Basic validation for top_n
    if ($top_n <= 0 || $top_n > 50) { // Cap at 50 for sanity
        $top_n = 10;
    }

    $curs = oci_new_cursor($conn);
    $stid = oci_parse($conn, "begin :curs := fun_get_system_wait_summary(:p_top_n); end;");
    oci_bind_by_name($stid, ":p_top_n", $top_n);
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);

    oci_execute($stid);
    oci_execute($curs);

    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        $data[] = $row;
    }

    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
}

/*
Performance Dashboard PHP Functions
*/

function getPerformanceRatios($conn) {
    $curs = oci_new_cursor($conn);
    $stid = oci_parse($conn, "begin :curs := fun_get_performance_ratios(); end;");
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);
    oci_execute($stid);
    oci_execute($curs);
    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        $data[] = $row;
    }
    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
}

function getCallRates($conn) {
    $curs = oci_new_cursor($conn);
    $stid = oci_parse($conn, "begin :curs := fun_get_call_rates(); end;");
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);
    oci_execute($stid);
    oci_execute($curs);
    $data = array(); // Expecting a single row
    if (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        // Convert SYSTIMESTAMP to a more JS-friendly format if needed, or ensure PL/SQL does.
        // Here, assuming CURRENT_DB_TIME is a string representation from Oracle that JS can parse or use.
        // If it's an OCI-Lob object for timestamp, it would need ->load()
         if (isset($row['CURRENT_DB_TIME']) && is_object($row['CURRENT_DB_TIME'])) {
             $row['CURRENT_DB_TIME'] = $row['CURRENT_DB_TIME']->load();
         }
        $data = $row;
    }
    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
}

function getTopSessionsByResource($conn) {
    $resource_metric = isset($_GET['resource_metric']) ? $_GET['resource_metric'] : 'CPU used by this session';
    $top_n = isset($_GET['top_n']) ? intval($_GET['top_n']) : 5;

    if ($top_n <= 0 || $top_n > 50) { // Cap for sanity
        $top_n = 5;
    }
    // Basic sanitization/validation for resource_metric can be added here if needed,
    // though the PL/SQL uses it in a WHERE clause which is safer than dynamic SQL column names.
    // Example: Check against a list of allowed metrics.
    $allowed_metrics = [
        'CPU used by this session',
        'session logical reads',
        'physical reads',
        'physical reads direct',
        'execute count',
        'parse count (total)'
    ];
    if (!in_array($resource_metric, $allowed_metrics)) {
        // Fallback to a default or return an error
        $resource_metric = 'CPU used by this session';
        // Alternatively: echo json_encode(['error' => 'Invalid resource metric specified.']); return;
    }


    $curs = oci_new_cursor($conn);
    $stid = oci_parse($conn, "begin :curs := fun_get_top_sessions_by_resource(:p_resource_metric_name, :p_top_n); end;");
    oci_bind_by_name($stid, ":p_resource_metric_name", $resource_metric);
    oci_bind_by_name($stid, ":p_top_n", $top_n);
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);

    oci_execute($stid);
    oci_execute($curs);

    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        $data[] = $row;
    }
    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
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
    getSGATable($conn, $HWM_percentage);
    break;

  case 'sgasize':
    getSGAMaxSize($conn);
    break;
  case 'sgastatus':
    isSGAGreatherHWM($conn, $HWM_percentage);
    break;
  case 'sgaalerts':
    getSGAAlerts($conn);
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
    getTSBarInfo($conn, $HWM_percentage);
    break;
  case 'tsinfo':
    getTSAllInfo($conn, $HWM_percentage);
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

  case 'rmanjobs':
    $curs = oci_new_cursor($conn);
    // Ensure the PL/SQL function name matches what was created in Script.sql
    $stid = oci_parse($conn, "begin :curs := fun_get_rman_backup_jobs(); end;");
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);
    oci_execute($stid);
    oci_execute($curs);
    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        $data[] = $row;
    }
    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
    break;

  /*
  NEWLY ADDED
  */
  case 'topnsql':
    getTopNSQL($conn);
    break;

  case 'activesessions':
    getActiveSessions($conn);
    break;

  case 'systemwaits':
    getSystemWaitSummary($conn);
    break;

  // Performance Dashboard endpoints
  case 'performanceratios':
    getPerformanceRatios($conn);
    break;
  case 'callrates':
    getCallRates($conn);
    break;
  case 'topsessions_resource':
    getTopSessionsByResource($conn);
    break;

  oci_close($conn);
}

/*
Session Monitoring
*/
function getActiveSessions($conn) {
    $curs = oci_new_cursor($conn);
    // For now, calling without parameters. Can be extended later if the PL/SQL func takes params (e.g. for status)
    $stid = oci_parse($conn, "begin :curs := fun_get_active_sessions(); end;");
    oci_bind_by_name($stid, ":curs", $curs, -1, OCI_B_CURSOR);

    oci_execute($stid);
    oci_execute($curs);

    $data = array();
    while (($row = oci_fetch_array($curs, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
        $data[] = $row;
    }

    oci_free_statement($stid);
    oci_free_statement($curs);
    echo json_encode($data);
}
