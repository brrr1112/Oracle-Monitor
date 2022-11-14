google.charts.load('current', { 'packages': ['corechart'] });
google.charts.setOnLoadCallback(drawChart);
var SGAsize;
var test;
$.ajax({
    url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=sgasize",
    dataType: 'json',
    async: false,
    success: function (response) {
        SGAsize = response;
    }
}).responsetext;

function drawChart() {
    $.ajax({
        url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=sga",
        dataType: 'json',
        async: false,
        success: function (response) {
            test = response;
        }
    }).responsetext;

    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Time');
    data.addColumn('number', 'Used Mb');
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
            duration: 1000,
            easing: 'out'
        },
        backgroundColor: '#ffffff',
        colors: ['blue', 'gray'],
        series: {
            1: { lineDashStyle: [10, 2] }
        }
    };

    var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
    chart.draw(data, options);

}

function getAlerts(){
    let alerts;
    console.log("FUNCTION");
    $.ajax({
        url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=sgaalerts",
        dataType: 'json',
        async: false,
        success: function (response) {
            //console.log("BEFORE");
            console.log(response);
            //console.log("After");
            alerts = response;
        }
    }).responsetext;

    document.getElementById("table").getElementsByTagName("tbody")[0].remove()
    document.getElementById("table").append(document.createElement("tbody", { id: "tablebody" }));
    
    for (const row of alerts) {
        var r = document.getElementById("table").getElementsByTagName("tbody")[0].insertRow(-1);
        for (const data of row) {
            var cell = r.insertCell(-1);
            cell.innerHTML = data;
        }
    }
}

function alerts() {
    var value;
    $.ajax({
        url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=sgastatus",
        dataType: 'json',
        async: false,
        success: function (response) {
            console.log(response);
            value = response;
        }
    }).responsetext;

    if (value = 1){
        getAlerts();
    }
}

setInterval(drawChart, 5000);
setInterval(alerts, 5000);