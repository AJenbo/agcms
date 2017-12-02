var getTableCall = null;
function getTable(categoryId, tableId, cell) {
    xHttp.cancel(getTableCall);
    getTableCall = xHttp.request("/ajax/category/" + categoryId + "/table/" + tableId + "/" + cell, injectHtml);
    return false;
}

var getKatCall = null;
function getKat(categoryId, column) {
    xHttp.cancel(getKatCall);
    getKatCall = xHttp.request("/ajax/category/" + categoryId + "/" + column, injectHtml);
    return false;
}

function injectHtml(data) {
    if (data.error) {
        return;
    }

    document.getElementById(data.id).innerHTML = data.html;
}

function updateprice() {
    var total = document.getElementsByClassName("total");
    var input = document.getElementsByName("quantity[]");
    var subtotal = 0;
    for (var i = 0; i < values.length; i++) {
        value = values[i] * input[i].value;
        if (values[i] > 0) {
            total[i].innerHTML = value.toFixed(2).toString().replace(/\./, ",");
        }
        subtotal = subtotal + value;
    }

    document.getElementById("total").innerHTML = subtotal.toFixed(2).toString().replace(/\./, ",");
}

function showhidealtpost(status) {
    var Trs = document.getElementsByTagName("TR");

    if (status) {
        for (var i = 0; i < Trs.length; i++) {
            if (Trs[i].className === "altpost") {
                Trs[i].style.display = "";
            }
        }
        return;
    }

    for (var i = 0; i < Trs.length; i++) {
        if (Trs[i].className === "altpost") {
            Trs[i].style.display = "none";
        }
    }
}

function getAddress_r1(responce) {
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
    if (document.getElementById("country").value === "DK" && responce.zipcode && arrayZipcode[responce.zipcode]) {
        document.getElementById("city").value = arrayZipcode[responce.zipcode];
    }
}

function getAddress_r2(responce) {
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
    if (document.getElementById("shippingCountry").value === "DK" && responce.zipcode) {
        document.getElementById("shippingCity").value = arrayZipcode[responce.zipcode];
    }
}

function chnageZipCode(zipcode, countryid, cityid) {
    if (document.getElementById(countryid).value !== "DK") {
        return false;
    }
    if (arrayZipcode[zipcode]) {
        document.getElementById(cityid).value = arrayZipcode[zipcode];
    }
}

function openPayment() {
    document.location.href = "/betaling/" + document.getElementById("id").value + "/" +
                             encodeURIComponent(document.getElementById("checkid").value) + "/";
}

var shoppingCart = {
    "default": {
        "items": [],
        "name": "",
        "attn": "",
        "address": "",
        "postbox": "",
        "postcode": "",
        "city": "",
        "country": "DK",
        "email": "",
        "phone1": "",
        "phone2": "",
        "hasShippingAddress": false,
        "shippingPhone": "",
        "shippingName": "",
        "shippingAttn": "",
        "shippingAddress": "",
        "shippingAddress2": "",
        "shippingPostbox": "",
        "shippingPostcode": "",
        "shippingCity": "",
        "shippingCountry": "DK",
        "note": "",
        "payMethod": "",
        "deleveryMethode": "",
        "newsletter": false,
    },
    "getCart": function() {
        var cart = localStorage.getItem("cart");
        if (!cart) {
            return shoppingCart.default;
        }

        return JSON.parse(cart);
    },
    "setCart": function(cart) {
        localStorage.setItem("cart", JSON.stringify(cart));
    },
    "resetCart": function() {
        var cart = shoppingCart.getCart();
        cart.items = [];
        shoppingCart.setCart(cart);
    },
    "addItem": function(type, id) {
        var cart = shoppingCart.getCart();

        for (var i = 0; i < cart.items.length; i++) {
            if (cart.items[i].type === type && cart.items[i].id === id) {
                cart.items[i].quantity++;
                shoppingCart.setCart(cart);
                return;
            }
        }

        cart.items.push({
            "type": type,
            "id": id,
            "quantity": 1,
        });

        shoppingCart.setCart(cart);

        if (typeof shoppingCart.onupdate === "function") {
            shoppingCart.onupdate();
        }
    },
    "open": function() {
        var cart = shoppingCart.getCart();
        document.location.href = "/order/?cart=" + encodeURIComponent(JSON.stringify(cart));
    },
    "openAddress": function() {
        var cart = shoppingCart.getCart();
        cart.payMethod = document.getElementById("payMethod").value;
        cart.deleveryMethode = document.getElementById("deleveryMethode").value;
        cart.note = document.getElementById("note").value;

        var quantity = 0;
        var quantities = document.getElementsByName("quantity[]");
        var items = [];
        for (var i = 0; i < quantities.length; i++) {
            quantity = parseInt(quantities[i].value);
            if (quantity) {
                cart.items[i].quantity = quantity;
                items.push(cart.items[i]);
            }
        }
        cart.items = items;
        shoppingCart.setCart(cart);

        document.location.href = "/order/address/?cart=" + encodeURIComponent(JSON.stringify(cart));
    },
    "sendCart": function() {
        var cart = shoppingCart.getCart();
        cart.name = document.getElementById("name").value;
        cart.attn = document.getElementById("attn").value;
        cart.address = document.getElementById("address").value;
        cart.postbox = document.getElementById("postbox").value;
        cart.postcode = document.getElementById("postcode").value;
        cart.city = document.getElementById("city").value;
        cart.country = document.getElementById("country").value;
        cart.email = document.getElementById("email").value;
        cart.phone1 = document.getElementById("phone1").value;
        cart.phone2 = document.getElementById("phone2").value;
        cart.hasShippingAddress = document.getElementById("hasShippingAddress").checked;
        cart.shippingPhone = document.getElementById("shippingPhone").value;
        cart.shippingName = document.getElementById("shippingName").value;
        cart.shippingAttn = document.getElementById("shippingAttn").value;
        cart.shippingAddress = document.getElementById("shippingAddress").value;
        cart.shippingAddress2 = document.getElementById("shippingAddress2").value;
        cart.shippingPostbox = document.getElementById("shippingPostbox").value;
        cart.shippingPostcode = document.getElementById("shippingPostcode").value;
        cart.shippingCity = document.getElementById("shippingCity").value;
        cart.shippingCountry = document.getElementById("shippingCountry").value;
        cart.newsletter = document.getElementById("newsletter").checked;
        shoppingCart.setCart(cart);

        var form = document.createElement("form");
        form.setAttribute("method", "POST");
        form.setAttribute("action", "/order/send/");

        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "cart");
        hiddenField.setAttribute("value", JSON.stringify(cart));

        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
    }
};
