var servers;
$.ajax({
    url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/login.php",
    dataType: 'json',
    async: false,
    success: function (response) {
        console.log(response);
        servers = response;
    }
}).responsetext;


function loadServers() {
    var opt = document.getElementById("servers");
    for (const iterator of servers) { 
        for (const server of iterator) {
            var x = document.createElement("option");
            x.value = server;
            x.innerHTML = server;
            opt.append(x);
        }
    }
}
loadServers();