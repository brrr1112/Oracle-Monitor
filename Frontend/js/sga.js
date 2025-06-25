google.charts.load('current', { 'packages': ['corechart'] });
google.charts.setOnLoadCallback(initializeSgaPage);

// Global to store SGA Max Size for chart scaling
let sgaMaxSizeForChart;

function initializeSgaPage() {
    fetchSgaMaxSize(); // Fetch max size first, then draw chart and alerts
    // No automatic interval here, refresh should be triggered by a button or a global dashboard refresh if this becomes part of one.
    // For standalone, a refresh button on SGA.php would be better than intervals.
    // Or, if intervals are desired:
    // setInterval(fetchSgaDataAndDrawChart, 5000);
    // setInterval(fetchSgaAlerts, 5000); // Consider if sgastatus check is still needed or if getAlerts is enough
}

function escapeHtml(unsafe) {
    if (unsafe === null || typeof unsafe === 'undefined') return '';
    return String(unsafe)
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

function fetchSgaMaxSize() {
    // Check if the main content area is hidden due to no profile selected
    if ($('#curve_chart').length === 0 && $('.alert-warning').is(':visible')) {
        console.log("SGA Page: No profile selected, skipping data load.");
        return;
    }
    $.ajax({
        url: "../server/results.php?q=sgasize", // Relative URL
        dataType: 'json',
        success: function (response) {
            if (response.error && response.detail_from_oracle_php) {
                handleSgaError('SGA Max Size Error: ' + escapeHtml(response.error));
                return;
            }
            sgaMaxSizeForChart = response;
            // Once max size is fetched, fetch data and draw the chart & alerts
            fetchSgaDataAndDrawChart();
            fetchSgaAlerts(); // Assuming sgaalerts is the correct endpoint
        },
        error: function(xhr, status, error) {
            let errorDetail = "Error fetching SGA Max Size.";
            try { let errResp = JSON.parse(xhr.responseText); if(errResp && errResp.error) errorDetail = errResp.error; } catch(e){}
            handleSgaError(`SGA Max Size Error: ${escapeHtml(errorDetail)} (Status: ${escapeHtml(xhr.statusText)})`);
        }
    });
}

function fetchSgaDataAndDrawChart() {
    if ($('#curve_chart').length === 0 && $('.alert-warning').is(':visible')) return; // Double check

    $('#curve_chart').html('<div class="d-flex justify-content-center align-items-center" style="height:100%;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading SGA Chart...</span></div></div>');

    $.ajax({
        url: "../server/results.php?q=sga", // Relative URL
        dataType: 'json',
        success: function (response) {
            if (response.error && response.detail_from_oracle_php) {
                handleSgaError('SGA Chart Data Error: ' + escapeHtml(response.error));
                return;
            }
            if (Array.isArray(response) && response.length > 0) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Time');
                data.addColumn('number', 'Used Mb');
                data.addColumn('number', 'HWM'); // High Water Mark
                data.addRows(response);
                var options = {
                    title: 'SGA Usage Monitor',
                    curveType: 'function',
                    legend: { position: 'bottom' },
                    vAxis: {
                        title: 'MB',
                        viewWindow: { min: 0 }, // Ensure Y-axis starts at 0
                        // If sgaMaxSizeForChart is available and valid, use it for maxValue
                        // maxValue: sgaMaxSizeForChart ? parseFloat(sgaMaxSizeForChart) : undefined
                    },
                    hAxis: { title: 'Time' },
                    animation: { duration: 1000, easing: 'out' },
                    backgroundColor: '#f8f9fa', // Light background for chart area
                    colors: ['#007bff', '#6c757d'], // Bootstrap primary and secondary
                    series: { 1: { lineDashStyle: [10, 2] } }
                };
                if (sgaMaxSizeForChart && parseFloat(sgaMaxSizeForChart) > 0) {
                    options.vAxis.maxValue = parseFloat(sgaMaxSizeForChart);
                     // Ensure minValue is less than maxValue if data can be small
                    if (options.vAxis.maxValue < 1000 && response.every(row => parseFloat(row[1]) < 1000 && parseFloat(row[2]) < 1000)) {
                        // options.vAxis.minValue = undefined; // Let Google Charts decide if max is small
                    } else if (options.vAxis.maxValue > 1000) {
                         options.vAxis.minValue = Math.min(...response.map(r => parseFloat(r[1])), ...response.map(r => parseFloat(r[2]))) > 500 ? 500 : 0;
                    }
                }


                var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
                chart.draw(data, options);
            } else {
                 $('#curve_chart').html("<div class='alert alert-info text-center'>No SGA data available to display chart.</div>");
            }
        },
        error: function(xhr, status, error) {
            let errorDetail = "Error fetching SGA usage data.";
            try { let errResp = JSON.parse(xhr.responseText); if(errResp && errResp.error) errorDetail = errResp.error; } catch(e){}
            handleSgaError(`SGA Chart Data Error: ${escapeHtml(errorDetail)} (Status: ${escapeHtml(xhr.statusText)})`);
        }
    });
}

function fetchSgaAlerts() {
    if ($('#tablebody').length === 0 && $('.alert-warning').is(':visible')) return;

    $('#tablebody').html('<tr><td colspan="4" class="text-center">Loading alerts...</td></tr>');
    $.ajax({
        url: '../server/results.php?q=sgaalerts', // Relative URL
        dataType: 'json',
        success: function (response) {
            if (response.error && response.detail_from_oracle_php) {
                $('#tablebody').html(`<tr><td colspan="4" class="text-center alert alert-danger">Alerts Error: ${escapeHtml(response.error)}</td></tr>`);
                return;
            }
            const tableBody = $('#tablebody');
            tableBody.empty();
            if (Array.isArray(response) && response.length > 0) {
                response.forEach(function(rowArray) {
                    let rowHtml = "<tr>";
                    rowArray.forEach(function(cellData) {
                        rowHtml += "<td>" + escapeHtml(String(cellData)) + "</td>";
                    });
                    rowHtml += "</tr>";
                    tableBody.append(rowHtml);
                });
            } else {
                tableBody.html('<tr><td colspan="4" class="text-center alert alert-info">No current SGA alerts.</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            let errorDetail = "Error fetching SGA alerts.";
            try { let errResp = JSON.parse(xhr.responseText); if(errResp && errResp.error) errorDetail = errResp.error; } catch(e){}
            $('#tablebody').html(`<tr><td colspan="4" class="text-center alert alert-danger">Alerts Error: ${escapeHtml(errorDetail)} (Status: ${escapeHtml(xhr.statusText)})</td></tr>`);
        }
    });
}

function handleSgaError(errorMessage) {
    // Display error in both chart and table areas if they exist
    if ($('#curve_chart').length) {
        $('#curve_chart').html(`<div class="alert alert-danger text-center">${errorMessage} <br>Please <a href='menu.php'>select a DB profile</a> or <a href='manage_connections.php'>manage connections</a>.</div>`);
    }
    if ($('#tablebody').length) {
        $('#tablebody').html(`<tr><td colspan="4" class="text-center alert alert-danger">${errorMessage}</td></tr>`);
    }
    console.error(errorMessage);
}

// Consider removing the old alerts() and getAlerts() functions if fetchSgaAlerts replaces them.
// The old alerts() function also called results.php?q=sgastatus, which might be a separate check.
// For now, focusing on sgaalerts for the table.
// If sgastatus is still needed, it should be a separate AJAX call.

// Removing old setIntervals, new refresh mechanism should be on SGA.php if needed or global.
// setInterval(drawChart, 5000);
// setInterval(alerts, 5000);