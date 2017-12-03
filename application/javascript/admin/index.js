var contextMenuActiveSide;
var contextMenuInactiveSide;
var contextMenuActiveKatContextMenu;
var contextMenuInactiveKatContextMenu;
var contextMenuListOrderContextMenu;

function attachContextMenus() {
    contextMenuActiveSide = new Proto.Menu({
        "selector": "#kat0content .side", // context menu will be shown when element with class of "side" is clicked
        "className": "menu desktop", // this is a class which will be attached to menu container (used for css styling)
        "menuItems": activeSideContextMenu // array of menu items
    });
    contextMenuInactiveSide = new Proto.Menu(
        {"selector": "#kat-1content .side", "className": "menu desktop", "menuItems": inactiveSideContextMenu});
    contextMenuActiveKatContextMenu = new Proto.Menu(
        {"selector": "#kat0content .kat", "className": "menu desktop", "menuItems": activeKatContextMenu});
    contextMenuInactiveKatContextMenu = new Proto.Menu(
        {"selector": "#kat-1content .kat", "className": "menu desktop", "menuItems": inactiveKatContextMenu});
    contextMenuListOrderContextMenu =
        new Proto.Menu({"selector": "#listOrder li", "className": "menu desktop", "menuItems": listOrderContextMenu});
}

function reattachContextMenus() {
    contextMenuActiveSide.reattach();
    contextMenuInactiveSide.reattach();
    contextMenuActiveKatContextMenu.reattach();
    contextMenuInactiveKatContextMenu.reattach();
    contextMenuListOrderContextMenu.reattach();
}

function getNodeFromContextMenuEvent(e) {
    if (e.element().tagName.toLowerCase() === "a") {
        return e.target;
    }
    return e.target.parentNode;
}

// TODO only for getSiteTree
var sideContextMenu = [{
    "name": "Rediger",
    "className": "edit",
    "callback": function(e) {
        location.href = "/admin/page/" + getNodeFromContextMenuEvent(e).parentNode.className.replace(/^side/, "") + "/";
    }
}];
var activeSideContextMenu = sideContextMenu.slice(0);
activeSideContextMenu.push({
    "name": "Fjern",
    "className": "unlink",
    "callback": function(e) {
        var element = getNodeFromContextMenuEvent(e).parentNode;
        var name = "";
        if (element.childNodes.length > 1) {
            name = element.parentNode.previousSibling.lastChild.nodeValue.trim();
        }
        var ids = element.id.match(/\d+/g);
        removeBinding(name, ids[1], ids[0], bindTree_r);
    }
});
var inactiveSideContextMenu = sideContextMenu.slice(0);
inactiveSideContextMenu.push({
    "name": "Slet",
    "className": "delete",
    "callback": function(e) {
        var element = getNodeFromContextMenuEvent(e).parentNode;
        var name = "";
        if (element.childNodes.length > 1) {
            name = element.firstChild.childNodes[1].nodeValue.trim();
        }
        deletePage(name, element.className.replace(/^side/, ""));
    }
});

var katContextMenu = [
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      "callback": function(e) {
          var element = getNodeFromContextMenuEvent(e);
          renameCategory(element.parentNode.id.replace(/^kat/, ""), element.lastChild.nodeValue.trim());
      }
    },
    {
      "name": "Rediger",
      "className": "edit",
      "callback": function(e) {
          location.href = "/admin/categories/" + getNodeFromContextMenuEvent(e).parentNode.id.replace(/^kat/, "") + "/";
      }
    }
];
var activeKatContextMenu = katContextMenu.slice(0);
activeKatContextMenu.push({
    "name": "fjern",
    "className": "unlink",
    "callback": function(e) {
        var element = getNodeFromContextMenuEvent(e);
        console.log(element);
        moveCategory(element.lastChild.nodeValue.trim(), element.parentNode.id.replace(/^kat/, ""), -1, true);
    }
});
var inactiveKatContextMenu = katContextMenu.slice(0);
inactiveKatContextMenu.push({
    "name": "Slet",
    "className": "delete",
    "callback": function(e) {
        var element = getNodeFromContextMenuEvent(e);
        console.log(element);
        deleteCategory(element.lastChild.nodeValue.trim(), element.parentNode.id.replace(/^kat/, ""));
    }
});

// TODO only for listorder
var listOrderContextMenu = [{
    "name": "Slet",
    "className": "delete",
    "callback": function(e) {
        e.target.parentNode.removeChild(e.target);
    }
}];

function sogogerstat(sog, erstat) {
    if (confirm("Dette vil søge og erstatte i al tekst på hele siden, vil du forsætte?")) {
        $("loading").style.visibility = "";
        x_sogogerstat(sog, erstat, searchAndReplaceCallback);
    }
}

function searchAndReplaceCallback(affected_rows) {
    if (!genericCallback(data)) {
        return;
    }
    alert("Påvirket sider: " + affected_rows + ".");
}

function displaySubMenus(state) {
    if (state == "1") {
        $("subMenus").style.display = "";
        return;
    }
    $("subMenus").style.display = "none";
}

function updateKat(id) {
    $("loading").style.visibility = "";

    var data = {
        "title": $("navn").value,
        "parentId": getRadio("kat"),
        "icon_id": parseInt($("icon_id").value) || null,
        "render_mode": parseInt($("vis").value),
        "email": $("email").value
    };

    if (!id) {
        xHttp.request("/admin/categories/", save_ny_kat_r, "POST", data);
        return false;
    }

    data.weightedChildren = $("custom_sort_subs").value === "1";
    data.subMenusOrder = $("subMenusOrder").value;

    xHttp.request("/admin/categories/" + id + "/", genericCallback, "PUT", data);
    return false;
}

function updatemaerke(id) {
    $("loading").style.visibility = "";

    var data = {
        "title": document.getElementById("navn").value,
        "link": document.getElementById("link").value,
        "iconId": parseInt(document.getElementById("icon_id").value) || null
    };

    if (!id) {
        xHttp.request("/admin/brands/", updatemaerke_r, "POST", data);

        return false;
    }

    xHttp.request("/admin/brands/" + id + "/", genericCallback, "PUT", data);

    return false;
}

var saveRequest = null;
function updateSide(id) {
    $("loading").style.visibility = "";

    var page = {
        "title": $("navn").value,
        "keywords": $("keywords").value,
        "excerpt": $("beskrivelse").value,
        "html": CKEDITOR.instances.text.getData(),
        "sku": $("varenr").value,
        "iconId": parseInt($("icon_id").value) || null,
        "requirementId": parseInt(getSelectValue("krav")) || null,
        "brandId": parseInt(getSelectValue("maerke")) || null,
        "price": parseInt($("pris").value) || 0,
        "oldPrice": parseInt($("for").value) || 0,
        "priceType": parseInt(getSelectValue("fra")),
        "oldPriceType": parseInt(getSelectValue("burde"))
    };

    xHttp.cancel(saveRequest);
    if (!id) {
        page.categoryId = parseInt(getRadio("kat"));
        saveRequest = xHttp.request("/admin/page/", opretSide_r, "POST", page);

        return false;
    }

    saveRequest = xHttp.request("/admin/page/" + id + "/", genericCallback, "PUT", page);

    return false;
}

function opretSide_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    window.location.href = "/admin/page/" + data.id + "/";
}

function updateSpecial(id) {
    $("loading").style.visibility = "";
    if ($("subMenusOrder")) {
        x_updateKatOrder($("subMenusOrder").value, genericCallback);
    }

    var html = CKEDITOR.instances.text.getData();

    var title = $("title") ? $("title").value : "";

    x_updateSpecial(id, html, title, genericCallback);
    return false;
}

function save_ny_kat_r(data) {
    if (!genericCallback(data)) {
        return;
    }
    location.href = "/admin/sitetree/";
}

function addNewItem() {
    var text = $("newItem");
    if (text.value != "") {
        var listOrder = $("listOrder");
        var li = document.createElement("li");
        li.id = "item_" + items;
        items++;
        var textnode = document.createTextNode(text.value);
        text.value = "";
        li.appendChild(textnode);
        listOrder.insertBefore(li, listOrder.firstChild);
        Sortable.create("listOrder", {"ghosting": false, "constraint": false, "hoverclass": "over"});
    }
    return false;
}

function saveListOrder(id) {
    $("loading").style.visibility = "";
    var newListOrder = "";
    var listOrder = $("listOrder");
    for (var i = 0; i < listOrder.childNodes.length; i++) {
        if (i) {
            newListOrder += "<";
        }
        newListOrder += listOrder.childNodes[i].innerHTML;
    }
    x_saveListOrder(id, $("listOrderNavn").value, newListOrder, genericCallback);
}

function makeNewList() {
    var name = prompt("Ny liste");
    if (name != null) {
        $("loading").style.visibility = "";
        x_makeNewList(name, makeNewList_r);
    }
}

function makeNewList_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    location.href = "/admin/?side=listsort-edit&id=" + data.id;
}

var contactCountRequest;
function countEmailTo() {
    $("loading").style.visibility = "";
    var query = "";
    var interestObjs = $("interests").getElementsByTagName("input");
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            query = "interests[]=" + encodeURIComponent(interestObjs[i].value) + "&";
        }
    }

    xHttp.cancel(contactCountRequest);
    contactCountRequest = xHttp.request("/admin/addressbook/count/?" + query, countEmailTo_r);
}

function countEmailTo_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    $("mailToCount").innerText = data.count;
}

function saveEmail(callback = null, send = false) {
    $("loading").style.visibility = "";

    var data = {
        "from": $("from").value,
        "interests": [],
        "subject": $("subject").value,
        "html": CKEDITOR.instances.text.getData(),
    };

    var interestObjs = $("interests").getElementsByTagName("input");
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            data.interests.push(interestObjs[i].value);
        }
    }

    var id = parseInt($("id").value) || null;
    if (id) {
        callback = callback || genericCallback;
        data.send = send;
        xHttp.request("/admin/newsletters/" + id + "/", callback, "PUT", data);
        return;
    }

    xHttp.request("/admin/newsletters/", sendEmail_r, "POST", data);
}

function updateContact(id) {
    $("loading").style.visibility = "";
    var data = {
        "name": $("navn").value,
        "email": $("email").value,
        "address": $("adresse").value,
        "country": $("land").value,
        "postcode": $("post").value,
        "city": $("by").value,
        "phone1": $("tlf1").value,
        "phone2": $("tlf2").value,
        "newsletter": $("kartotek").value,
        "interests": [],
    };
    var interestObjs = $("interests").getElementsByTagName("input");
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            data.interests.push(interestObjs[i].value);
        }
    }

    if (id) {
        saveRequest = xHttp.request("/admin/addressbook/" + id + "/", updateContact_r, "PUT", data);
        return;
    }
    saveRequest = xHttp.request("/admin/addressbook/", updateContact_r, "POST", data);
}

function updateContact_r(data) {
    if (!genericCallback(data)) {
        return;
    }
    location.href = "/admin/addressbook/list/";
}

function sendEmail() {
    if (!confirm("Ønsker du virkelig at sende denne nyhedsmail nu?")) {
        return false;
    }
    $("loading").style.visibility = "";
    var html = CKEDITOR.instances.text.getData();
    if ($("from").value == "") {
        $("loading").style.visibility = "hidden";
        alert("Du skal vælge en afsender!");
        return false;
    }
    if ($("subject").value == "") {
        $("loading").style.visibility = "hidden";
        alert("Du skal skrive et emne!");
        return false;
    }
    if (!html) {
        $("loading").style.visibility = "hidden";
        alert("Du skal skrive et tekst!");
        return false;
    }
    saveEmail(sendEmail_r, true);
    return false;
}

function sendEmail_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    location.href = "/admin/newsletters/";
}

function deleteuser(id, name) {
    if (!confirm("Do you want to delete the user '" + name + "'?")) {
        return;
    }

    xHttp.request("/admin/users/" + id + "/", reload_r, "DELETE");
}

function reload_r(data) {
    window.location.reload();
}

function updateuser(id) {
    if ($("password_new") && $("password_new").value !== $("password2").value) {
        alert("The passwords doesn't match.");
        return false;
    }

    $("loading").style.visibility = "";
    var update = {
        "access": getSelectValue("access"),
        "fullname": $("fullname") ? $("fullname").value : "",
        "password": $("password") ? $("password").value : "",
        "password_new": $("password_new") ? $("password_new").value : ""
    };
    xHttp.request("/admin/users/" + id + "/", reload_r, "PUT", update);
    return false;
}

function set_db_errors(data) {
    if (data.html) {
        $("errors").innerHTML = $("errors").innerHTML + data.html;
    }
}
var startTime;
function scan_db() {
    $("loading").style.visibility = "";
    $("errors").innerHTML = "";

    startTime = new Date().getTime();

    $("status").innerHTML = "Removing contacts that are missing vital information";
    xHttp.request("/admin/maintenance/contacts/empty/", maintainStep2, "DELETE");
}

function maintainStep2(data) {
    set_db_errors(data);
    $("status").innerHTML = "Searching for pages without bindings";
    xHttp.request("/admin/maintenance/pages/orphans/", maintainStep3);
}

function maintainStep3(data) {
    set_db_errors(data);
    $("status").innerHTML = "Searching for pages with illegal bindings";
    xHttp.request("/admin/maintenance/pages/mismatches/", maintainStep4);
}

function maintainStep4(data) {
    set_db_errors(data);
    $("status").innerHTML = "Searching for cirkalur linked categories";
    xHttp.request("/admin/maintenance/categories/circular/", maintainStep5);
}

function maintainStep5(data) {
    set_db_errors(data);
    $("status").innerHTML = "Checking the file names";
    xHttp.request("/admin/maintenance/files/names/", maintainStep6);
}

function maintainStep6(data) {
    set_db_errors(data);
    $("status").innerHTML = "Checking the folder names";
    xHttp.request("/admin/maintenance/files/folderNames/", maintainStep7);
}

function maintainStep7(data) {
    set_db_errors(data);
    $("status").innerHTML = "Sending delayed emails";
    xHttp.request("/admin/maintenance/emails/send/", maintainStep8, "POST");
}

function maintainStep8(data) {
    set_db_errors(data);
    $("status").innerHTML = "Getting system usage";
    xHttp.request("/admin/maintenance/usage/", maintainStep9);
}

function maintainStep9(data) {
    getUsage_r(data);
    $("status").innerHTML = "";
    $("loading").style.visibility = "hidden";
    $("errors").innerHTML = $("errors").innerHTML + "<br />" +
                            ("The scan took %d seconds.".replace(
                                /[%]d/g, Math.round((new Date().getTime() - startTime) / 1000).toString()));
}

function get_subscriptions_with_bad_emails() {
    $("loading").style.visibility = "";
    $("errors").innerHTML = "";

    var starttime = new Date().getTime();

    $("status").innerHTML = "Searching for illegal e-mail adresses";
    xHttp.request("/admin/maintenance/contacts/invalid/", set_db_errors);

    $("status").innerHTML = "";
    $("loading").style.visibility = "hidden";
    $("errors").innerHTML = $("errors").innerHTML + "<br />" +
                            ("The scan took %d seconds.".replace(
                                /[%]d/g, Math.round((new Date().getTime() - starttime) / 1000).toString()));
}

function removeNoneExistingFiles() {
    $("loading").style.visibility = "";
    $("status").innerHTML = "Removes not existing files from the database";
    x_removeNoneExistingFiles(removeNoneExistingFiles_r);
}

function removeNoneExistingFiles_r() {
    $("status").innerHTML = "Getting system usage";
    xHttp.request("/admin/maintenance/usage/", getUsage_r);
    $("status").innerHTML = "";
}

function getEmailUsage() {
    $("loading").style.visibility = "";
    $("status").innerHTML = "Getting email usage";
    xHttp.request("/admin/maintenance/emails/usage/", getEmailUsage_r);
}

function getEmailUsage_r(data) {
    $("mailboxsize").innerHTML = Math.round(data.size / 1024 / 1024 * 10) / 10 + "MB";
    $("status").innerHTML = "";
    $("loading").style.visibility = "hidden";
}

function getUsage_r(data) {
    $("wwwsize").innerHTML = Math.round(data.www / 1024 / 1024 * 10) / 10 + "MB";
    $("dbsize").innerHTML = Math.round(data.db / 1024 / 1024 * 10) / 10 + "MB";
}

function createInvoice() {
    xHttp.request("/admin/invoices/", createInvoice_r, "POST");
}

function createInvoice_r(data) {
    location.href = "/admin/invoices/" + data.id + "/";
}
