function init() {
    shoppingCart.onupdate();
    openForigenLinksInNewWindow();

    var activmenu = document.getElementById("activmenu");
    if (activmenu) {
        document.getElementById("menu").scrollTop = activmenu.offsetTop;
    }
}
