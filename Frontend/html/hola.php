<?php
    include_once('Oracle.php');
    //$sql = 'select distinct au.USERNAME parseuser, vs.sql_text, vs.executions, vs.users_opening, to_char(to_date(vs.first_load_time, 'YYYY-MM-DD/HH24:MI:SS'),'MM/DD HH24:MI:SS') first_load_time, rows_processed, a.name command_type from v$sqlarea vs , all_users au, audit_actions a where (au.user_id(+)=vs.parsing_user_id) and (executions >= 1) and vs.COMMAND_TYPE = a.ACTION order by USERNAME;';
    //$resultado = $conn->query($sql);

    $stid = oci_parse($conn,'Select PARSEUSER,SQL_TEXT,EXECUTIONS,USERS_OPENING,FIRST_LOAD_TIME,ROWS_PROCESSED,COMMAND_TYPE from sys.view_table_user_SQL');
    oci_execute($stid);
    $resultado = oci_execute($stid);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3"
        crossorigin="anonymous"></script>
    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
</head>

<body>
    <div class="container">

        
            <h1>Tabla Usuarios</h1>
            <br>

        <div class="form-control">
            <br><div class="table-responsive">
                <table class="table">
                    <thead class="text-muted">
                        <th>User</th>
                        <th>Sql_text</th>
                        <th>Executions</th>
                        <th>Users_opening</th>   
                        <th>first_load_time</th> 
                        <th>rows processed</th>  
                        <th>COMMAND_TYPE</th>  
                    </thead> 

                    <tbody>
                        <?php while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){?>
                            <tr>
                            <td><?php echo $row['PARSEUSER']; ?></td>
                            <td><?php echo $row['SQL_TEXT']; ?></td>
                            <td><?php echo $row['EXECUTIONS']; ?></td>
                            <td><?php echo $row['USERS_OPENING']; ?></td>
                            <td><?php echo $row['FIRST_LOAD_TIME']; ?></td>
                            <td><?php echo $row['ROWS_PROCESSED']; ?></td>
                            <td><?php echo $row['COMMAND_TYPE']; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>                  
                </table>
            </div>
        </div>
    </div>
</body>

</html>