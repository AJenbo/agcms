import xHttp from "../xHttp.js";
import {genericCallback, removeTagById} from "./javascript.js";

var listlink = [];

function listInsertRowCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    var tr = document.createElement("tr");
    tr.setAttribute("id", "list_row" + data.rowid);

    var cells = $("list" + data.listid + "footer");
    cells = Array.from(cells.childNodes);
    cells.splice(-1, 1);

    var td;
    var span;
    var input;
    for (const cell of cells) {
        td = document.createElement("td");
        td.style.textAlign = cell.firstChild.style.textAlign;
        input = document.createElement("input");
        input.setAttribute("value", cell.firstChild.value);
        input.style.display = "none";
        td.appendChild(input);
        span = document.createElement("span");
        span.appendChild(document.createTextNode(cell.firstChild.value));
        td.appendChild(span);
        tr.appendChild(td);

        cell.firstChild.value = "";
    }

    td = document.createElement("td");
    td.innerHTML =
        "<img onclick=\"listEditRow(" + data.listid + ", " + data.rowid +
        ");\" src=\"/theme/default/images/admin/application_edit.png\" alt=\"Rediger\" title=\"Rediger\" width=\"16\" height=\"16\" /><img onclick=\"listUpdateRow(" +
        data.listid + ", " + data.rowid +
        ");\" style=\"display:none\" src=\"/theme/default/images/admin/disk.png\" alt=\"Rediger\" title=\"Rediger\" width=\"16\" height=\"16\" /><img src=\"/theme/default/images/admin/cross.png\" alt=\"X\" title=\"Slet række\" onclick=\"listRemoveRow(" +
        data.listid + ", " + data.rowid + ")\" />";
    tr.appendChild(td);

    var rows = $("list" + data.listid + "rows");
    rows.appendChild(tr);
}

function listSaveRow(row, listid) {
    var data = {
        "cells": [],
        "link": null,
    };

    var cells = Array.from(row.childNodes);
    cells.splice(-1, 1);
    if (listlink[listid] === true) {
        data.link = cells.splice(-1, 1)[0].firstChild.value || null;
    }

    for (const cell of cells) {
        data.cells.push(cell.firstChild.value);
    }

    return data;
}

function listInsertRow(listid) {
    var footer = $("list" + listid + "footer");
    var data = listSaveRow(footer, listid);
    xHttp.request("/admin/tables/" + listid + "/row/", listInsertRowCallback, "POST", data);
}

function listUpdateRowCallback(data) {
    if (!genericCallback(data)) {
        return;
    }
    var cells = $("list_row" + data.rowid);

    cells.lastChild.childNodes[0].style.display = "";
    cells.lastChild.childNodes[1].style.display = "none";
    cells.lastChild.childNodes[2].style.display = "";

    cells = Array.from(cells.childNodes);
    cells.splice(-1, 1);

    for (const cell of cells) {
        if (typeof(cell.lastChild.textContent) === "string") {
            cell.lastChild.textContent = cell.firstChild.value;
        } else {
            cell.lastChild.innerText = cell.firstChild.value;
        }

        cell.lastChild.style.display = "";
        cell.firstChild.style.display = "none";
    }

    var rows = $("list" + data.listid + "rows");
    for (const row of rows.childNodes) {
        row.lastChild.style.display = "";
    }
    $("list" + data.listid + "footer").style.display = "";
}

function listUpdateRow(listid, rowid) {
    var row = $("list_row" + rowid);
    var data = listSaveRow(row, listid);
    xHttp.request("/admin/tables/" + listid + "/row/" + rowid + "/", listUpdateRowCallback, "PUT", data);
}

function listEditRow(listid, rowid) {
    // Prevent adding new rows
    $("list" + listid + "footer").style.display = "none";

    // Prevent editing other rows
    var rows = $("list" + listid + "rows");
    rows = Array.from(rows.childNodes);
    for (const row of rows) {
        row.lastChild.style.display = "none";
    }

    var cells = $("list_row" + rowid);
    // Display save button
    cells.lastChild.style.display = "";
    cells.lastChild.childNodes[0].style.display = "none";
    cells.lastChild.childNodes[1].style.display = "";
    cells.lastChild.childNodes[2].style.display = "none";

    // Display row editing tools
    cells = Array.from(cells.childNodes);
    cells.splice(-1, 1);
    for (const cell of cells) {
        // Set input width to the same as table cell
        cell.firstChild.style.width = cell.clientWidth - 2 + "px";
    }
    for (const cell of cells) {
        // Swap text for input
        cell.lastChild.style.display = "none";
        cell.firstChild.style.display = "";
    }
}

/**
 * Set with on input elements and display them late to avoid them affecting column width.
 *
 * @param {number} listid
 */
function listSizeFooter(listid) {
    var cells = $("list" + listid + "footer");
    cells = Array.from(cells.childNodes);
    cells.splice(-1, 1);

    for (const cell of cells) {
        cell.firstChild.style.width = cell.clientWidth + "px";
    }

    for (const cell of cells) {
        cell.firstChild.style.display = "";
    }
}

function listRemoveRowCallback(data) {
    if (!genericCallback(data)) {
        return;
    }
    removeTagById($("list_row" + data.rowid));
}

function listRemoveRow(listid, rowid) {
    if (confirm("Vil du virkelig slette denne linje.")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/tables/" + listid + "/row/" + rowid + "/", listRemoveRowCallback, "DELETE");
    }
}

export {listlink, listInsertRow, listUpdateRow, listEditRow, listSizeFooter, listRemoveRow};
