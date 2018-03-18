import "babel-polyfill";
import xHttp from "./xHttp.js";
import {getAddress, changeZipCode} from "./getAddress.js";
import shoppingCart from "./shoppingCart.js";

let getTableCall = null;
let getKatCall = null;
let values = [];

/**
 * Make all links to external pages open in a new window, and set a tool tip as to thint this to the user.
 */
function openForigenLinksInNewWindow() {
    const links = document.getElementsByTagName("a");
    for (const link of links) {
        if (link.hostname !== location.hostname) {
            link.setAttribute("target", "_blank");
            link.setAttribute("title", "Åbner i et nyt vindu");
        }
    }
}

/**
 * Short hand for getElementById.
 */
function $(id) {
    return document.getElementById(id);
}

function injectHtml(data) {
    if (data.error) {
        return;
    }

    document.getElementById(data.id).innerHTML = data.html;
}

function getTable(categoryId, tableId, cell) {
    xHttp.cancel(getTableCall);
    getTableCall = xHttp.request("/ajax/category/" + categoryId + "/table/" + tableId + "/" + cell, injectHtml);
    return false;
}

function getKat(categoryId, column) {
    xHttp.cancel(getKatCall);
    getKatCall = xHttp.request("/ajax/category/" + categoryId + "/" + column, injectHtml);
    return false;
}

/**
 * Update total order price on order page.
 */
function updatePrice() {
    let value = 0;
    let subtotal = 0;
    const total = document.getElementsByClassName("total");
    const input = document.getElementsByName("quantity[]");
    for (let i = 0; i < values.length; i++) {
        value = values[i] * input[i].value;
        if (values[i] > 0) {
            total[i].innerText = value.toFixed(2).toString().replace(/\./, ",");
        }
        subtotal = subtotal + value;
    }

    document.getElementById("total").innerText = subtotal.toFixed(2).toString().replace(/\./, ",");
}

function setShippingAddressVisability(status) {
    const rows = document.getElementsByTagName("tr");
    for (const row of rows) {
        if (row.className === "altpost") {
            row.style.display = status ? "" : "none";
        }
    }
}

/**
 * Open the payment process from manual input.
 */
function openPayment() {
    document.location.href = "/betaling/" + document.getElementById("id").value + "/" +
                             encodeURIComponent(document.getElementById("checkid").value) + "/";
    return false;
}

function getAddressCallback1(responce) {
    if (responce.error) {
        return;
    }
    if (responce.name) {
        document.getElementById("name").value = responce.name;
    }
    if (responce.attn) {
        document.getElementById("attn").value = responce.attn;
    }
    if (responce.address1) {
        document.getElementById("address").value = responce.address1;
    }
    if (responce.postbox) {
        document.getElementById("postbox").value = responce.postbox;
    }
    if (responce.zipcode) {
        document.getElementById("postcode").value = responce.zipcode;
    }
    if (responce.email) {
        document.getElementById("email").value = responce.email;
    }
    changeZipCode(responce.zipcode, "country", "city");
}

function getAddressCallback2(responce) {
    if (responce.error) {
        return;
    }
    if (responce.name) {
        document.getElementById("shippingName").value = responce.name;
    }
    if (responce.attn) {
        document.getElementById("shippingAttn").value = responce.attn;
    }
    if (responce.address1) {
        document.getElementById("shippingAddress").value = responce.address1;
    }
    if (responce.address2) {
        document.getElementById("shippingAddress2").value = responce.address2;
    }
    if (responce.postbox) {
        document.getElementById("shippingPostbox").value = responce.postbox;
    }
    if (responce.zipcode) {
        document.getElementById("shippingPostcode").value = responce.zipcode;
    }
    changeZipCode(responce.zipcode, "shippingCountry", "shippingCity");
}

window.addEventListener("DOMContentLoaded", function(event) {
    window.$ = $;
    window.getTable = getTable;
    window.getKat = getKat;
    window.updatePrice = updatePrice;
    window.setShippingAddressVisability = setShippingAddressVisability;
    window.openPayment = openPayment;

    // Expose imported functions
    window.shoppingCart = shoppingCart;
    window.getAddress = getAddress;
    window.getAddressCallback1 = getAddressCallback1;
    window.getAddressCallback2 = getAddressCallback2;
    window.changeZipCode = changeZipCode;

    values = window.values;

    shoppingCart.onupdate();
    openForigenLinksInNewWindow();
});
