
api = {};

api.request = function(uri,token,jsondata,method,callback) {

    var url = "https://www.rhjodoigne.be/api.php/"+uri;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if(xmlhttp.readyState == 4) {
            console.log(xmlhttp.responseText);
            callback(xmlhttp.responseText);
        }
    };
    xmlhttp.open(method, url);
    if(token) xmlhttp.setRequestHeader("Apitoken","token "+token);
    xmlhttp.setRequestHeader("Content-type","application/json");
    xmlhttp.send(jsondata);
}

api.getUser = function(token,callback) {

    var uri = "users";

    api.request(uri,token,null,"GET",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 200) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            callback(jsonresp);
        }
    });
}

api.getReservations = function(uid,cid,token,callback) {

    var uri = "reservations/"+uid+"/"+cid;

    api.request(uri,token,null,"GET",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 200) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            callback(jsonresp.reservations);
        }
    });
}

api.getDates = function(callback) {

    var uri = "concerts";

    api.request(uri,null,null,"GET",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 200) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            callback(jsonresp.concerts);
        }
    });
}

api.newUser = function(utitle,uname,token,callback) {

    var uri = "users";
    var jsondata = {};
    jsondata.utitle = utitle;
    jsondata.uname = uname;
    jsondata = JSON.stringify(jsondata);

    api.request(uri,token,jsondata,"PUT",function(r) {
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 201) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            callback(jsonresp);
        }
    });
}

api.newReservation = function(cid,nChildren,nAdults,token) {

    var uri = "reservations/"+cid;
    var jsondata = {};
    jsondata.nChildren = nChildren;
    jsondata.nAdults = nAdults;
    jsondata = JSON.stringify(jsondata);

    api.request(uri,token,jsondata,"PUT",function(r) {
        var jsonresp = JSON.parse(r);
        if(jsonresp.status == 500) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            window.location.href = jsonresp.url;
        }
    });
}

api.getFreePlacesXY = function(cid,callback) {

    var uri = "places/"+cid;

    api.request(uri,null,null,"GET",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 200) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            callback(jsonresp);
        }
    });
}

api.changeUserDetails = function(uname,utitle,token,callback) {

    var uri = "users";
    var jsondata = {};
    jsondata.uname = uname;
    jsondata.utitle = utitle;
    jsondata = JSON.stringify(jsondata);

    api.request(uri,token,jsondata,"PUT",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 201) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        } else {
            callback(jsonresp);
        }
    });
}

api.getPending = function(token,callback) {

    var uri = "reservations";

    api.request(uri,token,null,"GET",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status == 500) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        }
        else {
            callback(jsonresp);
        }
    });
}

api.sendTicket = function(rid,token,callback) {

    var uri = "tickets/"+rid;

    api.request(uri,token,null,"GET",function(r){
        var jsonresp = JSON.parse(r);
        if(jsonresp.status != 200) {
            alert(jsonresp.message);
            throw new Error(jsonresp.message);
        }
        else {
            callback(jsonresp);
        }
    });
}