import "./site.js";

window.addEventListener("DOMContentLoaded", function(event) {
    // Scroll to selected item in menu
    var activmenu = document.getElementById("activmenu");
    if (activmenu) {
        document.getElementById("menu").scrollTop = activmenu.offsetTop;
    }
});
