<?php
// Ensure user is logged in to the application
session_start();
if (!isset($_SESSION['app_user_id'])) {
    header('Location: index.html?error=' . urlencode("Please login to manage connections."));
    exit();
}
$app_username = $_SESSION['app_username']; // For display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage DB Connections - Oracle Monitor</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style/style.css"> <!-- Base style -->
    <link rel="stylesheet" href="style/menu.css">  <!-- For .box like styling and header -->

    <style>
        body { background-color: #2c3e50; color: #ecf0f1; font-size: 0.9rem;}
        .container-main { padding: 20px; }
        .card-custom { background-color: #34495e; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .card-custom h3, .card-custom h4 { color: #ecf0f1; border-bottom: 1px solid #4a627a; padding-bottom: 10px; margin-bottom:15px;}
        .form-label { color: #bdc3c7; }
        .form-control, .form-select {
            background-color: #2c3e50;
            color: #ecf0f1;
            border: 1px solid #4a627a;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2c3e50;
            color: #ecf0f1;
            border-color: #1abc9c;
            box-shadow: 0 0 0 0.2rem rgba(26, 188, 156, 0.25);
        }
        .btn-custom-primary { background-color: #1abc9c; border-color: #1abc9c; color: #fff; }
        .btn-custom-primary:hover { background-color: #16a085; border-color: #16a085; }
        .btn-custom-danger { background-color: #e74c3c; border-color: #e74c3c; color: #fff; }
        .btn-custom-danger:hover { background-color: #c0392b; border-color: #c0392b; }
        .table { color: #ecf0f1; }
        .table th { color: #bdc3c7; }
        .table td { background-color: #34495e; border-color: #4a627a;}
        .alert-custom { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-custom-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-custom-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

         /* Copied from menu.css to ensure consistent header */
        .header .box1 {
            background: #3e8e41; /* Main app color */
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
        }
        .header .titu { font-size: 2em; margin: 0; }
        .header .logo { height: 50px; width: auto; }
        .app-user-info { font-size: 0.9em; color: #f0f0f0; }
    </style>
</head>
<body>
    <div class="row">
        <div class="header">
            <div class="box1">
                <p class="titu">Manage DB Connections</p>
                <div style="display: flex; align-items: center;">
                    <span class="app-user-info">User: <?php echo htmlspecialchars($app_username); ?></span>
                    <a href="../server/logout_handler.php" style="margin-left: 20px; color: white; text-decoration: none; background-color: #c0392b; padding: 8px 15px; border-radius: 4px;">Logout</a>
                    <img class="logo" src="img/logo.png" style="margin-left: 20px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="container-main">
        <div class="button" style="margin-bottom: 20px;">
            <a href="menu.html"><button class="boton">Back to Menu</button></a>
        </div>

        <div id="messageArea" class="alert-custom" style="display:none;"></div>

        <!-- Add New Connection Profile Form -->
        <div class="card-custom">
            <h3>Add New Oracle Database Connection</h3>
            <form id="addConnectionForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="profile_name" class="form-label">Profile Name (e.g., "Dev DB1", "Prod CRM")</label>
                        <input type="text" class="form-control" id="profile_name" name="profile_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="db_host" class="form-label">Hostname or IP Address</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="db_port" class="form-label">Port (Default: 1521)</label>
                        <input type="number" class="form-control" id="db_port" name="db_port" placeholder="1521" value="1521">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="db_service_name" class="form-label">Service Name (or SID)</label>
                        <input type="text" class="form-control" id="db_service_name" name="db_service_name" required>
                    </div>
                     <div class="col-md-4 mb-3">
                        <label for="db_user" class="form-label">Oracle Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" required autocomplete="off">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_password" class="form-label">Oracle Password</label>
                        <input type="password" class="form-control" id="db_password" name="db_password" required autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-custom-primary">Add Connection</button>
            </form>
        </div>

        <!-- List Existing Connection Profiles -->
        <div class="card-custom mt-4">
            <h3>Your Saved Connections</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Profile Name</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Service/SID</th>
                            <th>DB User</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="connectionsTableBody">
                        <!-- Rows will be populated by JavaScript -->
                        <tr><td colspan="6" class="text-center">Loading connections...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    loadConnections();

    $('#addConnectionForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.ajax({
            url: '../server/connections_handler.php?action=add',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                displayMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addConnectionForm')[0].reset(); // Clear form
                    loadConnections(); // Refresh list
                }
            },
            error: function(xhr, status, error) {
                displayMessage('Error adding connection: ' + xhr.responseText, 'error');
            }
        });
    });

    // Event delegation for delete buttons
    $('#connectionsTableBody').on('click', '.delete-conn-btn', function() {
        const connId = $(this).data('id');
        if (confirm('Are you sure you want to delete this connection profile?')) {
            $.ajax({
                url: '../server/connections_handler.php?action=delete',
                type: 'POST',
                data: { conn_id: connId },
                dataType: 'json',
                success: function(response) {
                    displayMessage(response.message, response.status);
                    if (response.status === 'success') {
                        loadConnections(); // Refresh list
                    }
                },
                error: function(xhr, status, error) {
                    displayMessage('Error deleting connection: ' + xhr.responseText, 'error');
                }
            });
        }
    });
});

function loadConnections() {
    $('#connectionsTableBody').html('<tr><td colspan="6" class="text-center">Loading connections...</td></tr>');
    $.ajax({
        url: '../server/connections_handler.php?action=list',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            const tableBody = $('#connectionsTableBody');
            tableBody.empty();
            if (response.status === 'success' && response.connections.length > 0) {
                response.connections.forEach(function(conn) {
                    // Note: We don't display the password
                    tableBody.append(`
                        <tr>
                            <td>${escapeHtml(conn.profile_name)}</td>
                            <td>${escapeHtml(conn.db_host)}</td>
                            <td>${escapeHtml(conn.db_port)}</td>
                            <td>${escapeHtml(conn.db_service_name)}</td>
                            <td>${escapeHtml(conn.db_user)}</td>
                            <td>
                                <button class="btn btn-sm btn-custom-danger delete-conn-btn" data-id="${conn.conn_id}">Delete</button>
                            </td>
                        </tr>
                    `);
                });
            } else if (response.status === 'success') {
                tableBody.html('<tr><td colspan="6" class="text-center">No connection profiles found. Add one above.</td></tr>');
            } else {
                 tableBody.html(`<tr><td colspan="6" class="text-center" style="color:red;">${escapeHtml(response.message || 'Error loading connections.')}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            $('#connectionsTableBody').html(`<tr><td colspan="6" class="text-center" style="color:red;">Failed to load connections: ${escapeHtml(xhr.responseText)}</td></tr>`);
        }
    });
}

function displayMessage(message, type) {
    const messageArea = $('#messageArea');
    messageArea.text(message);
    messageArea.removeClass('alert-custom-success alert-custom-error').addClass(type === 'success' ? 'alert-custom-success' : 'alert-custom-error');
    messageArea.fadeIn().delay(5000).fadeOut();
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

</script>
</body>
</html>
