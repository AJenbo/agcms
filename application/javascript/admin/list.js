var listlink = [];

function listInsertRow_r(data) {
    if (!genericCallback(data)) {
        return;
    }
    var footer = $("list" + data.listid + "footer");

    var tr = document.createElement("tr");
    tr.setAttribute("id", "list_row" + data.rowid);

    var td;
    var span;
    var input;
    for (i = 0; i < footer.childNodes.length - 1; i++) {
        td = document.createElement("td");
        td.style.textAlign = footer.childNodes[i].firstChild.style.textAlign;
        input = document.createElement("input");
        input.setAttribute("value", footer.childNodes[i].firstChild.value);
        input.style.display = "none";
        td.appendChild(input);
        span = document.createElement("span");
        span.appendChild(document.createTextNode(footer.childNodes[i].firstChild.value));
        td.appendChild(span);
        tr.appendChild(td);

        footer.childNodes[i].firstChild.value = "";
    }

    td = document.createElement("td");
    td.innerHTML =
        "<img onclick=\"listEditRow(" + data.listid + ", " + data.rowid +
        ");\" src=\"/theme/default/images/admin/application_edit.png\" alt=\"Rediger\" title=\"Rediger\" width=\"16\" height=\"16\" /><img onclick=\"listUpdateRow(" +
        data.listid + ", " + data.rowid +
        ");\" style=\"display:none\" src=\"/theme/default/images/admin/disk.png\" alt=\"Rediger\" title=\"Rediger\" width=\"16\" height=\"16\" /><img src=\"/theme/default/images/admin/cross.png\" alt=\"X\" title=\"Slet række\" onclick=\"listRemoveRow(" +
        data.listid + ", " + data.rowid + ")\" />";
    tr.appendChild(td);

    rows = $("list" + data.listid + "rows");
    rows.appendChild(tr);
}

function listInsertRow(listid) {
    var footer = $("list" + listid + "footer");
    var data = listSaveRow(footer, listid);
    xHttp.request("/admin/tables/" + listid + "/row/", listInsertRow_r, "POST", data);
}

function listUpdateRow(listid, rowid) {
    var row = $("list_row" + rowid);
    var data = listSaveRow(row, listid);
    xHttp.request("/admin/tables/" + listid + "/row/" + rowid + "/", listUpdateRow_r, "PUT", data);
}

function listSaveRow(row, listid) {
    var data = {
        "cells": [],
        "link": null,
    };

    var cellcount = row.childNodes.length - 1;
    if (listlink[listid] === true) {
        cellcount -= 1;
        data.link = row.childNodes[cellcount].firstChild.value || null;
    }

    for (i = 0; i < cellcount; i++) {
        data.cells.push(row.childNodes[i].firstChild.value);
    }

    return data;
}

function listEditRow(listid, rowid) {
    $("list" + listid + "footer").style.display = "none";

    var row = $("list_row" + rowid);

    for (var i = 0; i < row.childNodes.length - 1; i++) {
        row.childNodes[i].firstChild.style.width = row.childNodes[i].clientWidth - 2 + "px";
    }
    for (i = 0; i < row.childNodes.length - 1; i++) {
        row.childNodes[i].lastChild.style.display = "none";
        row.childNodes[i].firstChild.style.display = "";
    }

    rows = $("list" + listid + "rows");
    for (i = 0; i < rows.childNodes.length; i++) {
        rows.childNodes[i].childNodes[row.childNodes.length - 1].style.display = "none";
    }
    row.childNodes[row.childNodes.length - 1].style.display = "";

    row.childNodes[row.childNodes.length - 1].firstChild.style.display = "none";
    row.childNodes[row.childNodes.length - 1].childNodes[1].style.display = "";
    row.childNodes[row.childNodes.length - 1].childNodes[2].style.display = "none";
    // Firefox scrolls :(
    row.firstChild.firstChild.focus();
}

function listUpdateRow_r(data) {
    if (!genericCallback(data)) {
        return;
    }
    var row = $("list_row" + data.rowid);

    for (i = 0; i < row.childNodes.length - 1; i++) {
        if (typeof(row.childNodes[i].lastChild.textContent) === "string") {
            row.childNodes[i].lastChild.textContent = row.childNodes[i].firstChild.value;
        } else {
            row.childNodes[i].lastChild.innerText = row.childNodes[i].firstChild.value;
        }

        row.childNodes[i].lastChild.style.display = "";
        row.childNodes[i].firstChild.style.display = "none";
    }

    row.childNodes[row.childNodes.length - 1].firstChild.style.display = "";
    row.childNodes[row.childNodes.length - 1].childNodes[1].style.display = "none";
    row.childNodes[row.childNodes.length - 1].childNodes[2].style.display = "";

    rows = $("list" + data.listid + "rows");
    for (i = 0; i < rows.childNodes.length; i++) {
        rows.childNodes[i].childNodes[row.childNodes.length - 1].style.display = "";
    }
    $("list" + data.listid + "footer").style.display = "";
}

function listSizeFooter(listid) {
    var row = $("list" + listid + "footer");

    for (var i = 0; i < row.childNodes.length - 1; i++) {
        row.childNodes[i].firstChild.style.width = row.childNodes[i].clientWidth + "px";
    }
    for (i = 0; i < row.childNodes.length - 1; i++) {
        row.childNodes[i].lastChild.style.display = "none";
        row.childNodes[i].firstChild.style.display = "";
    }
}

function listRemoveRow(listid, rowid) {
    if (confirm("Vil du virkelig slette denne linje.")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/tables/" + listid + "/row/" + rowid + "/", listRemoveRow_r, "DELETE");
    }
}

function listRemoveRow_r(data) {
    if (!genericCallback(data)) {
        return;
    }
    removeTagById($("list_row" + data.rowid));
}
