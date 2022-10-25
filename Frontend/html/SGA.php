<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Size Monitor</title>
    <link rel="stylesheet" href="sga.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      var SGAsize; 
      var test;
      $.ajax({
          url: "http://localhost/Oracle_Monitor_SGA_TableSpce/results/oracle.php?q=sgasize",
          dataType: 'json',
          async: false,
          success: function(response) {
            console.log(response);
             SGAsize= response;
          } 
        }).responsetext;

      function drawChart(){
        var rows;

        $.ajax({
          url: "http://localhost/Oracle_Monitor_SGA_TableSpce/results/oracle.php?q=sga",
          dataType: 'json',
          async: false,
          success: function(response) {
          // Do what ever with the response here
            console.log(response);
          // or save it for later. 
            test = response;
          } 
        }).responsetext;
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Time');
        data.addColumn('number','Used Mb');
        data.addColumn('number', 'HWM');
        data.addRows(test);
        var options = {
          title: 'SGA Performance',
          vAxis: {
            minValue: 1000,
            maxValue: SGAsize
          },
          curveType: 'function',
          legend: {
            position: 'bottom' 
          },
          animation: {
            duration:1000,
            easing: 'out'
          },
          backgroundColor: '#ffffff',
          colors: ['blue','gray'],
            series: {
              1: { lineDashStyle: [10, 2] }
          }
        };
        
        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
        chart.draw(data, options);

        
      }
      setInterval(drawChart, 5000);
    </script>
</head>
<body>
  <div class="row">
    <div class="header">
        <div class="box1">
          <p class="titu">SGA Size Monitor</p>
          <img class="logo" src="logo.png">
          <div class="button">
            <a href="menu.html">
              <button class="boton">Back to Menu</button>
            </a>
          </div>
        </div>
      </div>
    </div>
    <!–this is the div that will hold the pie chart–>
    <div class="row">
      <div id=”chart_div” ></div>
      <div id=”chart_div2″ ></div>
      <div id="curve_chart" style="width: 100%; height: 700px"></div>
    </div>

    <script>
        function myfuntion(){
            alert("Alto Consumo del SGA");
        }
    </script>
</body>
</html>