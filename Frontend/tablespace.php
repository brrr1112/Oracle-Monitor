<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Space</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">

    <link rel="stylesheet" href="style/tablespace.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script defer type="text/javascript" src="js/tablespace.js"></script>
    
</head>

<body>
    <div class="row">
        <div class="header">
            <div class="box1">
                <p class="titu">Table Space</p>
                <img class="logo" src="img/logo.png">
            </div>
        </div>
    </div>

    <!–this is the div that will hold the pie chart–>
        <div class="d-flex justify-content-evenly">
            <div class="piechart" id="piechart" style="width: 700px; height: 500px; "></div>
    
            <div id="barchart_values" style="width: 900px; height: 40%; "></div>

        </div>
        <br>
        <div class="table-responsive container">
          <table id="table" class="table" style="table-layout: fixed;">
                <thead class="text-start">
                <th>Name</th>
                <th>Used (MB)</th>
                <th>Free (MB)</th>
                <th>Total (MB)</th>
                <th>HWM (MB)</th>
                <th>Daily_grow (MB)</th>
                 <th>Remaining_Time (Days)</th>
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