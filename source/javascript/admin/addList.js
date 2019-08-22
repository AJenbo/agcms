import xHttp from "../xHttp.js";
import * as Sentry from '@sentry/browser';

Sentry.init({ dsn: 'https://ffe8276405e74679a38aec92752ea282@sentry.io/241257' });

function createTableCallback(data) {
    window.opener.location.reload();
    window.close();
}

function createTable() {
    var data = {
        "page_id": parseInt(document.getElementById("page_id").value),
        "title": document.getElementById("title").value,
        "columns": [],
        "order_by": parseInt(document.getElementById("dsort").value),
        "has_links": document.getElementById("link").checked
    };

    var cellsObjs = document.getElementsByName("cell");
    var cellNamesObjs = document.getElementsByName("cell_name");
    var sortsObjs = document.getElementsByName("sort");

    for (var i = 0; i < cellsObjs.length; i++) {
        if (cellsObjs[i].value === "" || cellNamesObjs[i].value === "" || sortsObjs[i].value === "") {
            continue;
        }

        data.columns.push({
            "title": cellNamesObjs[i].value.toString(),
            "type": cellsObjs[i].value.toString(),
            "sorting": sortsObjs[i].value.toString()
        });
    }

    xHttp.request("/admin/tables/", createTableCallback, "POST", data);

    return false;
}

window.addEventListener("DOMContentLoaded", function(event) {
    window.createTable = createTable;
});
