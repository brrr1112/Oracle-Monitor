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

    if(result==null || result.length==0){
        if (document.getElementById("alerttitle")!=null) {
            document.getElementById("alerttitle").remove();
        }
        h2 = document.createElement("h2");
        h2.id = "alerttitle";
        h2.innerHTML = "No queries";
        document.getElementById("alert").append(h2);
    }

    document.getElementById("table").getElementsByTagName("tbody")[0].remove()
    document.getElementById("table").append(document.createElement("tbody", { id: "tablebody" }));
    
    for (const row of result) {
        var r = document.getElementById("table").getElementsByTagName("tbody")[0].insertRow(-1);
        for (const data of row) {
            var cell = r.insertCell(-1);
            cell.innerHTML = data;

            // var td = document.createElement("td");
            // td.innerHTML = data;
            // selectuser.append(td);
        }
    }

}
   
loadUsernames();
loadSQL(null);

selectuser.addEventListener("change", ()=>{
    console.log(selectuser.value);
    loadSQL(selectuser.value), false}
    );