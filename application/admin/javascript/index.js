var contextMenuActiveSide;
var contextMenuInactiveSide;
var contextMenuActiveKatContextMenu;
var contextMenuInactiveKatContextMenu;
var contextMenuListOrderContextMenu;

function attachContextMenus() {
    contextMenuActiveSide = new Proto.Menu({
        selector: '#kat0content .side', // context menu will be shown when element with class of "side" is clicked
        className: 'menu desktop', // this is a class which will be attached to menu container (used for css styling)
        menuItems: activeSideContextMenu // array of menu items
    })
    contextMenuInactiveSide = new Proto.Menu({
        selector: '#kat-1content .side',
        className: 'menu desktop',
        menuItems: inactiveSideContextMenu
    })
    contextMenuActiveKatContextMenu = new Proto.Menu({
        selector: '#kat0content .kat',
        className: 'menu desktop',
        menuItems: activeKatContextMenu
    })
    contextMenuInactiveKatContextMenu = new Proto.Menu({
        selector: '#kat-1content .kat',
        className: 'menu desktop',
        menuItems: inactiveKatContextMenu
    })
    contextMenuListOrderContextMenu = new Proto.Menu({
        selector: '#listOrder li',
        className: 'menu desktop',
        menuItems: listOrderContextMenu
    })
}

function htmlspecialchars(string)
{
    return string.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function reattachContextMenus()
{
    contextMenuActiveSide.reattach();
    contextMenuInactiveSide.reattach();
    contextMenuActiveKatContextMenu.reattach();
    contextMenuInactiveKatContextMenu.reattach();
    contextMenuListOrderContextMenu.reattach();
}

//TODO only for getSiteTree
var activeSideContextMenu = [
    {
        name: 'Rediger',
        className: 'edit',
        //TODO update to use getContextMenuTarget()
        callback: function(e) {
            if (e.element().tagName.toLowerCase() == 'a') {
                location.href='?side=redigerside&id='+e.target.parentNode.className.replace(/^side/, '');
            } else {
                location.href='?side=redigerside&id='+e.target.parentNode.parentNode.className.replace(/^side/, '');
            }
        }
    },
    {
        name: 'Fjern',
        className: 'unlink',
        callback: function(e) {
            //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                //todo the respoce woun't fit here
                slet('bindtree', e.target.parentNode.parentNode.previousSibling.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.id.replace(/^bind/, ''));
            } else {
                //todo the respoce woun't fit here
                slet('bindtree', e.target.parentNode.parentNode.parentNode.previousSibling.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.parentNode.id.replace(/^bind/, ''));
            }
        }
    }
]

//TODO only for getSiteTree
var inactiveSideContextMenu = [
    {
        name: 'Rediger',
        className: 'edit',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                location.href='?side=redigerside&id='+e.target.parentNode.className.replace(/^side/, '');
            } else {
                location.href='?side=redigerside&id='+e.target.parentNode.parentNode.className.replace(/^side/, '');
            }
        }
    },
    {
        name: 'Slet',
        className: 'delete',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
        if (e.element().tagName.toLowerCase() == 'a') {
                slet('side', e.target.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.className.replace(/^side/, ''));
            } else {
                slet('side', e.target.parentNode.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.parentNode.className.replace(/^side/, ''));
            }
        }
    }
]

//TODO only for getSiteTree
var activeKatContextMenu = [
    {
        name: 'Omdøb',
        className: 'textfield_rename',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                renamekat(e.target.parentNode.id.replace(/^kat/, ''), e.target.lastChild.nodeValue.replace(/^\s+/, ''));
            } else {
                renamekat(e.target.parentNode.parentNode.id.replace(/^kat/, ''), e.target.parentNode.lastChild.nodeValue.replace(/^\s+/, ''));
            }
        }
    },
        {
        name: 'Rediger',
        className: 'edit',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                location.href='?side=redigerkat&id='+e.target.parentNode.id.replace(/^kat/, '');
            } else {
                location.href='?side=redigerkat&id='+e.target.parentNode.parentNode.id.replace(/^kat/, '');
            }
        }
    },
    {
        name: 'fjern',
        className: 'unlink',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                movekat(e.target.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.id.replace(/^kat/, ''), -1, true);
            } else {
                movekat(e.target.parentNode.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.parentNode.id.replace(/^kat/, ''), -1, true);
            }
        }
    }
]


//TODO only for getSiteTree
var inactiveKatContextMenu = [
    {
        name: 'Omdøb',
        className: 'textfield_rename',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                renamekat(e.target.parentNode.id.replace(/^kat/, ''), e.target.lastChild.nodeValue.replace(/^\s+/, ''));
            } else {
                renamekat(e.target.parentNode.parentNode.id.replace(/^kat/, ''), e.target.parentNode.lastChild.nodeValue.replace(/^\s+/, ''));
            }
        }
    },
    {
        name: 'Rediger',
        className: 'edit',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                location.href='?side=redigerkat&id='+e.target.parentNode.id.replace(/^kat/, '');
            } else {
                location.href='?side=redigerkat&id='+e.target.parentNode.parentNode.id.replace(/^kat/, '');
            }
        }
    },
    {
        name: 'Slet',
        className: 'delete',
        callback: function(e) {
        //TODO update to use getContextMenuTarget()
            if (e.element().tagName.toLowerCase() == 'a') {
                slet('kat', e.target.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.id.replace(/^kat/, ''));
            } else {
                slet('kat', e.target.parentNode.lastChild.nodeValue.replace(/^\s+/, ''), e.target.parentNode.parentNode.id.replace(/^kat/, ''));
            }
        }
    }
]

//TODO only for listorder
var listOrderContextMenu = [
    {
        name: 'Slet',
        className: 'delete',
        callback: function(e) {
            e.target.parentNode.removeChild(e.target);
        }
    }
]

function sogogerstat(sog, erstat) {
    if (confirm('Dette vil søge og erstatte i al tekst på hele siden, vil du forsætte?')==true){
        $('loading').style.visibility = '';
        x_sogogerstat(sog, erstat, sogogerstat_r);
    }
}

function sogogerstat_r(affected_rows) {
    $('loading').style.visibility = 'hidden';
    alert('Påvirket sider: '+affected_rows+'.');
}

function displaySubMenus(state) {
    if (state == '1') {
        $('subMenus').style.display = '';
    } else {
        $('subMenus').style.display = 'none';
    }
}

function updateKat(id) {
    $('loading').style.visibility = '';

    if (!id) {
        x_save_ny_kat($('navn').value,
            getRadio('kat'),
            $('icon').value,
            $('vis').value,
            $('email').value,
            save_ny_kat_r);
        return false;
    }

    x_updateKat(id,
        $('navn').value,
        getRadio('kat'),
        $('icon').value,
        $('vis').value,
        $('email').value,
        $('custom_sort_subs').value,
        $('subMenusOrder').value,
        generic_r);
    return false;
}

function updateSide(id) {
    $('loading').style.visibility = '';
    updateRTEs();

    if (!id) {
        x_opretSide(
            parseInt(getRadio("kat")),
            $("navn").value,
            $("keywords").value,
            $("pris").value ? parseInt($("pris").value) : 0,
            $("billed").value,
            $("beskrivelse").value,
            $("for").value ? parseInt($("for").value) : 0,
            $("text").value,
            $("varenr").value,
            parseInt(getSelectValue("burde")),
            parseInt(getSelectValue("fra")),
            parseInt(getSelectValue("krav")),
            parseInt(getSelectValue("maerke")),
            opretSide_r
        );
        return false;
    }

    x_updateSide(id,
        $('navn').value,
        $('keywords').value,
        $('pris').value ? parseInt($('pris').value) : 0,
        $('billed').value,
        $('beskrivelse').value,
        $('for').value ? parseInt($('for').value) : 0,
        $('text').value,
        $('varenr').value,
        parseInt(getSelectValue('burde')),
        parseInt(getSelectValue('fra')),
        parseInt(getSelectValue('krav')),
        parseInt(getSelectValue('maerke')),
        generic_r);
    return false;
}

function opretSide_r(data) {
    if (!generic_r(data)) {
        return;
    }

    window.location.href = "?side=redigerside&id=" + data["id"];
}

function updateSpecial(id) {
    $('loading').style.visibility = '';
    if($('subMenusOrder')) {
        x_updateKatOrder($('subMenusOrder').value, generic_r);
    }
    updateRTEs();
    x_updateSpecial(id, $('text').value, generic_r);
    return false;
}

function save_ny_kat_r(data) {
    if (data['error']) {
        alert(data['error']);
    } else {
        location.href = '?side=getSiteTree';
    }
}

function addNewItem() {
    var text = $('newItem');
    if (text.value != '') {
        var listOrder = $('listOrder');
        var li = document.createElement('li');
        li.id = 'item_'+items;
        items++;
        var textnode = document.createTextNode(text.value);
        text.value = '';
        li.appendChild(textnode);
        listOrder.insertBefore(li, listOrder.firstChild);
        Sortable.create('listOrder',{ghosting:false,constraint:false,hoverclass:'over'});
    }
    return false;
}

function saveListOrder(id) {
    var newListOrder = '';
    var listOrder = $('listOrder');
    for (var i = 0; i<listOrder.childNodes.length; i++) {
        if (i) {
            newListOrder += '<';
        }
        newListOrder += listOrder.childNodes[i].innerHTML;
    }
    x_saveListOrder(id, $('listOrderNavn').value, newListOrder, generic_r);
}

function makeNewList() {
    var name = prompt('Ny liste');
    if (name != null) {
        $('loading').style.visibility = '';
        x_makeNewList(name, makeNewList_r);
    }
}

function makeNewList_r(data) {
    $('loading').style.visibility = 'hidden';
    if (data['error']) {
        alert(data['error']);
        return;
    }

    location.href = '/admin/?side=listsort-edit&id=' + data['id'];
}

function countEmailTo() {
    $('loading').style.visibility = '';
    //Cancle all othere ajax requests to avoide reponce order mix up
    //TODO only cancle requests relating to countEmailTo
    sajax.cancel();
    var interestObjs = $('interests').getElementsByTagName('input');
    var interests = [];
    for (var i=0; i<interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            interests.push(interestObjs[i].value);
        }
    }
    x_countEmailTo(interests, countEmailTo_r)
}

function countEmailTo_r(data) {
    $('loading').style.visibility = 'hidden';
    if (data['error']) {
        alert(data['error']);
    }
    $('mailToCount').innerHTML = data;
}

function saveEmail() {
    $('loading').style.visibility = '';
    updateRTEs();
    var interestObjs = $('interests').getElementsByTagName('input');
    var interests = '';
    for (var i=0; i<interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            if (interests != '') {
                interests += '<';
            }
            interests += interestObjs[i].value;
        }
    }
    x_saveEmail($('id').value, $('from').value, interests, $('subject').value, $('text').value, generic_r);
}

function updateContact(id) {
    $('loading').style.visibility = '';
    var interestObjs = $('interests').getElementsByTagName('input');
    var interests = '';
    for (var i=0; i<interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            if (interests != '') {
                interests += '<';
            }
            interests += interestObjs[i].value;
        }
    }
    x_updateContact(id, $('navn').value, $('email').value, $('adresse').value, $('land').value, $('post').value, $('by').value, $('tlf1').value, $('tlf2').value, $('kartotek').value, interests, updateContact_r);
}

function updateContact_r(data) {
    $('loading').style.visibility = 'hidden';
    if (data['error']) {
        alert(data['error']);
    } else {
        location.href = '?side=addressbook';
    }
}

function sendEmail() {
    if (!confirm('Ønsker du virkelig at sende denne nyhedsmail nu?')) {
        return false;
    }
    $('loading').style.visibility = '';
    updateRTEs();
    if ($('from').value == '') {
        $('loading').style.visibility = 'hidden';
        alert('Du skal vælge en afsender!');
        return false;
    }
    if ($('subject').value == '') {
        $('loading').style.visibility = 'hidden';
        alert('Du skal skrive et emne!');
        return false;
    }
    if ($('text').value == '') {
        $('loading').style.visibility = 'hidden';
        alert('Du skal skrive et tekst!');
        return false;
    }
    var interestObjs = $('interests').getElementsByTagName('input');
    var interests = '';
    for (var i=0; i<interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            if (interests != '') {
                interests += '<';
            }
            interests += interestObjs[i].value;
        }
    }
    x_sendEmail($('id').value, $('from').value, interests, $('subject').value, $('text').value, sendEmail_r);
}

function sendEmail_r(data) {
    if (data['error']) {
        alert(data['error']);
        $('loading').style.visibility = 'hidden';
        return;
    }

    location.href = '?side=emaillist';
}

function deleteuser(id, name)
{
    if (confirm('Do you want to delete the user \'' + name + '\'?') == true) {
        x_deleteuser(id, reload_r);
    }
}

function reload_r(data) {
    if (data['error']) {
        alert(data['error']);
    }
    window.location.reload();
}
function updateuser(id) {
    if ($('password_new') && $('password_new').value != $('password2').value) {
        alert("The passwords doesn't match.");
        return false;
    }

    $('loading').style.visibility = '';
    var update = {};
    update.access = getSelectValue('access');
    update.fullname = $('fullname') ? $('fullname').value : '';
    update.password = $('password') ? $('password').value : '';
    update.password_new = $('password_new') ? $('password_new').value : '';
    x_updateuser(id, update, reload_r);
    return false;
}
