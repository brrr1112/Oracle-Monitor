
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
    <script defer type="text/javascript" src="js/users.js"></script>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="title">Users Monitor</p>
                    <img class="logo" src="img/logo.png">
                </div>
            </div>
        </div>
        <div class="button">
            <a href="menu.html">
                <button class="boton">Back to Menu</button>
            </a>
        </div>

        <h1 class="title">RMAN</h1>
        <br>
        <h2 class="title">Create Backup</h2>

        <div class="Container">
            <div class="child1">
                <form action="/action_page.php">
                   
                </form> 
            </div>
            <div class="child2">
                <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
                     <label for="user">First name:</label><br>
                    <input type="text" id="user" name="user" value="user"><br>
                    <label for="password">Last name:</label><br>
                    <input type="password" id="password" name="password" value="password"><br><br>
                    <input type="checkbox" id="controlFile" name="controlFile" value="controlFile">
                    <label for="controlFile"><h3 style="color:#ffffff">Include control Files</h3></label><br>
                    <input type="checkbox" id="logFile" name="logFile" value="logFile">
                    <label for="controlFile"><h3 style="color:#ffffff">Include log Files</h3></label><br>
                    <input type="submit" value="Submit">
                </form> 
            </div>

            <?php
                $user ="";
                $password ="";
                $controlFile ="";
                $logFile ="";
            if(!empty($_POST)){
                $user=$_POST['user'];
                $password=$_POST['password'];
                $controlFile=$_POST['controlFile'];
                $logFile=$_POST['logFile'];
            }
                $myfile = fopen("rman.txt", "a") or die("Unable to open file!");
                $txt = "connect target /\n connect catalog $user/$password\n";
                fwrite($myfile, $txt);
                $txt = "run{allocate channel CH1 device type DISK format 'C:\app\BACKUPS/%U_backups_.bak'; backup database include current controlfile plus archivelog delete all input;}";
                fwrite($myfile, $txt);
                fclose($myfile);
            ?>


        </div>



    </div>
</body>

</html>

<!--
<div class="form-control">
            <br><div class="table-responsive">
                <table class="table">
                    <thead class="text-muted">
                        <th>Log Mode</th>
            
                    </thead>    
                    <tbody>
                            
                        <?php while($row = oci_fetch_array($sto, OCI_ASSOC+OCI_RETURN_NULLS)){?>
                            <tr>
                                <td><?php echo $row['LOG_MODE']; ?></td>
                            </tr>
                        <?php } ?>
                           
                    </tbody>                  
                </table>
            </div>
</div>

<?php
    include_once('Oracle.php');

    $sto = oci_parse($conn,'select LOG_MODE from V$DATABASE');
    oci_execute($sto);
    $resultado = oci_execute($sto);
    oci_close($conn);
?>
-->