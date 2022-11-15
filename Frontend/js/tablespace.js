google.charts.load('current', { 'packages': ['corechart'] });
google.charts.setOnLoadCallback(drawPieChart);
google.charts.setOnLoadCallback(drawBarChart);


var tspie;
$.ajax({
    url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=tspie",
    dataType: 'json',
    async: false,
    success: function (response) {
        console.log(response);
        tspie = response;
    }
}).responsetext;


function drawPieChart() {
    var data = new google.visualization.DataTable(tspie);

    var options = {
        title: 'Table space size',
        curveType: 'function',
        legend: { position: 'bottom' }
    };

    var chart = new google.visualization.PieChart(document.getElementById('piechart'));
    chart.draw(data, options);
}


var tsnames;

$.ajax({
    url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=tsnames",
    dataType: 'json',
    async: false,
    success: function (response) {
        console.log(response);
        tsnames = response;
    }
}).responsetext;


google.charts.load("current", { packages: ["corechart"] });
google.charts.setOnLoadCallback(drawChart);

function drawBarChart() {
    var tsdata;
    $.ajax({
        url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=tsbar",
        dataType: 'json',
        async: false,
        success: function (response) {
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

function getTable(){
    let tbstable;
    console.log("FUNCTION");
    $.ajax({
        url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=tsinfo",
        dataType: 'json',
        async: false,
        success: function (response) {
            //console.log("BEFORE");
            console.log(response);
            //console.log("After");
            tbstable = response;
        }
    }).responsetext;

    document.getElementById("table").getElementsByTagName("tbody")[0].remove()
    document.getElementById("table").append(document.createElement("tbody", { id: "tablebody" }));
    
    for (const row of tbstable) {
        var r = document.getElementById("table").getElementsByTagName("tbody")[0].insertRow(-1);
        for (const data of row) {
            var cell = r.insertCell(-1);
            cell.innerHTML = data;
        }
    }
}