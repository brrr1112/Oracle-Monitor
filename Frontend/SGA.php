<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Size Monitor</title>
    <link rel = "stylesheet" href="style/sga.css">
    <script src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script type = "text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
  <div class="row">
    <div id=”chart_div” ></div>
    <div id=”chart_div2″ ></div>
    <div id="curve_chart" style="width: 50%; height: 500px"></div>
  </div>
  <div class="button">
            <a href="menu.html">
              <button class="boton">Back to Menu</button>
            </a>
  </div>
</body>
</html>