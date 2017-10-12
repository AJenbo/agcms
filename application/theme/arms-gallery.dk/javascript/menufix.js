function init()
{
    $('container').style.top = $('submenu').offsetTop + $('submenu').offsetHeight + 'px';
    var activmenu = $('activmenu');
    if(activmenu) {
        $('menu').scrollTop = activmenu.offsetTop;
    }
}
