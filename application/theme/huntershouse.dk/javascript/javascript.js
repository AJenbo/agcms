function init() {
    shoppingCart.onupdate();
    var links = document.getElementsByTagName("a");
    for (var i = 0; i < links.length; i++) {
        if (links[i].hostname !== location.hostname) {
            links[i].setAttribute("target", "_blank");
            links[i].setAttribute("title", "Åbner i et nyt vindu");
        }
    }

    var activmenu = $("activmenu");
    if (activmenu) {
        $("menu").scrollTop = activmenu.offsetTop;
    }
}

function $(id) {
    return document.getElementById(id);
}

shoppingCart.onupdate = function() {
    var itemCount = shoppingCart.getCart().items.length;
    $("count").firstChild.data = itemCount;
    $("cartCount").innerText = itemCount ? "(" + itemCount + ")" : "";
};
