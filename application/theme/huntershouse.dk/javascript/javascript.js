function init() {
    shoppingCart.onupdate();
    openForigenLinksInNewWindow();

    var activmenu = document.getElementById("activmenu");
    if (activmenu) {
        document.getElementById("menu").scrollTop = activmenu.offsetTop;
    }
}

shoppingCart.onupdate = function() {
    var itemCount = shoppingCart.getCart().items.length;
    document.getElementById("count").firstChild.data = itemCount;
    document.getElementById("cartCount").innerText = itemCount ? "(" + itemCount + ")" : "";
};
