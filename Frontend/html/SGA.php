<?php

include_once('Oracle.php');


if (!$conn) {
$e = oci_error();
trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$query= oci_parse($conn,'select USED_MB,TIME from sys.job_SGA_Table');
oci_execute($query);

$rows = array();
$table = array();
$table['cols'] = array(

array('label' => 'TIME', 'type' => 'string'),
array('label' => 'USED_MB', 'type' => 'number'),
);

    $rows = array();
    while($r = oci_fetch_array($query, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $temp = array();
    //The below col names have to be in upper caps.
    
    $temp[] = array('v' => (string) $r["TIME"]);
    
    $temp[] = array('v' => (int) $r["USED_MB"]);
    
    $rows[] = array('c' => $temp);
    }
    

$table['rows'] = $rows;
$jsonTable = json_encode($table);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
  
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = new google.visualization.DataTable(<?=$jsonTable?>);

        var options = {
          title: 'SGA Performance',
          curveType: 'function',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>
</head>
<body>
    <!–this is the div that will hold the pie chart–>
    <div id=”chart_div” ></div>
    <div id=”chart_div2″ ></div>
    <div id="curve_chart" style="width: 80%; height: 500px"></div>
</body>
</html>