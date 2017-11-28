var xHttp = {
    "requests": [],
    "cancel": function(id) {
        if (xHttp.requests[id]) {
            xHttp.requests[id].abort();
            xHttp.requests.splice(id, 1, null);
        }
    },

    "request": function(uri, callback = null, method = "GET", data = null) {
        var id = xHttp.requests.length;

        var x = new window.XMLHttpRequest();
        x.responseType = "json";
        x.onload = function() {
            xHttp.requests[id] = null;

            if (x.status < 200 || x.status > 299 || x.response.error) {
                var message = x.response && x.response.error && x.response.error.message || x.statusText;
                alert("Error: " + message);
            }

            if (null === callback) {
                return;
            }

            callback(x.response || {});
        };

        x.open(method, uri);
        x.setRequestHeader("X-Requested-With", "XMLHttpRequest");

        if (data !== null && typeof data !== "string") {
            x.setRequestHeader("Content-Type", "application/json");
            data = JSON.stringify(data);
        }

        x.send(data);

        xHttp.requests[id] = x;
        return id;
    }
};
