function init() {
    shoppingCart.onupdate();
    var links = document.getElementsByTagName("a");
    for (var i = 0; i < links.length; i++) {
        if (links[i].hostname !== location.hostname) {
            links[i].setAttribute("target", "_blank");
            links[i].setAttribute("title", "Åbner i et nyt vindu");
        }
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

var krav;
function openkrav(url) {
    var left = (screen.width - 512) / 2;
    var top = (screen.height - 395) / 2;
    krav = window.open(url, "krav", "scrollbars=1,toolbar=0,width=512,height=395,left = " + left + ",top = " + top);
    return false;
}
