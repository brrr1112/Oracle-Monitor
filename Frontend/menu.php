<?php
session_start();
if (!isset($_SESSION['app_user_id'])) {
    header('Location: index.html?message=' . urlencode("Please login to access the menu.") . "&type=error");
    exit();
}
$app_username = $_SESSION['app_username'] ?? 'User';
$selected_oracle_conn_id = $_SESSION['selected_oracle_conn_id'] ?? null;
$selected_oracle_profile_name = $_SESSION['selected_oracle_profile_name'] ?? 'None Selected';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Oracle Monitor</title>
    <link rel="stylesheet" href="style/menu.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <style>
        .connection-selector-bar {
            background-color: #2c3e50; /* Darker shade */
            padding: 10px 20px;
            color: #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #3498db;
        }
        .connection-selector-bar label {
            margin-right: 10px;
            font-weight: bold;
        }
        .connection-selector-bar select {
            padding: 5px 8px;
            border-radius: 4px;
            border: 1px solid #4a627a;
            background-color: #34495e;
            color: #ecf0f1;
            min-width: 200px;
        }
        .connection-selector-bar .current-selection {
            font-style: italic;
        }
        .manage-connections-link a {
            color: #1abc9c;
            text-decoration: none;
            font-weight: bold;
        }
        .manage-connections-link a:hover {
            text-decoration: underline;
        }
         #dbSelectMessage { margin-left: 15px; font-style: italic; }
    </style>
</head>
<body>
    <div class="row">
        <div class="header">
            <div class="box1">
                <p class="titu">Oracle Monitor Menu</p>
                <div style="display: flex; align-items: center;">
                    <span style="color:white; margin-right:15px;">User: <?php echo htmlspecialchars($app_username); ?></span>
                    <a href="../server/logout_handler.php" style="margin-left: 20px; color: white; text-decoration: none; background-color: #c0392b; padding: 8px 15px; border-radius: 4px;">Logout</a>
                    <img class="logo" src="img/logo.png" style="margin-left: 20px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="connection-selector-bar">
        <div>
            <label for="oracleConnectionSelect">Active Oracle DB:</label>
            <select id="oracleConnectionSelect">
                <option value="">-- Select a Connection --</option>
                <!-- Options will be populated by JavaScript -->
            </select>
            <span id="dbSelectMessage" style="display:none;"></span>
        </div>
        <div class="current-selection">
            Currently Monitoring: <strong id="currentOracleProfileName"><?php echo htmlspecialchars($selected_oracle_profile_name); ?></strong>
             | <span class="manage-connections-link"><a href="manage_connections.php">Manage Connections</a></span>
        </div>
    </div>

    <div class="row" style="padding-top: 20px;">
      <div class="column">
        <div class="box">
          <h2 class="titulo">User Monitor</h2>
          <img class="imagen2" src="img/usuario.png" />
          <div class="button">
            <a href="userSQL.php"> <!-- These pages will need auth check and selected DB context -->
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">SGA Size Monitor</h2>
          <img class="imagen2" src="img/grafica.png" />
          <div class="button">
            <a href="SGA.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">Table Space</h2>
          <img class="imagen2" src="img/tabla.png" />
          <div class="button">
            <a href="tablespace.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">Logs Monitor</h2>
          <img class="imagen2" src="img/log.png" />
          <div class="button">
            <a href="logsMonitor.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">RMAN</h2>
          <img class="imagen2" src="img/rman.png" />
          <div class="button">
            <a href="rman.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">Top N SQL</h2>
          <img class="imagen2" src="img/graphic.png" /> <!-- Reusing an existing image, can be updated -->
          <div class="button">
            <a href="top_sql.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">Active Sessions</h2>
          <img class="imagen2" src="img/user.png" /> <!-- Reusing an existing image -->
          <div class="button">
            <a href="sessions.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">System Waits</h2>
          <img class="imagen2" src="img/logs.png" /> <!-- Reusing an existing image, consider a more specific one -->
          <div class="button">
            <a href="waits.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="box">
          <h2 class="titulo">Performance Dashboard</h2>
          <img class="imagen2" src="img/grafica.png" /> <!-- Reusing grafica.png -->
          <div class="button">
            <a href="performance_dashboard.php">
              <button class="boton">Enter</button>
            </a>
          </div>
        </div>
      </div>

    </div>

<script type="text/javascript">
$(document).ready(function() {
    const selectedOracleConnId = <?php echo json_encode($selected_oracle_conn_id); ?>;
    const noSelectionText = "-- Select a Connection --";

    function loadConnectionProfiles() {
        $.ajax({
            url: '../server/connections_handler.php?action=list',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                const selectElement = $('#oracleConnectionSelect');
                selectElement.empty(); // Clear existing options
                selectElement.append($('<option>', { value: '', text: noSelectionText }));

                if (response.status === 'success' && response.connections.length > 0) {
                    response.connections.forEach(function(conn) {
                        selectElement.append($('<option>', {
                            value: conn.conn_id,
                            text: escapeHtml(conn.profile_name)
                        }));
                    });
                    if (selectedOracleConnId) {
                        selectElement.val(selectedOracleConnId);
                    }
                } else if (response.connections.length === 0) {
                     selectElement.append($('<option>', { value: '', text: 'No profiles defined. Please add one.'})).prop('disabled', true);
                     $('#currentOracleProfileName').text('None - Please Add/Select a Profile');
                } else {
                    displayDbSelectMessage('Error: ' + (response.message || 'Could not load profiles.'), 'error');
                }
            },
            error: function(xhr) {
                displayDbSelectMessage('Failed to load connection profiles: ' + xhr.responseText, 'error');
                 $('#oracleConnectionSelect').empty().append($('<option>', { value: '', text: 'Error loading profiles' })).prop('disabled', true);
            }
        });
    }

    $('#oracleConnectionSelect').on('change', function() {
        const connId = $(this).val();
        const profileName = $(this).find('option:selected').text();
        $('#dbSelectMessage').hide();

        if (connId) {
            $.ajax({
                url: '../server/connections_handler.php?action=set_active_connection',
                type: 'POST',
                data: { conn_id: connId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#currentOracleProfileName').text(escapeHtml(response.selected_profile_name));
                        displayDbSelectMessage('Active DB set to: ' + escapeHtml(response.selected_profile_name), 'success');
                        // Potentially trigger a refresh of data on monitoring pages if this menu is part of a global header
                        // or inform user that monitoring pages will now use this connection.
                    } else {
                        displayDbSelectMessage('Error: ' + (response.message || 'Could not set active connection.'), 'error');
                         // Revert dropdown if backend failed to set it
                        if(selectedOracleConnId) $('#oracleConnectionSelect').val(selectedOracleConnId);
                        else $('#oracleConnectionSelect').val('');
                    }
                },
                error: function(xhr) {
                    displayDbSelectMessage('Failed to set active connection: ' + xhr.responseText, 'error');
                    if(selectedOracleConnId) $('#oracleConnectionSelect').val(selectedOracleConnId);
                    else $('#oracleConnectionSelect').val('');
                }
            });
        } else { // "-- Select a Connection --" chosen
             $('#currentOracleProfileName').text('None Selected');
             // Optionally, clear the session variable on the backend if "None" is selected
             // For now, just a UI update. The backend requires a profile to be selected for results.php
        }
    });

    function displayDbSelectMessage(message, type) {
        const msgArea = $('#dbSelectMessage');
        msgArea.text(message)
               .removeClass('text-success text-danger')
               .addClass(type === 'success' ? 'text-success' : 'text-danger')
               .fadeIn().delay(3000).fadeOut();
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

    // Initial load of connection profiles
    loadConnectionProfiles();
});
</script>
</body>
</html>
