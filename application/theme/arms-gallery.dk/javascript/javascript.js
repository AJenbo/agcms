function init() {
    shoppingCart.onupdate();
    openForigenLinksInNewWindow();

    $("container").style.top = $("submenu").offsetTop + $("submenu").offsetHeight + "px";
    var activmenu = $("activmenu");
    if (activmenu) {
        $("menu").scrollTop = activmenu.offsetTop;
    }
}

shoppingCart.onupdate = function() {
    var itemCount = shoppingCart.getCart().items.length;
    document.getElementById("count").firstChild.data = itemCount;
    document.getElementById("cartCount").innerText = itemCount ? "(" + itemCount + ")" : "";
};

function $(id) {
    return document.getElementById(id);
}

var krav;
function openkrav(url) {
    var left = (screen.width - 512) / 2;
    var top = (screen.height - 395) / 2;
    krav = window.open(url, "krav", "scrollbars=1,toolbar=0,width=512,height=395,left = " + left + ",top = " + top);
    return false;
}
