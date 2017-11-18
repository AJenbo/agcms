function newfaktura() {
    $("loading").style.visibility = "";
    x_newfaktura(newfaktura_r);
}
function copytonew(id) {
    $("loading").style.visibility = "";
    x_copytonew(id, newfaktura_r);
}
function newfaktura_r(id) {
    window.location.href = "/admin/faktura.php?id=" + id;
}

function removeRow(row) {
    $("vareTable").removeChild(row.parentNode.parentNode);
    if ($("vareTable").childNodes.length == 0) {
        addRow();
    }
    prisUpdate();
}

function addRow() {
    var tr = document.createElement("tr");
    var td = document.createElement("td");
    td.innerHTML =
        "<input name=\"quantitie\" style=\"width:58px;\" class=\"tal\" onkeyup=\"prisUpdate()\" onchange=\"prisUpdate()\" onblur=\"prisUpdate()\" />";
    tr.appendChild(td);
    td = document.createElement("td");
    td.innerHTML =
        "<input name=\"product\" style=\"width:303px;\" onkeyup=\"prisUpdate()\" onchange=\"prisUpdate()\" onblur=\"prisUpdate()\" />";
    tr.appendChild(td);
    td = document.createElement("td");
    td.innerHTML =
        "<input name=\"value\" style=\"width:69px;\" class=\"tal\" onkeyup=\"prisUpdate()\" onchange=\"prisUpdate()\" onblur=\"prisUpdate()\" />";
    tr.appendChild(td);
    td = document.createElement("td");
    td.className = "tal total";
    tr.appendChild(td);
    td = document.createElement("td");
    td.className = "web";
    td.style.border = "0";
    td.style.fontWeight = "bold";
    td.innerHTML =
        "<a href=\"#\" onclick=\"removeRow(this); return false\"><img alt=\"X\" src=\"images/cross.png\" height=\"16\" width=\"16\" title=\"Remove Line\" /></a>";
    tr.appendChild(td);
    $("vareTable").appendChild(tr);
}

function getAddress(tlf) {
    $("loading").style.visibility = "";
    x_getAddress(tlf, getAddress_r);
}

function getAddress_r(data) {
    if (data.error) {
        alert(data.error);
    } else {
        $("navn").value = data.name;
        $("attn").value = data.attn;
        $("adresse").value = data.address1;
        $("postnr").value = data.zipcode;
        chnageZipCode(data.zipcode, "land", "by");
        $("postbox").value = data.postbox;
        if (!$("email").value) {
            $("email").value = data.email;
            valideMail();
        }
    }
    $("loading").style.visibility = "hidden";
}

function getAltAddress(tlf) {
    $("loading").style.visibility = "";
    x_getAddress(tlf, getAltAddress_r);
}

function getAltAddress_r(data) {
    if (data.error) {
        alert(data.error);
    } else {
        $("postname").value = data.name;
        $("postattn").value = data.attn;
        $("postaddress").value = data.address1;
        $("postaddress2").value = data.address2;
        $("postpostalcode").value = data.zipcode;
        chnageZipCode(data.zipcode, "postcountry", "postcity");
        $("postpostbox").value = data.postbox;
    }
    $("loading").style.visibility = "hidden";
}

function prisUpdate() {
    invoiceLines = [];
    invoiceAmount = 0;

    var titles = document.getElementsByName("product");
    var values = document.getElementsByName("value");
    var quantities = document.getElementsByName("quantitie");
    var totals = $$(".total");
    var premoms = $("premoms").checked;
    var momssats = parseFloat($("momssats").value);

    var netto = 0;
    var quantity = 0;
    var value = 0;
    var total = 0;

    for (var i = 0; i < quantities.length; i++) {
        quantity = parseInt(quantities[i].value);
        if (isNaN(quantity)) {
            quantity = 0;
        }

        value = parseFloat(parseFloat(values[i].value.replace(/[^-0-9,]/g, "").replace(/,/, ".")).toFixed(2));
        if (isNaN(value)) {
            value = 0;
        }

        total = quantity * value;

        totals[i].innerHTML = "";
        if (total != 0) {
            totals[i].innerHTML = numberFormat(total);
        }

        netto += premoms ? (total / 1.25) : total;

        if (quantity || titles[i].value !== "" || value) {
            invoiceLines.push({"quantity": quantity, "title": titles[i].value, "value": value});
        }
    }

    $("netto").innerHTML = numberFormat(netto);

    $("moms").innerHTML = numberFormat(netto * momssats);

    var fragt = parseFloat($("fragt").value.replace(/[^-0-9,]/g, "").replace(/,/, "."));
    if (isNaN(fragt)) {
        fragt = 0;
    }

    payamount = parseFloat(fragt + netto + netto * momssats);
    $("payamount").innerHTML = numberFormat(payamount);
    invoiceAmount = payamount.toFixed(2);

    if (!quantities.length || quantities[quantities.length - 1].value != "" || titles[titles.length - 1].value != "" ||
        values[values.length - 1].value != "") {
        addRow();
    }

    return true;
}

function numberFormat(number) {
    return number.toFixed(2).toString().replace(/\./, ",");
}

function pbsconfirm(id) {
    $("loading").style.visibility = "";
    // TODO save comment
    x_pbsconfirm(id, reload_r);
}

function annul(id) {
    $("loading").style.visibility = "";
    // TODO save comment
    x_annul(id, reload_r);
}

function reload_r(date) {
    if (date.error) {
        alert(date.error);
        $("loading").style.visibility = "hidden";
    }

    window.location.reload();
}

function save(id, type) {
    if (type == null) {
        type = "save";
    }

    if (type == "cancel" && !confirm("Are you sure you want to cancel this Invoice?")) {
        return false;
    }

    $("loading").style.visibility = "";
    var update = {};
    if (status === "new") {
        update.lines = invoiceLines;
        update.shipping = $("fragt").value.replace(/[^-0-9,]/g, "").replace(/,/, ".");
        update.amount = invoiceAmount;
        update.vat = $("momssats").value;
        update.preVat = $("premoms").checked;
        update.date = $("date").value;
        update.iref = $("iref").value;
        update.eref = $("eref").value;
        update.name = $("navn").value;
        update.attn = $("attn").value;
        update.address = $("adresse").value;
        update.postbox = $("postbox").value;
        update.postcode = $("postnr").value;
        update.city = $("by").value;
        update.country = $("land").value;
        update.email = $("email").value;
        update.phone1 = $("tlf1").value;
        update.phone2 = $("tlf2").value;
        update.hasShippingAddress = $("altpost").checked ? 1 : 0;
        if ($("altpost").checked) {
            update.shippingPhone = $("posttlf").value;
            update.shippingName = $("postname").value;
            update.shippingAttn = $("postattn").value;
            update.shippingAddress = $("postaddress").value;
            update.shippingAddress2 = $("postaddress2").value;
            update.shippingPostbox = $("postpostbox").value;
            update.shippingPostcode = $("postpostalcode").value;
            update.shippingCity = $("postcity").value;
            update.shippingCountry = $("postcountry").value;
        }
    }

    update.note = $("note").value;

    if ($("clerk")) {
        update.clerk = getSelectValue("clerk");
    }
    if ($("department")) {
        update.department = getSelectValue("department");
    }

    if (type == "giro") {
        update.paydate = $("gdate").value;
    }

    if (type == "cash") {
        update.paydate = $("cdate").value;
    }

    x_save(id, type, update, save_r);
}

function sendReminder(id) {
    x_sendReminder(id, sendReminder_r);
}

function sendReminder_r(data) {
    alert(data.error);
}

function save_r(date) {
    if (date.error) {
        alert(date.error);
    }

    if (date.status != status || date.type == "lock" || date.type == "cancel" || date.type == "giro" ||
        date.type == "cash") {
        window.location.reload();
    }

    if (date.status != "new" && $("note").value) {
        $$(".note")[0].innerHTML += "<br />" + htmlEncode($("note").value);
        $("note").value = "";
    }

    $("loading").style.visibility = "hidden";
}

var validemailajaxcall;
var lastemail;

function valideMail() {
    if (!$("emaillink")) {
        return;
    }

    if (!$("email").value.match("^[A-z0-9_.-]+@([A-z0-9-]+\\.)+[A-z0-9-]+$")) {
        valideMail_r(false);
        return;
    }

    if ($("email").value != lastemail || $("emaillink").style.display == "none") {
        lastemail = $("email").value;
        if (validemailajaxcall) {
            sajax.cancel(validemailajaxcall);
        }
        $("loading").style.visibility = "";
        valideMail_r(false);
        validemailajaxcall = x_valideMail($("email").value, valideMail_r);
    }
}

function valideMail_r(valideMail) {
    $("emaillink").style.display = valideMail ? "" : "none";
    $("loading").style.visibility = "hidden";
}

function showhidealtpost(status) {
    var altpostTrs = $$(".altpost");
    for (var i = 0; i < altpostTrs.length; i++) {
        altpostTrs[i].style.display = status ? "" : "none";
    }
}

function chnageZipCode(zipcode, country, city) {
    if ($(country).value !== "DK") {
        return;
    }

    $(city).value = arrayZipcode[zipcode] ? arrayZipcode[zipcode] : "";
}

var invoiceLines = [];
var invoiceAmount = 0;
