
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

        <h1 class="title">RMAN</h1>
        <br>
       

        <div class="flexcontainer">
        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
            <div class="child uno">
            <h2 class="title">Create Backup</h2>
                    <input type="checkbox" id="fullbackup" name="fullbackup" value="Full Backup">
                    <label for="fullbackup"><h3 style="color:#ffffff">Full Backup</h3></label><br>

                    <input type="checkbox" id="Inconsistente" name="Inconsistente" value="Inconsistente">
                    <label for="Inconsistente"><h3 style="color:#ffffff">Inconsistente</h3></label><br>
            </div>
            <div class="child dos">
                    <label for="user">First name:</label><br>
                    <input type="text" id="user" name="user" value=""><br>

                    <label for="password">Last name:</label><br>
                    <input type="password" id="password" name="password" value=""><br><br>

                    <label for="user">Location:</label><br>
                    <input type="text" id="Location" name="Location" value="C:\app\"><br>

                    <label for="user">File Name:</label><br>
                    <input type="text" id="filename" name="fileName" value=""><br>

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
                $aux = "";
                $Location= "";
                $fileName= "";
                $auxLF= "";

                if(!empty($_POST['controlFile'])){
                    $controlFile=$_POST['controlFile'];
                }

                if(!empty($_POST['logFile'])){
                    $logFile=$_POST['logFile'];
                }

                if(!empty($_POST)){
                $user=$_POST['user'];
                $password=$_POST['password'];
                $fileName=$_POST['fileName'];
                $Location=$_POST['Location'];
                }

            if(!empty($_POST['Location']) and ($_POST['fileName'])){
                $valor = $_POST['Location'];
                $valor2 = $_POST['fileName'];
                if(!empty($valor)){
                    $auxLF = "$Location\%U_$fileName.bak";
                }
            }

            function setTimeFormat()
            {
                exec("cd C:\wamp64\www\Oracle_Monitor_SGA_TableSpce\Frontend");
                exec("rman @rman.txt");
            }
            
            if(!empty($_POST['logFile'])){
                $myfile = fopen("rman.txt", "w+") or die("Unable to open file!");
                $txt = "connect target /\n connect catalog $user/$password\n";
                fwrite($myfile, $txt);
                $txt = "run{allocate channel CH1 device type DISK format '$auxLF'; backup database plus archivelog;}";
                fwrite($myfile, $txt);
                fclose($myfile);
                setTimeFormat();
                if(setTimeFormat())
                {
                    echo "ok";
                }
            }

            if(!empty($_POST['controlFile'])){
                $myfile = fopen("rman.txt", "w+") or die("Unable to open file!");
                $txt = "connect target /\n connect catalog $user/$password\n";
                fwrite($myfile, $txt);
                $txt = "run{allocate channel CH1 device type DISK format '$auxLF'; backup database include current controlfile;}";
                fwrite($myfile, $txt);
                fclose($myfile);
                setTimeFormat();
                if(setTimeFormat())
                {
                    echo "ok";
                }
            }

            if(!empty($_POST['controlFile'])and($_POST['logFile'])){
                $myfile = fopen("rman.txt", "w+") or die("Unable to open file!");
                $txt = "connect target /\n connect catalog $user/$password\n";
                fwrite($myfile, $txt);
                $txt = "run{allocate channel CH1 device type DISK format '$auxLF'; backup database include current controlfile plus archivelog;}";
                fwrite($myfile, $txt);
                fclose($myfile);
                setTimeFormat();
                if(setTimeFormat())
                {
                    echo "ok";
                }
            }
            ?>
        </div>
    </div>
</body>

</html>
