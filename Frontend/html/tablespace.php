<?php

include_once('Oracle.php');


if (!$conn) {
$e = oci_error();
trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$query= oci_parse($conn,'select tablespace_name, sum(bytes) total from Dba_data_files Group by tablespace_name');
oci_execute($query);

$rows = array();
$table = array();
$table['cols'] = array(

array('label' => 'tablespace_name', 'type' => 'string'),
array('label' => 'USED_MB Total', 'type' => 'number'),
);

    $rows = array();
    while($r = oci_fetch_array($query, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $temp = array();
    //The below col names have to be in upper caps.
    
    $temp[] = array('v' => (string) $r["TABLESPACE_NAME"]);
    
    $temp[] = array('v' => (int) $r["TOTAL"]);
    
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
          title: 'Table space size',
          curveType: 'function',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }
    </script>
</head>
<body>
    <!–this is the div that will hold the pie chart–>
    <div id=”chart_div” ></div>
    <div id=”chart_div2″ ></div>
    <div id="piechart" style="width: 900px; height: 500px;"></div>
</body>
</html>