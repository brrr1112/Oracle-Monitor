<?php
    include_once('Oracle.php');
    //$sql = 'select distinct au.USERNAME parseuser, vs.sql_text, vs.executions, vs.users_opening, to_char(to_date(vs.first_load_time, 'YYYY-MM-DD/HH24:MI:SS'),'MM/DD HH24:MI:SS') first_load_time, rows_processed, a.name command_type from v$sqlarea vs , all_users au, audit_actions a where (au.user_id(+)=vs.parsing_user_id) and (executions >= 1) and vs.COMMAND_TYPE = a.ACTION order by USERNAME;';
    //$resultado = $conn->query($sql);

    $stid = oci_parse($conn,'Select PARSEUSER,COMMAND_TYPE,FIRST_LOAD_TIME,SQL_TEXT from sys.view_table_user_SQL');
    oci_execute($stid);
    $resultado = oci_execute($stid);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="200" >
    <title>SGA Size Monitor</title>
    <link rel="stylesheet" href="tableuser.css">
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
    <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">SGA Size Monitor</p>
                    <img class="logo" src="logo.png">
                    <div class="button">
                        <a href="menu.html">
                            <button class="boton">Back to Menu</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
            <h1>Tabla Usuarios</h1>
            <br>

        <div class="form-control">
            <br><div class="table-responsive">
                <table class="table">
                    <thead class="text-muted">
                        <th>User</th>
                        <th>first_load_time</th> 
                        <th>COMMAND_TYPE</th> 
                        <th>Sql_text</th>
            
                    </thead> 

                    <tbody>
                        <?php while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){?>
                            <tr>
                            <td><?php echo $row['PARSEUSER']; ?></td>
                            <td><?php echo $row['FIRST_LOAD_TIME']; ?></td>
                            <td><?php echo $row['COMMAND_TYPE']; ?></td>
                            <td><?php echo $row['SQL_TEXT']; ?></td>
                            
                            
                            </tr>
                        <?php } ?>
                    </tbody>                  
                </table>
            </div>
        </div>
    </div>
</body>

</html>