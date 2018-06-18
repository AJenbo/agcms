const xHttp = {
    "requests": [],
    "cancel": function(id) {
        if (xHttp.requests[id]) {
            xHttp.requests[id].abort();
            xHttp.requests.splice(id, 1, null);
        }
    },
    "request": function(uri, callback, method, data) {
        const id = xHttp.requests.length;

        const x = new window.XMLHttpRequest();
        x.responseType = "json";
        x.onload = function() {
            xHttp.requests[id] = null;

            if (x.status < 200 || x.status > 299 || x.response.error) {
                const message = (x.response && x.response.error && x.response.error.message) || x.statusText;
                alert("Error: " + message);
            }

            callback(x.response || {"error": {"message": x.statusText}});
        };

        x.open(method || "GET", uri);
        x.setRequestHeader("X-Requested-With", "XMLHttpRequest");

        if (data && typeof data !== "string") {
            x.setRequestHeader("Content-Type", "application/json");
            data = JSON.stringify(data);
        }

        x.send(data || null);

        xHttp.requests[id] = x;
        return id;
    }
};

export default xHttp;
