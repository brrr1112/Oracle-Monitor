$(document).ready(function () {
    loadRmanJobs();

    // Optional: Set an interval to refresh the RMAN jobs table periodically
    // setInterval(loadRmanJobs, 60000); // Refresh every 60 seconds
});

function loadRmanJobs() {
    $.ajax({
        url: '../server/results.php?q=rmanjobs',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            populateRmanJobsTable(data);
        },
        error: function (xhr, status, error) {
            console.error("Error loading RMAN jobs: " + status + " " + error);
            $('#rmanJobsTableBody').html('<tr><td colspan="9" style="text-align:center; color:red;">Error loading RMAN jobs. Check console for details.</td></tr>');
        }
    });
}

function populateRmanJobsTable(jobs) {
    var tableBody = $('#rmanJobsTableBody');
    tableBody.empty(); // Clear existing rows

    if (jobs && jobs.length > 0) {
        jobs.forEach(function (job) {
            var row = '<tr>' +
                '<td>' + (job.SESSION_KEY || '') + '</td>' +
                '<td>' + (job.INPUT_TYPE || '') + '</td>' +
                '<td>' + (job.STATUS || '') + '</td>' +
                '<td>' + (job.START_TIME_STR || '') + '</td>' +
                '<td>' + (job.END_TIME_STR || '') + '</td>' +
                '<td>' + (job.INPUT_BYTES_DISPLAY || '') + '</td>' +
                '<td>' + (job.OUTPUT_BYTES_DISPLAY || '') + '</td>' +
                '<td>' + (job.ELAPSED_SECONDS || '') + '</td>' +
                '<td>' + (job.COMPRESSION_RATIO || '') + '</td>' +
                '</tr>';
            tableBody.append(row);
        });
    } else {
        tableBody.append('<tr><td colspan="9" style="text-align:center;">No RMAN backup jobs found or data is not in expected format.</td></tr>');
    }
}
