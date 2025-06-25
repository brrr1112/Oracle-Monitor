<?php
session_start();
if (!isset($_SESSION['app_user_id'])) {
    header('Location: index.html?message=' . urlencode("Please login to access this page.") . "&type=error");
    exit();
}
$app_username = $_SESSION['app_username'] ?? 'User';
$selected_oracle_profile_name = $_SESSION['selected_oracle_profile_name'] ?? 'None Selected';

// This check is crucial for pages that directly load data.
// If no profile is selected, results.php will return an error, which JS should handle.
// We can also show a more prominent message here if nothing is selected.
$no_profile_selected = !isset($_SESSION['selected_oracle_conn_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> Duplicate viewport -->
  <title>SGA Monitor - <?php echo htmlspecialchars($selected_oracle_profile_name); ?></title>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <link rel="stylesheet" href="style/sga.css">
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript" src="js/sga.js"></script>
</head>

<body>
  <div class="row">
    <div class="header">
      <div class="box1" style="display: flex; justify-content: space-between; align-items: center;">
        <p class="titu" style="margin: 0;">SGA Monitor <span style="font-size: 0.6em; color: #f0f0f0;">(DB: <?php echo htmlspecialchars($selected_oracle_profile_name); ?>)</span></p>
        <img class="logo" src="img/logo.png" style="height: 50px;">
      </div>
    </div>
  </div>

  <?php if ($no_profile_selected): ?>
    <div class="container mt-4">
        <div class="alert alert-warning text-center">
            <h4>No Oracle Database Profile Selected</h4>
            <p>Please <a href="menu.php">go to the Menu</a> to select an active Oracle database connection profile for monitoring.</p>
            <p>If you haven't added any profiles yet, you can <a href="manage_connections.php">manage your connections here</a>.</p>
        </div>
    </div>
  <?php else: ?>
    <!–this is the div that will hold the pie chart–>
        <div id="curve_chart" style="width: 90%; max-width: 800px; height: 500px; margin:20px auto;"></div>

        <div class="container">
            <div class="alert-banner" style="margin-top:30px;">
              <img class="alert-icon" src="img/alert.png">
              <h2>Alerts</h2>
            </div>
            <br>
            <div class="table-responsive">
              <table id="table" class="table table-striped" style="table-layout: fixed">
                <thead class="text-start">
                  <th>User</th>
                  <th>load_time</th>
                  <th>Sql_text</th>
                  <th>Status</th>
                </thead>
                <tbody id="tablebody">
                  <!-- JS will populate this or show error from results.php -->
                </tbody>
              </table>
            </div>
        </div>
  <?php endif; ?>

    <div class="button text-center mt-4 mb-4">
      <a href="menu.php">
        <button class="boton">Back to Menu</button>
      </a>
    </div>
</body>

</html>