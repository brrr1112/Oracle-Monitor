
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="200">
    <title>Users Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style/users.css">
    <script defer type="text/javascript" src="js/rman.js"></script>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="title">RMAN Monitor</p>
                    <img class="logo" src="img/logo.png">
                </div>
            </div>
        </div>
        <div class="button">
            <a href="menu.html">
                <button class="boton">Back to Menu</button>
            </a>
        </div>

        <h1 class="title">RMAN Backup Job History</h1>
        <br>
        <div class="table-responsive">
            <table id="rmanJobsTable" class="table" style="table-layout: fixed">
                <thead class="text-start">
                    <th>Session Key</th>
                    <th>Input Type</th>
                    <th>Status</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Input Bytes</th>
                    <th>Output Bytes</th>
                    <th>Elapsed (s)</th>
                    <th>Compression</th>
                </thead>
                <tbody id="rmanJobsTableBody">
                </tbody>
            </table>
        </div>
        <br>
        <hr>
        <h2 class="title" style="margin-top: 30px;">Initiate RMAN Backup</h2>
        <p style="color: #ffffff; text-align: center;"><i>Note: Functionality to initiate backups will be revised for security. This section is temporarily simplified.</i></p>
       

        <div class="flexcontainer">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="child uno">
                    <div class="preguntas">
                        <h3 class="title" style="color:#ffffff">Select Backup Type</h3>
                        <input type="radio" id="backupTypeFull" name="backupType" value="full" checked>
                        <label for="backupTypeFull"><h4 style="color:#ffffff">Full Backup</h4></label><br>

                        <input type="radio" id="backupTypeTS" name="backupType" value="tablespace">
                        <label for="backupTypeTS"><h4 style="color:#ffffff">Tablespace Backup</h4></label><br>
                    </div>
                </div>
                <div class="child dos">
                    <div class="preguntas">
                        <label for="backupLocation"><h5 style="color:#ffffff">Backup Location (Full Path, e.g., /u01/backup or C:\oracle\backups):</h5></label><br>
                        <input type="text" id="backupLocation" name="backupLocation" value="" required><br>

                        <label for="backupFileNamePrefix"><h5 style="color:#ffffff; margin-top:10px">File Name Prefix (e.g., full_db_ora19c):</h5></label><br>
                        <input type="text" id="backupFileNamePrefix" name="backupFileNamePrefix" value="" required><br>

                        <div id="tablespaceNameDiv" style="display:none;">
                            <label for="tablespaceName"><h5 style="color:#ffffff; margin-top:10px">Tablespace Name (for Tablespace backup):</h5></label><br>
                            <input type="text" id="tablespaceName" name="tablespaceName" value=""><br>
                        </div>

                        <div id="fullBackupOptionsDiv">
                            <input type="checkbox" id="includeControlFile" name="includeControlFile" value="1">
                            <label for="includeControlFile"><h4 style="color:#ffffff; margin-top:10px">Include Control Files (Full Backup)</h4></label><br>

                            <input type="checkbox" id="includeArchiveLogs" name="includeArchiveLogs" value="1">
                            <label for="includeArchiveLogs"><h4 style="color:#ffffff">Include Archive Logs (Full Backup)</h4></label><br>
                        </div>

                        <h5 style="color:#ffffff; margin-top:15px;">Optional RMAN Catalog Connection:</h5>
                        <label for="catalogUser"><h5 style="color:#ffffff">Catalog User:</h5></label><br>
                        <input type="text" id="catalogUser" name="catalogUser" value=""><br>

                        <label for="catalogPassword"><h5 style="color:#ffffff; margin-top:10px">Catalog Password:</h5></label><br>
                        <input type="password" id="catalogPassword" name="catalogPassword" value=""><br><br>

                        <input type="submit" name="initiateBackup" value="Initiate Backup">
                    </div>
                </div>
            </form> 
            <script>
                // Show/hide tablespace input based on backup type
                document.querySelectorAll('input[name="backupType"]').forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        if (this.value === 'tablespace') {
                            document.getElementById('tablespaceNameDiv').style.display = 'block';
                            document.getElementById('tablespaceName').required = true;
                            document.getElementById('fullBackupOptionsDiv').style.display = 'none';
                        } else {
                            document.getElementById('tablespaceNameDiv').style.display = 'none';
                            document.getElementById('tablespaceName').required = false;
                            document.getElementById('fullBackupOptionsDiv').style.display = 'block';
                        }
                    });
                });
                // Trigger change event on load to set initial state
                document.querySelector('input[name="backupType"]:checked').dispatchEvent(new Event('change'));
            </script>
        </div>

            <?php
            // Secure RMAN Backup Initiation Logic
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['initiateBackup'])) {
                require_once('../server/oracle.php'); // Ensure $conn is available

                if (!$conn) {
                    echo "<div class='error-message' style='color:red; text-align:center;'>Database connection failed. Cannot initiate backup.</div>";
                    // Optionally log detailed error: error_log(oci_error()['message']);
                } else {
                    $backup_type = $_POST['backupType'];
                    $backup_location = $_POST['backupLocation'];
                    $file_name_prefix = $_POST['backupFileNamePrefix'];

                    $catalog_user = !empty($_POST['catalogUser']) ? $_POST['catalogUser'] : null;
                    $catalog_password = !empty($_POST['catalogPassword']) ? $_POST['catalogPassword'] : null;

                    $rman_script_content = null;
                    $status = '';
                    $message = '';
                    $proc_name = '';
                    $params = [];

                    if ($backup_type === 'full') {
                        $proc_name = 'PROC_START_RMAN_FULL_BACKUP';
                        $include_controlfile = isset($_POST['includeControlFile']) ? 1 : 0; // Pass as 1 or 0 for PL/SQL BOOLEAN
                        $include_archivelogs = isset($_POST['includeArchiveLogs']) ? 1 : 0; // Pass as 1 or 0

                        $stmt_str = "BEGIN $proc_name(:p_backup_path, :p_file_name_prefix, :p_include_controlfile, :p_include_archivelogs, :p_catalog_user, :p_catalog_password, :o_rman_script, :o_status, :o_message); END;";
                        $stmt = oci_parse($conn, $stmt_str);
                        oci_bind_by_name($stmt, ':p_backup_path', $backup_location);
                        oci_bind_by_name($stmt, ':p_file_name_prefix', $file_name_prefix);
                        oci_bind_by_name($stmt, ':p_include_controlfile', $include_controlfile, -1, SQLT_INT);
                        oci_bind_by_name($stmt, ':p_include_archivelogs', $include_archivelogs, -1, SQLT_INT);

                    } elseif ($backup_type === 'tablespace') {
                        $tablespace_name = $_POST['tablespaceName'];
                        if (empty($tablespace_name)) {
                            echo "<div class='error-message' style='color:orange; text-align:center;'>Tablespace name is required for tablespace backup.</div>";
                            exit;
                        }
                        $proc_name = 'PROC_START_RMAN_TS_BACKUP';
                        $stmt_str = "BEGIN $proc_name(:p_tablespace_name, :p_backup_path, :p_file_name_prefix, :p_catalog_user, :p_catalog_password, :o_rman_script, :o_status, :o_message); END;";
                        $stmt = oci_parse($conn, $stmt_str);
                        oci_bind_by_name($stmt, ':p_tablespace_name', $tablespace_name);
                        oci_bind_by_name($stmt, ':p_backup_path', $backup_location);
                        oci_bind_by_name($stmt, ':p_file_name_prefix', $file_name_prefix);
                    }

                    if (isset($stmt)) {
                        oci_bind_by_name($stmt, ':p_catalog_user', $catalog_user);
                        oci_bind_by_name($stmt, ':p_catalog_password', $catalog_password);

                        // For CLOB output
                        $rman_script_lob = oci_new_descriptor($conn, OCI_D_LOB);
                        oci_bind_by_name($stmt, ':o_rman_script', $rman_script_lob, -1, OCI_B_CLOB);

                        oci_bind_by_name($stmt, ':o_status', $status, 200);
                        oci_bind_by_name($stmt, ':o_message', $message, 2000);

                        if (!oci_execute($stmt)) {
                            $e = oci_error($stmt);
                            $status = 'ERROR';
                            $message = "OCI Execute Error: " . htmlentities($e['message']);
                        } else {
                            if ($status === 'SUCCESS' && $rman_script_lob !== null) {
                                $rman_script_content = $rman_script_lob->load();
                                $rman_script_lob->free();
                            }
                        }
                        oci_free_statement($stmt);
                    }

                    if ($status === 'SUCCESS' && !empty($rman_script_content)) {
                        // Define a secure, writable temporary directory for RMAN scripts
                        // IMPORTANT: This path MUST be outside the web root and have restricted permissions.
                        // For example, /tmp/rman_scripts/ or a dedicated directory.
                        // Ensure this directory exists and is writable by the web server user.
                        $temp_script_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oracle_monitor_rman_scripts';
                        if (!is_dir($temp_script_dir)) {
                            mkdir($temp_script_dir, 0700, true); // Create with restricted permissions
                        }
                        $temp_script_file = tempnam($temp_script_dir, 'rman_cmd_');

                        if ($temp_script_file === false) {
                            echo "<div class='error-message' style='color:red; text-align:center;'>Error: Could not create temporary RMAN script file. Check permissions for $temp_script_dir.</div>";
                        } else {
                            file_put_contents($temp_script_file, $rman_script_content);

                            // Execute RMAN using the generated script file
                            // Ensure 'rman' executable is in the PATH of the web server user, or use full path.
                            $rman_exec_command = "rman CMDELLE @" . escapeshellarg($temp_script_file);

                            // For security, it's better to use proc_open for more control if possible,
                            // but exec is simpler for this example if environment is controlled.
                            $exec_output = [];
                            $exec_return_var = -1;
                            exec($rman_exec_command, $exec_output, $exec_return_var);

                            unlink($temp_script_file); // Clean up the temporary script file

                            if ($exec_return_var === 0) {
                                echo "<div class='correct-message' style='color:lightgreen; text-align:center;'>RMAN backup process initiated successfully. Check RMAN logs and backup history for status.</div>";
                                echo "<pre style='color:white; background-color:black; padding:10px;'>" . htmlentities(implode("\n", $exec_output)) . "</pre>";
                            } else {
                                echo "<div class='error-message' style='color:red; text-align:center;'>RMAN execution failed. Return code: $exec_return_var.</div>";
                                echo "<pre style='color:white; background-color:darkred; padding:10px;'>" . htmlentities(implode("\n", $exec_output)) . "</pre>";
                                error_log("RMAN Execution Failed for script ($temp_script_file created from procedure $proc_name): " . implode("\n", $exec_output) . " | Return: " . $exec_return_var);

                            }
                        }
                    } else {
                        echo "<div class='error-message' style='color:red; text-align:center;'>Failed to generate RMAN script: " . htmlentities($message) . " (Status: " . htmlentities($status) . ")</div>";
                         error_log("RMAN Script Generation Failed from procedure $proc_name: " . $message . " (Status: " . $status . ")");
                    }
                    
                    if (isset($conn)) {
                       // oci_close($conn); // Connection is managed by oracle.php, might be reused.
                    }
                }
            }
            ?>
        
    </div>
</body>

</html>
