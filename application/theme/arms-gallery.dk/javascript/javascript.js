function init() {
    shoppingCart.onupdate();
    openForigenLinksInNewWindow();

    var subMenu = document.getElementById("submenu");
    document.getElementById("container").style.top = subMenu.offsetTop + subMenu.offsetHeight + "px";
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

var krav;
function openkrav(url) {
    var left = (screen.width - 512) / 2;
    var top = (screen.height - 395) / 2;
    krav = window.open(url, "krav", "scrollbars=1,toolbar=0,width=512,height=395,left = " + left + ",top = " + top);
    return false;
}
