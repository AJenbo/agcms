const shoppingCart = {
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
        "deleveryMethod": "",
        "newsletter": false,
    },
    "getCart": function() {
        const cart = localStorage.getItem("cart");
        if (!cart) {
            return shoppingCart.default;
        }

        return JSON.parse(cart);
    },
    "setCart": function(cart) {
        localStorage.setItem("cart", JSON.stringify(cart));
    },
    "resetCart": function() {
        const cart = shoppingCart.getCart();
        cart.items = [];
        shoppingCart.setCart(cart);
        shoppingCart.onupdate();
    },
    "addItem": function(type, id) {
        const cart = shoppingCart.getCart();

        for (const item of cart.items) {
            if (item.type === type && item.id === id) {
                item.quantity++;
                shoppingCart.setCart(cart);
                return;
            }
        }

        cart.items.push({
            type,
            id,
            "quantity": 1,
        });

        shoppingCart.setCart(cart);
        shoppingCart.onupdate();
    },
    "open": function() {
        const cart = shoppingCart.getCart();
        document.location.href = "/order/?cart=" + encodeURIComponent(JSON.stringify(cart));
    },
    "openAddress": function() {
        const cart = shoppingCart.getCart();
        cart.payMethod = document.getElementById("payMethod").value;
        cart.deleveryMethod = document.getElementById("deleveryMethod").value;
        cart.note = document.getElementById("note").value;

        let quantity = 0;
        const quantities = document.getElementsByName("quantity[]");
        const items = [];
        for (let i = 0; i < quantities.length; i++) {
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
        const cart = shoppingCart.getCart();
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

        const form = document.createElement("form");
        form.setAttribute("method", "POST");
        form.setAttribute("action", "/order/send/");

        const hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "cart");
        hiddenField.setAttribute("value", JSON.stringify(cart));

        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
    },
    "onupdate": function() {
        const itemCount = shoppingCart.getCart().items.length;
        const mobileCart = document.getElementById("count");
        if (mobileCart && mobileCart.firstChild) {
            mobileCart.firstChild.data = itemCount;
        }
        document.getElementById("cartCount").innerText = itemCount ? "(" + itemCount + ")" : "";
    }
};

export default shoppingCart;
