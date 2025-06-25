google.charts.load('current', {'packages':['corechart', 'gauge', 'bar']});
google.charts.setOnLoadCallback(initializeDashboard);

let callRateData = {
    previous: null,
    lastTimestamp: null
};

function initializeDashboard() {
    // Initial data loads
    loadPerformanceRatios();
    loadCallRates();
    loadTopSqlSummary();
    loadTopSessionsByResource();

    // Setup event listeners for controls
    $('#refreshAllData').on('click', function() {
        // Reset call rate data for fresh calculation on manual refresh
        callRateData.previous = null;
        callRateData.lastTimestamp = null;

        loadPerformanceRatios();
        loadCallRates();
        loadTopSqlSummary();
        loadTopSessionsByResource();
    });

    $('#topSqlMetricSelect, #topNSqlInput').on('change', loadTopSqlSummary);
    $('#topSessionResourceMetricSelect, #topNSessionInput').on('change', loadTopSessionsByResource);

    // Setup periodic refresh
    setInterval(function() {
        loadPerformanceRatios();
        loadCallRates();
        loadTopSqlSummary();
        loadTopSessionsByResource();
    }, 30000); // Refresh every 30 seconds
}

// 1. Performance Ratios
function loadPerformanceRatios() {
    $.ajax({
        url: '../server/results.php?q=performanceratios',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0 && data[0].RATIO_NAME !== 'Error') {
                updateRatiosDisplay(data);
            } else {
                $('#ratiosSection').find('.ratio-value').text('Error');
                $('#ratiosSection').find('[id$=Chart]').empty().text('Error loading data.');
                 console.error("Error loading performance ratios:", data.length > 0 ? data[0].ERROR_MSG : "No data");
            }
        },
        error: function(xhr, status, error) {
            $('#ratiosSection').find('.ratio-value').text('N/A');
            $('#ratiosSection').find('[id$=Chart]').empty().text('Error loading data.');
            console.error("AJAX Error loading performance ratios:", status, error);
        }
    });
}

function updateRatiosDisplay(ratios) {
    ratios.forEach(function(ratio) {
        let chartDivId = '';
        let textDivId = '';
        switch(ratio.RATIO_NAME) {
            case 'Buffer Cache Hit Ratio':
                textDivId = '#bufferCacheHitRatio';
                chartDivId = 'bufferCacheHitChart';
                break;
            case 'Library Cache Hit Ratio':
                textDivId = '#libraryCacheHitRatio';
                chartDivId = 'libraryCacheHitChart';
                break;
            case 'Dictionary Cache Hit Ratio':
                textDivId = '#dictCacheHitRatio';
                chartDivId = 'dictCacheHitChart';
                break;
            case 'Latch Hit Ratio':
                 textDivId = '#latchHitRatio';
                 chartDivId = 'latchHitChart';
                 break;
        }
        if (textDivId) $(textDivId).text(parseFloat(ratio.RATIO_VALUE).toFixed(2) + '%');
        if (chartDivId) drawGaugeChart(chartDivId, ratio.RATIO_NAME, parseFloat(ratio.RATIO_VALUE));
    });
}

function drawGaugeChart(elementId, label, value) {
    var data = google.visualization.arrayToDataTable([
        ['Label', 'Value'],
        [label.replace(' Ratio',''), value]
    ]);
    var options = {
        width: '100%', height: 150,
        redFrom: 0, redTo: 85,
        yellowFrom: 85, yellowTo: 95,
        greenFrom: 95, greenTo: 100,
        minorTicks: 5,
        animation:{ duration: 500, easing: 'inAndOut' }
    };
    var chart = new google.visualization.Gauge(document.getElementById(elementId));
    chart.draw(data, options);
}


// 2. Call Rates
function loadCallRates() {
     $.ajax({
        url: '../server/results.php?q=callrates',
        type: 'GET',
        dataType: 'json',
        success: function(currentData) {
            if (currentData && currentData.USER_CALLS_CUMULATIVE !== -1) {
                updateCallRatesDisplay(currentData);
            } else {
                $('#userCallsPerSec, #userCommitsPerSec, #userRollbacksPerSec').text('Error');
                console.error("Error loading call rates:", currentData.ERROR_MSG);
            }
        },
        error: function(xhr, status, error) {
            $('#userCallsPerSec, #userCommitsPerSec, #userRollbacksPerSec').text('N/A');
            console.error("AJAX Error loading call rates:", status, error);
        }
    });
}

function updateCallRatesDisplay(current) {
    if (callRateData.previous && callRateData.lastTimestamp) {
        // Parse Oracle timestamp (YYYY-MM-DD HH24:MI:SS.FF)
        // Example: "2023-10-27 10:00:00.123456"
        // Need to handle potential timezone differences if any, but SYSTIMESTAMP usually in DB server's TZ.
        // For JS Date.parse, a more standard format might be needed or manual parsing.
        // Oracle format "YYYY-MM-DD HH:MI:SS.FF TZR" or similar is more robust.
        // For simplicity, assuming PL/SQL returns a string that JS Date can somewhat parse or we convert.
        // Let's assume CURRENT_DB_TIME is a string like "YYYY-MM-DD/HH24:MI:SS.FF" (as per fun_get_top_sql)
        // or directly from SYSTIMESTAMP which might be "DD-MON-RR HH.MI.SS.FF AM/PM" depending on NLS.
        // The fun_get_call_rates returns SYSTIMESTAMP. Let's format it in PLSQL or convert here.
        // For now, assuming it is a string that can be parsed or converted.
        // A robust way: send as epoch or ISO 8601 string from PL/SQL.
        // PHP side: $row['CURRENT_DB_TIME']->load() if it's an OCI-Lob for timestamp.
        // The PL/SQL for fun_get_call_rates returns SYSTIMESTAMP, which oci_fetch_array might format.
        // Let's assume it's a string like "26-OCT-23 12.34.56.789012 PM" or similar.
        // This is tricky. For now, best effort parsing.

        let currentTime;
        // Attempt to parse the Oracle timestamp string. This is fragile.
        // A better way is to have PL/SQL format it as TO_CHAR(SYSTIMESTAMP, 'YYYY-MM-DD"T"HH24:MI:SS.FF6"Z"') for UTC
        // or TO_CHAR(SYSTIMESTAMP, 'YYYY-MM-DD HH24:MI:SS') and assume server time.
        // For now, let's try to make the date object. The format from oci_fetch_array for SYSTIMESTAMP is often 'DD-MON-YY HH.MI.SS.FF AM/PM'
        // This needs to be handled carefully.
        // A quick fix if PHP returns it as a string from Oracle:
        // current.CURRENT_DB_TIME might be like "27-OCT-23 10.53.11.567890 AM"
        // This is hard to parse directly.
        // *Self-correction*: The PL/SQL SYSTIMESTAMP will be fetched by PHP. PHP oci_fetch_array might convert it to a string.
        // The safest is to get epoch time in PL/SQL: (SYSTIMESTAMP - TIMESTAMP '1970-01-01 00:00:00 UTC') * 24 * 60 * 60
        // Since we don't have that now, let's assume the string is somewhat parsable or we just use client time diff.
        // For a quick demo, using client-side time for delta_seconds can work if refresh interval is fixed and short.

        let currentTimestamp = new Date().getTime(); // Using client time for simplicity of delta calculation
        let deltaSeconds = (currentTimestamp - callRateData.lastTimestamp) / 1000;

        if (deltaSeconds > 0) {
            let callsPerSec = ((current.USER_CALLS_CUMULATIVE - callRateData.previous.USER_CALLS_CUMULATIVE) / deltaSeconds).toFixed(2);
            let commitsPerSec = ((current.USER_COMMITS_CUMULATIVE - callRateData.previous.USER_COMMITS_CUMULATIVE) / deltaSeconds).toFixed(2);
            let rollbacksPerSec = ((current.USER_ROLLBACKS_CUMULATIVE - callRateData.previous.USER_ROLLBACKS_CUMULATIVE) / deltaSeconds).toFixed(2);

            $('#userCallsPerSec').text(callsPerSec);
            $('#userCommitsPerSec').text(commitsPerSec);
            $('#userRollbacksPerSec').text(rollbacksPerSec);
        } else {
             $('#userCallsPerSec').text('...');
             $('#userCommitsPerSec').text('...');
             $('#userRollbacksPerSec').text('...');
        }
    } else {
        // First data load, just display cumulative or 'Calculating...'
        $('#userCallsPerSec').text('Calculating...');
        $('#userCommitsPerSec').text('Calculating...');
        $('#userRollbacksPerSec').text('Calculating...');
    }
    callRateData.previous = current;
    callRateData.lastTimestamp = new Date().getTime(); // Using client time
}


// 3. Top N SQL Summary
function loadTopSqlSummary() {
    var metric = $('#topSqlMetricSelect').val();
    var topN = $('#topNSqlInput').val();

    $('#topSqlChart').html('<p style="text-align:center;">Loading Top SQL chart...</p>');

    $.ajax({
        url: `../server/results.php?q=topnsql&metric=${metric}&top_n=${topN}`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                drawTopSqlChart(data, metric);
            } else {
                 $('#topSqlChart').html('<p style="text-align:center;">No Top SQL data found.</p>');
            }
        },
        error: function(xhr, status, error) {
            $('#topSqlChart').html('<p style="text-align:center; color:red;">Error loading Top SQL data.</p>');
            console.error("AJAX Error loading Top SQL summary:", status, error);
        }
    });
}

function drawTopSqlChart(sqlData, metric) {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'SQL ID');

    let metricLabel = metric;
    if (metric === 'CPU_TIME') metricLabel = 'CPU Time (s)';
    if (metric === 'ELAPSED_TIME') metricLabel = 'Elapsed Time (s)';

    data.addColumn('number', metricLabel);

    sqlData.forEach(function(sql) {
        let value;
        switch(metric) {
            case 'CPU_TIME': value = parseFloat(sql.CPU_TIME_SEC); break;
            case 'ELAPSED_TIME': value = parseFloat(sql.ELAPSED_TIME_SEC); break;
            case 'BUFFER_GETS': value = parseInt(sql.BUFFER_GETS); break;
            case 'DISK_READS': value = parseInt(sql.DISK_READS); break;
            case 'EXECUTIONS': value = parseInt(sql.EXECUTIONS); break;
            default: value = 0;
        }
        data.addRow([sql.SQL_ID, value]);
    });

    var options = {
        title: `Top SQL by ${metricLabel}`,
        chartArea: {width: '60%', height: '70%'},
        hAxis: { title: metricLabel, minValue: 0 },
        vAxis: { title: 'SQL ID', textStyle: {fontSize: 10} },
        legend: { position: 'none' },
        bars: 'horizontal',
        height: 300 + (sqlData.length * 20), // Dynamic height
        colors: ['#e67e22']
    };
    var chart = new google.visualization.BarChart(document.getElementById('topSqlChart'));
    chart.draw(data, options);
}


// 4. Top Sessions by Resource
function loadTopSessionsByResource() {
    var resourceMetric = $('#topSessionResourceMetricSelect').val();
    var topN = $('#topNSessionInput').val();

    $('#topSessionsChart').html('<p style="text-align:center;">Loading Top Sessions chart...</p>');
    $('#topSessionsTableBody').html('<tr><td colspan="4" style="text-align:center;">Loading...</td></tr>');

    $.ajax({
        url: `../server/results.php?q=topsessions_resource&resource_metric=${encodeURIComponent(resourceMetric)}&top_n=${topN}`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
             if (data && data.length > 0 && data[0].SID !== -1) {
                drawTopSessionsChart(data, resourceMetric);
                populateTopSessionsTable(data);
            } else {
                 $('#topSessionsChart').html('<p style="text-align:center;">No Top Sessions data found.</p>');
                 $('#topSessionsTableBody').html('<tr><td colspan="4" style="text-align:center;">No data.</td></tr>');
                 if(data && data.length > 0 && data[0].SID === -1) console.error("Error from DB for Top Sessions:", data[0].ERROR_MSG);
            }
        },
        error: function(xhr, status, error) {
            $('#topSessionsChart').html('<p style="text-align:center; color:red;">Error loading Top Sessions data.</p>');
            $('#topSessionsTableBody').html('<tr><td colspan="4" style="text-align:center; color:red;">Error.</td></tr>');
            console.error("AJAX Error loading Top Sessions by resource:", status, error);
        }
    });
}

function drawTopSessionsChart(sessionData, resourceMetric) {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Session (SID)');
    data.addColumn('number', resourceMetric);

    sessionData.forEach(function(session) {
        data.addRow([`SID: ${session.SID} (${session.USERNAME || 'N/A'})`, parseInt(session.METRIC_VALUE)]);
    });

    var options = {
        title: `Top Sessions by ${resourceMetric}`,
        chartArea: {width: '60%', height: '70%'},
        hAxis: { title: 'Metric Value', minValue: 0 },
        vAxis: { title: 'Session', textStyle: {fontSize: 10} },
        legend: { position: 'none' },
        bars: 'horizontal',
        height: 300 + (sessionData.length * 25), // Dynamic height
        colors: ['#2980b9']
    };
    var chart = new google.visualization.BarChart(document.getElementById('topSessionsChart'));
    chart.draw(data, options);
}

function populateTopSessionsTable(sessionData) {
    var tableBody = $('#topSessionsTableBody');
    tableBody.empty();
    if (sessionData && sessionData.length > 0) {
        sessionData.forEach(function(s) {
            tableBody.append(`<tr><td>${s.SID}</td><td>${s.USERNAME || ''}</td><td>${s.PROGRAM || ''}</td><td>${s.METRIC_VALUE}</td></tr>`);
        });
    } else {
        tableBody.append('<tr><td colspan="4" style="text-align:center;">No session data.</td></tr>');
    }
}
