<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SGA Monitor</title>
  <link rel="stylesheet" href="style/sga.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript" src="js/sga.js"></script>
</head>

<body>
  <div class="row">
    <div class="header">
      <div class="box1">
        <p class="titu">SGA Size Monitor</p>
        <img class="logo" src="img/logo.png">
      </div>
    </div>
  </div>
  <!–this is the div that will hold the pie chart–>
    <div class="row-content">
      <div id="curve_chart" style="width: 50%; height: 500px"></div>
      <div class="alert-div">
        <div class="alert-banner">
          <img class="alert-icon" src="img/alert.png">
          <h2>Alerts</h2>
        </div>
        <div class="table-responsive">
          <table id="table" class="table" style="table-layout: fixed">
            <thead class="text-start">
              <th>User</th>
              <th>load_time</th>
              <th>Sql_text</th>
            </thead>
            <tbody id="tablebody">
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="button">
      <a href="menu.html">
        <button class="boton">Back to Menu</button>
      </a>
    </div>
</body>

</html>