<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SGA Monitor</title>
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
      <div class="box1">
        <p class="titu">SGA Size Monitor</p>
        <img class="logo" src="img/logo.png">
      </div>
    </div>
  </div>
  <!–this is the div that will hold the pie chart–>
      <div id="curve_chart" style="width: 50%; height: 500px; margin:0 auto;"></div>
    
      
        <div class="alert-banner" style="margin-top:30px;">
          <img class="alert-icon" src="img/alert.png">
          <h2>Alerts</h2>
        </div>
        <br>
        <div class="table-responsive">
          <table id="table" class="table" style="table-layout: fixed">
            <thead class="text-start">
              <th>User</th>
              <th>load_time</th>
              <th>Sql_text</th>
              <th>Status</th>
            </thead>
            <tbody id="tablebody">
            </tbody>
          </table>
        </div>
    <div class="button">
      <a href="menu.html">
        <button class="boton">Back to Menu</button>
      </a>
    </div>
</body>

</html>