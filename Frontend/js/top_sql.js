$(document).ready(function () {
    loadTopSql(); // Initial load

    $('#refreshTopSql').on('click', function () {
        loadTopSql();
    });

    // Optional: auto-refresh data periodically
    // setInterval(loadTopSql, 30000); // e.g., every 30 seconds
});

function loadTopSql() {
    var metric = $('#metricSelect').val();
    var topN = $('#topNInput').val();

    // Basic validation for topN input
    if (parseInt(topN) < 1 || parseInt(topN) > 100) {
        alert("Top N must be between 1 and 100.");
        $('#topNInput').val(10); // Reset to default
        topN = 10;
    }

    $('#topSqlTableBody').html('<tr><td colspan="10" style="text-align:center;">Loading...</td></tr>');


    $.ajax({
        url: `../server/results.php?q=topnsql&metric=${metric}&top_n=${topN}`,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            populateTopSqlTable(data);
        },
        error: function (xhr, status, error) {
            console.error("Error loading Top N SQL: " + status + " " + error);
            var errorMsg = `<tr><td colspan="10" style="text-align:center; color:red;">Error loading Top N SQL. Status: ${status}, Error: ${error}. Check console.</td></tr>`;
            if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.error) errorMsg = `<tr><td colspan="10" style="text-align:center; color:red;">${response.error}</td></tr>`;
                } catch(e) {
                     // If responseText is not JSON or doesn't have error property
                    errorMsg = `<tr><td colspan="10" style="text-align:center; color:red;">Error loading Top N SQL. Raw response: ${xhr.responseText.substring(0,100)}...</td></tr>`;
                }
            }
            $('#topSqlTableBody').html(errorMsg);
        }
    });
}

function populateTopSqlTable(sqlStatements) {
    var tableBody = $('#topSqlTableBody');
    tableBody.empty();

    if (sqlStatements && sqlStatements.length > 0) {
        sqlStatements.forEach(function (stmt) {
            var snippet = stmt.SQL_TEXT_SNIPPET || '';
            // Basic HTML escaping for the snippet to prevent XSS if SQL text contains HTML-like characters.
            var escapedSnippet = $('<div>').text(snippet).html();

            var row = '<tr>' +
                '<td>' + (stmt.SQL_ID || '') + '</td>' +
                '<td class="sql-text-snippet" title="' + escapedSnippet + '">' + escapedSnippet + '</td>' +
                '<td>' + (stmt.PARSING_USERNAME || stmt.PARSING_USER_ID || '') + '</td>' +
                '<td>' + (stmt.EXECUTIONS !== null ? stmt.EXECUTIONS : '') + '</td>' +
                '<td>' + (stmt.CPU_TIME_SEC !== null ? stmt.CPU_TIME_SEC : '') + '</td>' +
                '<td>' + (stmt.ELAPSED_TIME_SEC !== null ? stmt.ELAPSED_TIME_SEC : '') + '</td>' +
                '<td>' + (stmt.BUFFER_GETS !== null ? stmt.BUFFER_GETS : '') + '</td>' +
                '<td>' + (stmt.DISK_READS !== null ? stmt.DISK_READS : '') + '</td>' +
                '<td>' + (stmt.PLAN_HASH_VALUE || '') + '</td>' +
                '<td>' + (stmt.LAST_ACTIVE_TIME || '') + '</td>' +
                '</tr>';
            tableBody.append(row);
        });
    } else {
        tableBody.append('<tr><td colspan="10" style="text-align:center;">No SQL statements found for the selected criteria.</td></tr>');
    }
}
