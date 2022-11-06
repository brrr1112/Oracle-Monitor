selectuser = document.getElementById("usernames");

function loadUsernames() {
    var usernames;
    $.ajax({
        url: "http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=usernames",
        dataType: 'json',
        async: false,
        success: function (response) {
            console.log(response);
            usernames = response;
        }
    }).responsetext;

    for (const user of usernames) {
        var x = document.createElement("option");
        x.value = user;
        x.innerHTML = user;
        selectuser.append(x);
    }
}

function loadSQL(user){
    var link="http://localhost/Oracle_Monitor_SGA_TableSpce/server/results.php?q=usersql";
    if (user!=null) {
        link = link+"&user="+user;
    }
    var result;
    console.log(link);
    $.ajax({
        url: link,
        dataType: 'json',
        async: false,
        success: function (response) {
            console.log(response);
            result = response;
        }
    }).responsetext;


    document.getElementById("alerta").style.display = 'none';
    console.log('hidden');
    if(result==null || result.length == 0){
        console.log(result.length);
        document.getElementById("alerta").style.display = 'contents';
        console.log('visble');
    }

    document.getElementById("table").getElementsByTagName("tbody")[0].remove()
    document.getElementById("table").append(document.createElement("tbody", { id: "tablebody" }));
    
    for (const row of result) {
        var r = document.getElementById("table").getElementsByTagName("tbody")[0].insertRow(-1);
        for (const data of row) {
            var cell = r.insertCell(-1);
            cell.innerHTML = data;
        }
    }

}
   
loadUsernames();
loadSQL(null);

selectuser.addEventListener("change", ()=>{
    console.log(selectuser.value);
    loadSQL(selectuser.value), false}
    );