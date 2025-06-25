<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Performance Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link rel="stylesheet" href="style/users.css"> <!-- Reusing base style, can be specialized -->
    <link rel="stylesheet" href="style/menu.css"> <!-- For .box class styling if used -->
    <script defer type="text/javascript" src="js/performance_dashboard.js"></script>
    <style>
        body { background-color: #2c3e50; color: #ecf0f1; } /* Dark theme base */
        .dashboard-container { padding: 20px; }
        .dashboard-section {
            background-color: #34495e; /* Slightly lighter card background */
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .dashboard-section h3 {
            color: #ecf0f1;
            margin-top: 0;
            border-bottom: 1px solid #4a627a;
            padding-bottom: 10px;
        }
        .chart-placeholder { width: 100%; min-height: 300px; display: flex; align-items: center; justify-content: center; }
        .ratio-value { font-size: 2em; font-weight: bold; color: #1abc9c; } /* Teal for good values */
        .call-rate-value { font-size: 1.5em; color: #e67e22; } /* Orange for rates */
        .control-bar select, .control-bar input, .control-bar button {
            margin-left: 10px;
            padding: 5px 8px;
            border-radius: 4px;
            border: 1px solid #555;
            background-color: #444;
            color: white;
        }
         .header .box1 { /* Copied from menu.css to ensure consistent header */
            background: #3e8e41;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Pushes title and logo to opposite ends */
            padding: 10px 20px; /* Add some padding */
        }
        .header .titu { /* Copied from menu.css */
            font-size: 2em;
            margin: 0; /* Remove default margins */
        }
        .header .logo { /* Copied from menu.css */
            height: 50px; /* Adjust as needed */
            width: auto;
        }
        .table-responsive { max-height: 400px; } /* For scrollable tables if they get too long */
        th { color: #bdc3c7; } /* Lighter grey for table headers */
    </style>
</head>
<body>
    <div class="row"> <!-- Header structure from other pages -->
        <div class="header">
            <div class="box1">
                <p class="titu">Performance Dashboard</p>
                <img class="logo" src="img/logo.png">
            </div>
        </div>
    </div>

    <div class="container-fluid dashboard-container">
        <div class="button" style="margin-bottom: 20px;">
            <a href="menu.html"><button class="boton">Back to Menu</button></a>
            <button id="refreshAllData" class="boton" style="margin-left: 10px;">Refresh All Data</button>
        </div>

        <div class="row">
            <!-- Key Ratios Section -->
            <div class="col-md-6">
                <div class="dashboard-section">
                    <h3>Key Performance Ratios</h3>
                    <div id="ratiosSection">
                        <div class="row">
                            <div class="col-sm-6 text-center">
                                <h5>Buffer Cache Hit</h5>
                                <div id="bufferCacheHitRatio" class="ratio-value">Loading...</div>
                                <div id="bufferCacheHitChart" style="width: 100%; height: 150px;"></div>
                            </div>
                            <div class="col-sm-6 text-center">
                                <h5>Library Cache Hit</h5>
                                <div id="libraryCacheHitRatio" class="ratio-value">Loading...</div>
                                <div id="libraryCacheHitChart" style="width: 100%; height: 150px;"></div>
                            </div>
                        </div>
                         <div class="row mt-3">
                            <div class="col-sm-6 text-center">
                                <h5>Dictionary Cache Hit</h5>
                                <div id="dictCacheHitRatio" class="ratio-value">Loading...</div>
                                <div id="dictCacheHitChart" style="width: 100%; height: 150px;"></div>
                            </div>
                            <div class="col-sm-6 text-center">
                                <h5>Latch Hit Ratio</h5>
                                <div id="latchHitRatio" class="ratio-value">Loading...</div>
                                <div id="latchHitChart" style="width: 100%; height: 150px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call Rates Section -->
            <div class="col-md-6">
                <div class="dashboard-section">
                    <h3>Call Rates (per second)</h3>
                    <div id="callRatesSection" class="text-center">
                        <p>User Calls/sec: <span id="userCallsPerSec" class="call-rate-value">Loading...</span></p>
                        <p>Commits/sec: <span id="userCommitsPerSec" class="call-rate-value">Loading...</span></p>
                        <p>Rollbacks/sec: <span id="userRollbacksPerSec" class="call-rate-value">Loading...</span></p>
                        <small>(Calculated based on changes since last refresh)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top N SQL Summary Section -->
            <div class="col-md-12">
                <div class="dashboard-section">
                    <h3>Top N SQL Summary
                        <small class="control-bar" style="display: inline; background: none; box-shadow: none; padding:0;">
                            <label for="topSqlMetricSelect">Metric:</label>
                            <select id="topSqlMetricSelect">
                                <option value="CPU_TIME" selected>CPU Time</option>
                                <option value="ELAPSED_TIME">Elapsed Time</option>
                                <option value="BUFFER_GETS">Buffer Gets</option>
                                <option value="DISK_READS">Disk Reads</option>
                                <option value="EXECUTIONS">Executions</option>
                            </select>
                            <label for="topNSqlInput">N:</label>
                            <input type="number" id="topNSqlInput" value="5" min="3" max="20">
                        </small>
                        <a href="top_sql.php" style="float:right; font-size:0.8em; color: #1abc9c;">View Full Page</a>
                    </h3>
                    <div id="topSqlChart" class="chart-placeholder">Loading chart...</div>
                    <!-- Optional: A small table here too, or just rely on the chart -->
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Sessions by Resource Section -->
            <div class="col-md-12">
                <div class="dashboard-section">
                    <h3>Top Sessions by Resource
                        <small class="control-bar" style="display: inline; background: none; box-shadow: none; padding:0;">
                            <label for="topSessionResourceMetricSelect">Resource:</label>
                            <select id="topSessionResourceMetricSelect">
                                <option value="CPU used by this session" selected>CPU Used</option>
                                <option value="session logical reads">Logical Reads</option>
                                <option value="physical reads">Physical Reads</option>
                                <option value="execute count">Execute Count</option>
                            </select>
                            <label for="topNSessionInput">N:</label>
                            <input type="number" id="topNSessionInput" value="5" min="3" max="20">
                        </small>
                         <a href="sessions.php" style="float:right; font-size:0.8em; color: #1abc9c;">View All Active Sessions</a>
                    </h3>
                    <div id="topSessionsChart" class="chart-placeholder">Loading chart...</div>
                    <div class="table-responsive mt-3">
                        <table id="topSessionsTable" class="table table-sm">
                            <thead><tr><th>SID</th><th>Username</th><th>Program</th><th>Metric Value</th></tr></thead>
                            <tbody id="topSessionsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
