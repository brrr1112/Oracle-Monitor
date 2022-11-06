<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="200">
    <title>SGA Size Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style/users.css">
    <script defer type="text/javascript" src="js/users.js"></script>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="header">
                <div class="box1">
                    <p class="titu">Users Monitor</p>
                    <img class="logo" src="img/logo.png">
                </div>
            </div>
        </div>
        <div class="button">
            <a href="menu.html">
                <button class="boton">Back to Menu</button>
            </a>
        </div>

        <h1>Users Table</h1>
        <br>

        <form id="form" method="GET">
            <div class="inputbox">
                <label>USER: </label>
                <select id="usernames" name="usernames">
                    <option selected="selected" disabled>Choose User</option>
                </select>
                <i></i>
            </div>
        </form>

        <div id="alerta" class="alerta">
            <h2>No queries</h2>
        </div>

        <br>
        <div class="table-responsive">
            <table id="table" class="table table-striped" style="table-layout: fixed">
                <thead class="text-muted">
                    <th>User</th>
                    <th>first_load_time</th>
                    <th>COMMAND_TYPE</th>
                    <th>Sql_text</th>
                </thead>
                <tbody id="tablebody">
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>