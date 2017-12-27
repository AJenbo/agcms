var invoiceLines = [];
var invoiceAmount = 0;
var validemailajaxcall;
var lastemail;

function redirectToInvoice(data) {
    window.location.href = "/admin/invoices/" + data.id + "/";
}

function copytonew(id) {
    $("loading").style.visibility = "";
    xHttp.request("/admin/invoices/" + id + "/clone/", redirectToInvoice, "POST");
    return false;
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
    td.style.border = "0";
    td.style.fontWeight = "bold";
    td.innerHTML =
        "<a href=\"#\" onclick=\"return removeRow(this)\"><img alt=\"X\" src=\"/theme/default/images/admin/cross.png\" height=\"16\" width=\"16\" title=\"Remove Line\" /></a>";
    tr.appendChild(td);
    $("vareTable").appendChild(tr);
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

        totals[i].innerText = "";
        if (total !== 0) {
            totals[i].innerText = numberFormat(total);
        }

        netto += premoms ? (total / 1.25) : total;

        if (quantity || titles[i].value !== "" || value) {
            invoiceLines.push({quantity, "title": titles[i].value, value});
        }
    }

    $("netto").innerText = numberFormat(netto);

    $("moms").innerText = numberFormat(netto * momssats);

    var fragt = parseFloat($("fragt").value.replace(/[^-0-9,]/g, "").replace(/,/, "."));
    if (isNaN(fragt)) {
        fragt = 0;
    }

    payamount = parseFloat(fragt + netto + netto * momssats);
    $("payamount").innerText = numberFormat(payamount);
    invoiceAmount = payamount.toFixed(2);

    if (!quantities.length || quantities[quantities.length - 1].value !== ""
        || titles[titles.length - 1].value !== "" || values[values.length - 1].value !== "") {
        addRow();
    }

    return true;
}

function removeRow(row) {
    $("vareTable").removeChild(row.parentNode.parentNode);
    if ($("vareTable").childNodes.length === 0) {
        addRow();
    }
    prisUpdate();
    return false;
}

function setVisabilityForSendAction(data) {
    $("emaillink").style.display = data.isValid ? "" : "none";
    $("loading").style.visibility = "hidden";
}

function valideMail() {
    if (!$("emaillink")) {
        return;
    }

    if (!$("email").value.match("^[A-z0-9_.-]+@([A-z0-9-]+\\.)+[A-z0-9-]+$")) {
        displayInvoiceEmailAction({"isValid": false});
        return;
    }

    if ($("email").value !== lastemail || $("emaillink").style.display === "none") {
        lastemail = $("email").value;
        xHttp.cancel(validemailajaxcall);
        $("loading").style.visibility = "";
        displayInvoiceEmailAction({"isValid": false});

        validemailajaxcall = xHttp.request(
            "/admin/addressbook/validEmail/?email=" + encodeURIComponent(lastemail),
            setVisabilityForSendAction,
            "GET"
        );
    }
}

function chnageZipCode(zipcode, country, city) {
    if ($(country).value !== "DK") {
        return;
    }

    $(city).value = arrayZipcode[zipcode] || "";
}

function injectInvoiceAddress(data) {
    if (!data.error) {
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

function getInvoiceAddress(tlf) {
    $("loading").style.visibility = "";
    getAddress(tlf, injectInvoiceAddress);
}

function injectInvoiceDeliverAddress(data) {
    if (!data.error) {
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

function getAltAddress(tlf) {
    $("loading").style.visibility = "";
    getAddress(tlf, injectInvoiceDeliverAddress);
}

function numberFormat(number) {
    return number.toFixed(2).toString().replace(/\./, ",");
}

function reloadPage(date) {
    window.location.reload();
}

function pbsconfirm(id) {
    $("loading").style.visibility = "";
    // TODO save comment
    xHttp.request("/admin/invoices/payments/" + id + "/", reloadPage, "POST");
    return false;
}

function annul(id) {
    $("loading").style.visibility = "";
    // TODO save comment
    xHttp.request("/admin/invoices/payments/" + id + "/", reloadPage, "DELETE");
    return false;
}

function invoiceSaveResponse(date) {
    if (date.status !== status || date.type === "lock" || date.type === "cancel" || date.type === "giro" ||
        date.type === "cash") {
        window.location.reload();
    }

    if (date.status !== "new" && $("note").value) {
        $$(".note")[0].innerText += "\n" + $("note").value;
        $("note").value = "";
    }

    $("loading").style.visibility = "hidden";
}

function save(id = null, type = null) {
    if (type === null) {
        type = "save";
    }

    if (type === "cancel" && !confirm("Are you sure you want to cancel this Invoice?")) {
        return false;
    }

    $("loading").style.visibility = "";
    var update = {};
    if (status === "new") {
        update.lines = invoiceLines;
        update.shipping = $("fragt").value.replace(/[^-0-9,]/g, "").replace(/,/, ".") || 0;
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


    if ($("clerk")) {
        update.clerk = getSelectValue("clerk");
    }
    if ($("department")) {
        update.department = getSelectValue("department");
    }
    update.note = $("note") ? $("note").value : "";
    update.internalNote = $("internalNote").value;

    if (type === "giro") {
        update.paydate = $("gdate").value;
    }

    if (type === "cash") {
        update.paydate = $("cdate").value;
    }

    update.action = type;

    if (id === null) {
        xHttp.request("/admin/invoices/", redirectToInvoice, "POST", update);
        return false;
    }

    xHttp.request("/admin/invoices/" + id + "/", invoiceSaveResponse, "PUT", update);
    return false;
}

function showReminderSendMessage(data) {
    if (!genericCallback(data)) {
        return;
    }

    alert("Em påmindelse er blevet sendt til kunden.");
}

function sendReminder(id) {
    xHttp.request("/admin/invoices/" + id + "/email/", showReminderSendMessage, "POST");
    return false;
}

function showhidealtpost(status) {
    var rows = $$(".altpost");
    for (const row of rows) {
        row.style.display = status ? "" : "none";
    }
}
