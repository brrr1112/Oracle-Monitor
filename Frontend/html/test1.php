<?php
    include_once('Oracle.php');

    $query= oci_parse($conn,'begin :result := switch_minutes_avg; end;');

    oci_bind_by_name($query, ':result', $result, 40);

    oci_execute($query);

    oci_free_statement($query);

    oci_close($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <p class="titu">Logs Status Monitor</p>
                    <img class="logo" src="logo.png">
                    <div class="button">
                        <a href="menu.html">
                            <button class="boton">Back to Menu</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
            <h1>Logs</h1>
            <br>
    
        <div class="form-control">
            <br><div class="table-responsive">
                <table class="table">
                    <thead class="text-muted">
                        <th>Tiempo Promedio de cambio de Logs</th>
            
                    </thead> 

                    <tbody>
                        
                            <tr>
                            <td><?php echo $result; ?></td>
                            </tr>
                        
                    </tbody>                  
                </table>
            </div>
        </div>
    </div>
</body>

</html>
