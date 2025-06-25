google.charts.load('current', {'packages':['corechart', 'bar']});
// google.setOnLoadCallback(loadSystemWaits); // Delay initial load until chart library is ready - or call from document.ready

$(document).ready(function () {
    // Ensure charts are loaded before first call if making one here
    // For simplicity, we'll call it directly and chart drawing will handle readiness
    loadSystemWaits();

    $('#refreshWaits').on('click', function () {
        loadSystemWaits();
    });
});

function loadSystemWaits() {
    var topN = $('#topNWaitsInput').val();

    if (parseInt(topN) < 1 || parseInt(topN) > 50) {
        alert("Top N must be between 1 and 50.");
        $('#topNWaitsInput').val(10); // Reset to default
        topN = 10;
    }

    $('#waitsTableBody').html('<tr><td colspan="6" style="text-align:center;">Loading system waits...</td></tr>');
    // Clear previous chart
    $('#waitsChart').empty().html('<p style="text-align:center; padding-top:50px;">Loading chart data...</p>');


    $.ajax({
        url: `../server/results.php?q=systemwaits&top_n=${topN}`,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            populateWaitsTable(data);
            drawWaitsChart(data);
        },
        error: function (xhr, status, error) {
            console.error("Error loading system waits: ", status, error, xhr.responseText);
             var errorMsg = `<tr><td colspan="6" style="text-align:center; color:red;">Error loading system waits. Status: ${status}, Error: ${error}. Check console.</td></tr>`;
            if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.error) errorMsg = `<tr><td colspan="6" style="text-align:center; color:red;">${response.error}</td></tr>`;
                } catch(e) {
                     errorMsg = `<tr><td colspan="6" style="text-align:center; color:red;">Error loading system waits. Raw response: ${xhr.responseText.substring(0,200)}...</td></tr>`;
                }
            }
            $('#waitsTableBody').html(errorMsg);
            $('#waitsChart').html('<p style="text-align:center; color:red;">Could not load chart data.</p>');
        }
    });
}

function populateWaitsTable(waits) {
    var tableBody = $('#waitsTableBody');
    tableBody.empty();

    if (waits && waits.length > 0) {
        waits.forEach(function (wait) {
            var row = '<tr>' +
                '<td>' + (wait.EVENT || '') + '</td>' +
                '<td>' + (wait.WAIT_CLASS || '') + '</td>' +
                '<td>' + (wait.TOTAL_WAITS !== null ? wait.TOTAL_WAITS : '') + '</td>' +
                '<td>' + (wait.TOTAL_TIMEOUTS !== null ? wait.TOTAL_TIMEOUTS : '') + '</td>' +
                '<td>' + (wait.TIME_WAITED_SECONDS !== null ? wait.TIME_WAITED_SECONDS : '') + '</td>' +
                '<td>' + (wait.AVERAGE_WAIT_SECONDS !== null ? wait.AVERAGE_WAIT_SECONDS : '') + '</td>' +
                '</tr>';
            tableBody.append(row);
        });
    } else {
        tableBody.append('<tr><td colspan="6" style="text-align:center;">No system wait events found (excluding idle) or data not in expected format.</td></tr>');
    }
}

function drawWaitsChart(waitsData) {
    if (!waitsData || waitsData.length === 0) {
        $('#waitsChart').html('<p style="text-align:center;">No data available to display chart.</p>');
        return;
    }

    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Wait Event');
    data.addColumn('number', 'Time Waited (Seconds)');

    waitsData.forEach(function(wait) {
        data.addRow([wait.EVENT, parseFloat(wait.TIME_WAITED_SECONDS)]);
    });

    var options = {
        title: 'Top System Wait Events by Time Waited',
        chartArea: {width: '60%', height: '70%'},
        hAxis: {
          title: 'Time Waited (Seconds)',
          minValue: 0
        },
        vAxis: {
          title: 'Wait Event',
          textStyle: { fontSize: 10 }
        },
        legend: { position: 'none' },
        bars: 'horizontal', // Horizontal bar chart
        colors: ['#3366cc'] // Example color
    };

    var chartContainer = document.getElementById('waitsChart');
    // Clear previous chart content explicitly before drawing a new one
    $(chartContainer).empty();

    var chart = new google.visualization.BarChart(chartContainer);
    chart.draw(data, options);
}
