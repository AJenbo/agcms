var xHttp = {
    "requests" : [],
    "cancel" : function(id) {
        if(xHttp.requests[id]) {
            xHttp.requests[id].abort();
            xHttp.requests.splice(id, 1, null);
        }
    },

    "request" : function(uri, callback, method = "GET", data = null) {
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

        x.open(method, uri);

        if(typeof data !== 'string') {
            x.setRequestHeader("Content-Type", "application/json");
            data = JSON.stringify(data);
        }

        x.send(data);

        xHttp.requests[id] = x;
        return id;
    }
};
