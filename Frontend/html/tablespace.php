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
    <title>Table Space</title>
    <link rel="stylesheet" href="tableuser.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>  
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
    <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">Table Space</p>
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
    <div id=”chart_div” ></div>
    <div id=”chart_div2″ ></div>
    <div id="piechart" style="width: 700px; height: 500px;"></div>

    <script>
        var tsnames;
        
        $.ajax({
          url: "http://localhost/Oracle_Monitor_SGA_TableSpce/results/oracle.php?q=tsnames",
          dataType: 'json',
          async: false,
          success: function(response) {
            console.log(response);
            tsnames = response;
          } 
        }).responsetext;
        

        google.charts.load("current", {packages:["corechart"]});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var tsdata;
            $.ajax({
                url: "http://localhost/Oracle_Monitor_SGA_TableSpce/results/oracle.php?q=tsbar",
                dataType: 'json',
                async: false,
                success: function(response) {
                    console.log(response); 
                    tsbar = response;
                }
            }).responsetext;

            var data = new google.visualization.DataTable();
            data.addColumn('string', 'name');
            data.addColumn('number', 'Used');
            data.addColumn('number', 'Free_1');
            data.addColumn('number', 'Free_2');
            for (let i = 0; i < tsbar.length; i++) {
                console.log(tsbar[i]);
                data.addRow(tsbar[i]);
            }
            //data.addRows(tsbar);
            

            //valores de prueba
/*
            var data = google.visualization.arrayToDataTable([
                ['Used Mb', 'Free #1 Mb', 'Free #2 Mb',  { role: 'annotation' } ],
                [tsnames[0], 10, 24, 20],
                ['2020', 16, 22, 23],
                ['2030', 28, 19, 29]
            ]);*/
            var options = {
                width: 400,
                height: 400,
                legend: { position: 'top', maxLines: 3 },
                bar: { groupWidth: '75%' },
                isStacked: true
            };
            var chart = new google.visualization.BarChart(document.getElementById("barchart_values"));
            chart.draw(data, options);
        }
        /*
        function loadBar(){
            $.get('test.php', { album: this.title }, function() {
            content.html(response);
            });
            var value = document.getElementById("ts").value;
            console.log(value);
            drawChart(value);

        }
        */
    </script>
    <div id="barchart_values" style="width: 900px; height: 300px;"></div>
</body>
</html>