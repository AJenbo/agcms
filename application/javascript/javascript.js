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
