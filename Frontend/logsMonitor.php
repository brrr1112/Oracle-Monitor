<?php

$opts =  array('http' => array('header'=> 'Cookie: ' . $_SERVER['HTTP_COOKIE']."\r\n"));

$context = stream_context_create($opts);

$jsonlogs = file_get_contents('http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=logsinfo', false, $context);
$jsonmode = file_get_contents("http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=logmode", false, $context);
$jsonmin = file_get_contents("http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=logsswitch", false, $context);
$logsinfo = json_decode($jsonlogs);
//$logsinfo = null;
$mode = json_decode($jsonmode);
$switchminutes = json_decode($jsonmin);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>LOG Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style/users.css">

</head>

<body>
    <div class="container">
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">Logs Status Monitor</p>
                    <img class="logo" src="img/logo.png">
                </div>
            </div>
        </div>

        <h1 class="title">Logs</h1>
        <br>

        <br>
        <div class="table-responsive">
            <table class="table">
                <thead cGROUPtext-muted">
                    <thead class="text-center">
                        <th>GROUP</th>
                        <th>MEMBERS</th>
                        <th>STATUS</th>
                    </thead>
                <tbody>
                    <?php 
                    if($logsinfo != null) {foreach ($logsinfo as $row) { ?>
                        <tr class="fila text-center">
                            <div class="log">
                                <?php foreach ($row as $data) { ?>
                                    <td><?php
                                        if ($data == "INACTIVE" || $data == "ACTIVE") { ?>
                                            <img class="log" src="img/zzzlog.png" title="<?php echo $data." Log"?>">
                                        <?php
                                        } elseif ($data == "CURRENT") { ?>
                                            <img class="log" src="img/currentLog.png" title="<?php echo $data." Log"?>">
                                        <?php
                                        } else {
                                            echo $data;
                                        } ?>
                                    </td>
                                <?php } ?>
                            </div>
                        <tr>
                        <?php } }?>
                </tbody>
            </table>
        </div>

        <br>
        <div class="table-responsive">
            <table class="table">
                <thead class="text-start">
                    <th>Switch Log (minutes) </th>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $switchminutes; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <br>
        <div class="table-responsive">
            <table class="table">
                <thead class="text-start">
                    <th>Log Mode</th>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $mode; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="button">
            <a href="menu.html">
                <button class="boton">Back to Menu</button>
            </a>
        </div>
    </div>
</body>

</html>