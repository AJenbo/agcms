var getTableCall = null;
function getTable(categoryId, tableId, cell)
{
    xHttp.cancel(getTableCall);
    getTableCall = xHttp.request("/ajax/category/" + categoryId + "/table/" + tableId + "/" + cell, injectHtml);
    return false;
}

var getAddressCall = null;
function getAddress(phonenumber, callback)
{
    phonenumber = phonenumber.replace('/\s/', '');
    phonenumber = phonenumber.replace('/^[+]45/', '');
    if(!phonenumber) {
        alert('De skal udfylde telefon nummeret først.');
        return false;
    }
    if(phonenumber.length !== 8) {
        alert('Telefonnummeret skal være på 8 cifre!');
        return false;
    }
    xHttp.cancel(getAddressCall);
    getAddressCall = xHttp.request("/ajax/address/" + phonenumber, callback);
    return false;
}

var getKatCall = null;
function getKat(categoryId, column)
{
    xHttp.cancel(getKatCall);
    getKatCall = xHttp.request("/ajax/category/" + categoryId + "/" + column, injectHtml);
    return false;
}

function injectHtml(data)
{
    if(!data || data.error) {
        alert(data.error || "Error");
        return;
    }

    document.getElementById(data.id).innerHTML = data.html;
}

function updateprice()
{
    var total = document.getElementsByClassName("total");
    var input = document.getElementsByName("quantity[]");
    var subtotal = 0;
    for(var i = 0; i < values.length; i++) {
        value = values[i] * input[i].value;
        if(values[i] > 0) {
            total[i].innerHTML = value.toFixed(2).toString().replace(/\./, ",");
        }
        subtotal = subtotal + value;
    }

    document.getElementById("total").innerHTML = subtotal.toFixed(2).toString().replace(/\./, ",");
}

function showhidealtpost(status)
{
    var Trs = document.getElementsByTagName("TR");

    if(status) {
        for(var i = 0; i < Trs.length; i++) {
            if(Trs[i].className === "altpost") {
                Trs[i].style.display = "";
            }
        }
        return;
    }

    for(var i = 0; i < Trs.length; i++) {
        if(Trs[i].className === "altpost") {
            Trs[i].style.display = "none";
        }
    }
}

function getAddress_r1(responce)
{
    if(responce.error) {
        alert(responce.error);
        return false;
    }
    if(responce.name) {
        document.getElementById("navn").value = responce.name;
    }
    if(responce.attn) {
        document.getElementById("attn").value = responce.attn;
    }
    if(responce.address1) {
        document.getElementById("adresse").value = responce.address1;
    }
    if(responce.postbox) {
        document.getElementById("postbox").value = responce.postbox;
    }
    if(responce.zipcode) {
        document.getElementById("postnr").value = responce.zipcode;
    }
    if(responce.email) {
        document.getElementById("email").value = responce.email;
    }
    if(document.getElementById("land").value === "DK" && responce.zipcode && arrayZipcode[responce.zipcode]) {
        document.getElementById("by").value = arrayZipcode[responce.zipcode];
    }
}

function getAddress_r2(responce)
{
    if(responce.error) {
        alert(responce.error);
        return false;
    }
    if(responce.name) {
        document.getElementById("postname").value = responce.name;
    }
    if(responce.attn) {
        document.getElementById("postattn").value = responce.attn;
    }
    if(responce.address1) {
        document.getElementById("postaddress").value = responce.address1;
    }
    if(responce.address2) {
        document.getElementById("postaddress2").value = responce.address2;
    }
    if(responce.postbox) {
        document.getElementById("postpostbox").value = responce.postbox;
    }
    if(responce.zipcode) {
        document.getElementById("postpostalcode").value = responce.zipcode;
    }
    if(document.getElementById("postcountry").value === "DK" && responce.zipcode) {
        document.getElementById("postcity").value = arrayZipcode[responce.zipcode];
    }
}

function chnageZipCode(zipcode, countryid, cityid)
{
    if(document.getElementById(countryid).value !== "DK") {
        return false;
    }
    if(arrayZipcode[zipcode]) {
        document.getElementById(cityid).value = arrayZipcode[zipcode];
    }
}

var xHttp = {
    "requests" : [],
    "cancel" : function(id) {
        if(xHttp.requests[id]) {
            xHttp.requests[id].abort();
            xHttp.requests.splice(id, 1, null);
        }
    },

    "request" : function(uri, callback, data) {
        var id = xHttp.requests.length;

        var x = new window.XMLHttpRequest();
        x.responseType = "json";
        x.onload = function(event) {
            xHttp.requests[id] = null;

            if(x.status < 200 || x.status > 299) {
                alert(x.statusText);
                return;
            }

            callback(x.response);
        };

        var method = "GET";
        if(data) {
            method = "POST";
            x.setRequestHeader("Content-Type", "application/json");
            data = JSON.stringify(data);
        }

        x.open(method, uri);
        x.send(data);

        xHttp.requests[id] = x;
        return id;
    }
};
