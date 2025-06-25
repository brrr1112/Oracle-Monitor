<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>System Wait Events</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script> <!-- For potential charts -->
    <link rel="stylesheet" href="style/users.css"> <!-- Reusing users.css for now -->
    <script defer type="text/javascript" src="js/waits.js"></script>
    <style>
        .control-bar {
            padding: 10px;
            background-color: #333;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .control-bar label {
            margin-right: 5px;
        }
        .control-bar input {
            margin-right: 15px;
            padding: 5px;
            border-radius: 3px;
            border: 1px solid #555;
            background-color: #444;
            color: white;
            width: 80px;
        }
        #waitsChart {
            width: 100%;
            height: 400px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">System Wait Events</p>
                    <img class="logo" src="img/logo.png">
                </div>
            </div>
        </div>
        <div class="button">
            <a href="menu.html">
                <button class="boton">Back to Menu</button>
            </a>
        </div>

        <div class="control-bar">
            <label for="topNWaitsInput">Top N:</label>
            <input type="number" id="topNWaitsInput" value="10" min="1" max="50">
            <button id="refreshWaits" class="boton" style="padding: 5px 10px; font-size: 0.9em;">Refresh</button>
        </div>

        <h1 class="title">Top System Wait Events (Non-Idle)</h1>
        <br>
        <div class="table-responsive">
            <table id="waitsTable" class="table table-striped table-hover" style="table-layout: auto;">
                <thead class="text-start">
                    <tr>
                        <th>Event Name</th>
                        <th>Wait Class</th>
                        <th>Total Waits</th>
                        <th>Total Timeouts</th>
                        <th>Time Waited (s)</th>
                        <th>Average Wait (s)</th>
                    </tr>
                </thead>
                <tbody id="waitsTableBody">
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div id="timeWaitedChart" style="width: 100%; height: 400px; margin-top: 20px;"></div>
            </div>
            <div class="col-md-6">
                <div id="totalWaitsChart" style="width: 100%; height: 400px; margin-top: 20px;"></div>
            </div>
        </div>
    </div>
</body>
</html>
