<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Active Sessions Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link rel="stylesheet" href="style/users.css"> <!-- Reusing users.css for now -->
    <script defer type="text/javascript" src="js/sessions.js"></script>
    <style>
        .table-responsive {
            max-height: 70vh; /* Limit table height and make it scrollable if needed */
        }
        th, td {
            white-space: nowrap; /* Prevent text wrapping in cells */
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">Active Sessions Monitor</p>
                    <img class="logo" src="img/logo.png">
                </div>
            </div>
        </div>
        <div class="button">
            <a href="menu.html">
                <button class="boton">Back to Menu</button>
            </a>
            <button id="refreshSessions" class="boton" style="margin-left: 10px;">Refresh</button>
        </div>

        <h1 class="title">Active Oracle Sessions</h1>
        <br>
        <div class="table-responsive">
            <table id="sessionsTable" class="table table-striped table-hover" style="table-layout: auto;">
                <thead class="text-start">
                    <tr>
                        <th>SID</th>
                        <th>Serial#</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>OS User</th>
                        <th>Machine</th>
                        <th>Program</th>
                        <th>Logon Time</th>
                        <th>Last Call ET (s)</th>
                        <th>SQL ID</th>
                        <th>Prev SQL ID</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Client Info</th>
                        <th>Server PID</th>
                        <th>Resource Group</th>
                    </tr>
                </thead>
                <tbody id="sessionsTableBody">
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
