$(document).ready(function () {
    loadActiveSessions(); // Initial load

    $('#refreshSessions').on('click', function () {
        loadActiveSessions();
    });

    // Optional: auto-refresh data periodically
    // setInterval(loadActiveSessions, 15000); // e.g., every 15 seconds
});

function loadActiveSessions() {
    $('#sessionsTableBody').html('<tr><td colspan="16" style="text-align:center;">Loading active sessions...</td></tr>');

    $.ajax({
        url: '../server/results.php?q=activesessions',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            populateSessionsTable(data);
        },
        error: function (xhr, status, error) {
            console.error("Error loading active sessions: ", status, error, xhr.responseText);
            var errorMsg = `<tr><td colspan="16" style="text-align:center; color:red;">Error loading active sessions. Status: ${status}, Error: ${error}. Check console.</td></tr>`;
            if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.error) errorMsg = `<tr><td colspan="16" style="text-align:center; color:red;">${response.error}</td></tr>`;
                } catch(e) {
                    // If responseText is not JSON or doesn't have error property
                     errorMsg = `<tr><td colspan="16" style="text-align:center; color:red;">Error loading active sessions. Raw response: ${xhr.responseText.substring(0,200)}...</td></tr>`;
                }
            }
            $('#sessionsTableBody').html(errorMsg);
        }
    });
}

function populateSessionsTable(sessions) {
    var tableBody = $('#sessionsTableBody');
    tableBody.empty();

    if (sessions && sessions.length > 0) {
        sessions.forEach(function (session) {
            var row = '<tr>' +
                '<td>' + (session.SID || '') + '</td>' +
                '<td>' + (session.SERIAL || '') + '</td>' + // SERIAL# from Oracle becomes SERIAL in OCI fetch
                '<td>' + (session.USERNAME || '') + '</td>' +
                '<td>' + (session.STATUS || '') + '</td>' +
                '<td>' + (session.OSUSER || '') + '</td>' +
                '<td>' + (session.MACHINE || '') + '</td>' +
                '<td>' + (session.PROGRAM || '') + '</td>' +
                '<td>' + (session.LOGON_TIME_STR || '') + '</td>' +
                '<td>' + (session.LAST_CALL_ET !== null ? session.LAST_CALL_ET : '') + '</td>' +
                '<td>' + (session.SQL_ID || '') + '</td>' +
                '<td>' + (session.PREV_SQL_ID || '') + '</td>' +
                '<td>' + (session.MODULE || '') + '</td>' +
                '<td>' + (session.ACTION || '') + '</td>' +
                '<td>' + (session.CLIENT_INFO || '') + '</td>' +
                '<td>' + (session.SERVER_PROCESS_ID || '') + '</td>' +
                '<td>' + (session.RESOURCE_CONSUMER_GROUP || '') + '</td>' +
                '</tr>';
            tableBody.append(row);
        });
    } else {
        tableBody.append('<tr><td colspan="16" style="text-align:center;">No active sessions found or data is not in the expected format.</td></tr>');
    }
}
