<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Top N SQL Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link rel="stylesheet" href="style/users.css"> <!-- Reusing users.css for now, can customize later -->
    <script defer type="text/javascript" src="js/top_sql.js"></script>
    <style>
        .control-bar {
            padding: 15px;
            background-color: #333;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .control-bar label {
            margin-right: 5px;
        }
        .control-bar select, .control-bar input {
            margin-right: 15px;
            padding: 5px;
            border-radius: 3px;
            border: 1px solid #555;
            background-color: #444;
            color: white;
        }
        .sql-text-snippet {
            max-width: 400px; /* Adjust as needed */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer; /* Indicate it's expandable or has full text on hover/click */
        }
    </style>
</head>

<body>
    <div class="container-fluid"> <!-- Using container-fluid for wider tables -->
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">Top N SQL Monitor</p>
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
            <label for="metricSelect">Order By:</label>
            <select id="metricSelect">
                <option value="CPU_TIME" selected>CPU Time</option>
                <option value="ELAPSED_TIME">Elapsed Time</option>
                <option value="BUFFER_GETS">Buffer Gets</option>
                <option value="DISK_READS">Disk Reads</option>
                <option value="EXECUTIONS">Executions</option>
            </select>

            <label for="topNInput">Top N:</label>
            <input type="number" id="topNInput" value="10" min="1" max="100">

            <button id="refreshTopSql" class="boton" style="padding: 5px 10px; font-size: 0.9em;">Refresh</button>
        </div>

        <h1 class="title">Top SQL Statements</h1>
        <br>
        <div class="table-responsive">
            <table id="topSqlTable" class="table" style="table-layout: auto;"> <!-- auto layout for better column sizing -->
                <thead class="text-start">
                    <th>SQL ID</th>
                    <th>SQL Text Snippet</th>
                    <th>Parsing User</th>
                    <th>Executions</th>
                    <th>CPU Time (s)</th>
                    <th>Elapsed Time (s)</th>
                    <th>Buffer Gets</th>
                    <th>Disk Reads</th>
                    <th>Plan Hash Value</th>
                    <th>Last Active Time</th>
                </thead>
                <tbody id="topSqlTableBody">
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
