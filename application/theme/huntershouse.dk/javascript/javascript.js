function init() {
    shoppingCart.onupdate();
    openForigenLinksInNewWindow();

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
