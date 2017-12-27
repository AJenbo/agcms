var contextMenuActiveSide;
var contextMenuInactiveSide;
var contextMenuActiveKatContextMenu;
var contextMenuInactiveKatContextMenu;
var contextMenuListOrderContextMenu;

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

function unlinkCategoryCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById("bind" + data.deleted[0] + "p" + data.pageId);
    if (data.added && $("kat" + data.added.categoryId + "content").innerText !== "") {
        xHttp.request("/admin/sitetree/" + data.added.categoryId + "/", expandCategoryCallback);
    }
}

// TODO only for getSiteTree
var sideContextMenu = [{
    "name": "Rediger",
    "className": "edit",
    callback(e) {
        location.href = "/admin/page/" + getNodeFromContextMenuEvent(e).parentNode.className.replace(/^side/, "") + "/";
    }
}];
var activeSideContextMenu = sideContextMenu.slice(0);
activeSideContextMenu.push({
    "name": "Fjern",
    "className": "unlink",
    callback(e) {
        var element = getNodeFromContextMenuEvent(e).parentNode;
        var name = "";
        if (element.childNodes.length > 1) {
            name = element.parentNode.previousSibling.lastChild.nodeValue.trim();
        }
        var ids = element.id.match(/\d+/g);
        removeBinding(name, ids[1], ids[0], unlinkCategoryCallback);
    }
});
var inactiveSideContextMenu = sideContextMenu.slice(0);
inactiveSideContextMenu.push({
    "name": "Slet",
    "className": "delete",
    callback(e) {
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
      callback(e) {
          var element = getNodeFromContextMenuEvent(e);
          renameCategory(element.parentNode.id.replace(/^kat/, ""), element.lastChild.nodeValue.trim());
      }
    },
    {
      "name": "Rediger",
      "className": "edit",
      callback(e) {
          location.href = "/admin/categories/" + getNodeFromContextMenuEvent(e).parentNode.id.replace(/^kat/, "") + "/";
      }
    }
];
var activeKatContextMenu = katContextMenu.slice(0);
activeKatContextMenu.push({
    "name": "fjern",
    "className": "unlink",
    callback(e) {
        var element = getNodeFromContextMenuEvent(e);
        console.log(element);
        moveCategory(element.lastChild.nodeValue.trim(), element.parentNode.id.replace(/^kat/, ""), -1, true);
    }
});
var inactiveKatContextMenu = katContextMenu.slice(0);
inactiveKatContextMenu.push({
    "name": "Slet",
    "className": "delete",
    callback(e) {
        var element = getNodeFromContextMenuEvent(e);
        console.log(element);
        deleteCategory(element.lastChild.nodeValue.trim(), element.parentNode.id.replace(/^kat/, ""));
    }
});

// TODO only for listorder
var listOrderContextMenu = [{
    "name": "Slet",
    "className": "delete",
    callback(e) {
        e.target.parentNode.removeChild(e.target);
    }
}];

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

function displaySubMenus(state) {
    if (state === "1") {
        $("subMenus").style.display = "";
        return;
    }
    $("subMenus").style.display = "none";
}

function createCategoryCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    location.href = "/admin/sitetree/";
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
        xHttp.request("/admin/categories/", createCategoryCallback, "POST", data);
        return false;
    }

    data.weightedChildren = $("custom_sort_subs").value === "1";
    data.subMenusOrder = $("subMenusOrder").value;

    xHttp.request("/admin/categories/" + id + "/", genericCallback, "PUT", data);
    return false;
}

function updateBrandCallback(data) {
    location.href = "/admin/brands/";
}

function saveBrand(id = null) {
    $("loading").style.visibility = "";

    var data = {
        "title": document.getElementById("navn").value,
        "link": document.getElementById("link").value,
        "iconId": parseInt(document.getElementById("icon_id").value) || null
    };

    if (!id) {
        xHttp.request("/admin/brands/", updateBrandCallback, "POST", data);

        return false;
    }

    xHttp.request("/admin/brands/" + id + "/", genericCallback, "PUT", data);

    return false;
}

function createPageCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    window.location.href = "/admin/page/" + data.id + "/";
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
        saveRequest = xHttp.request("/admin/page/", createPageCallback, "POST", page);

        return false;
    }

    saveRequest = xHttp.request("/admin/page/" + id + "/", genericCallback, "PUT", page);

    return false;
}

function updateSpecial(id) {
    $("loading").style.visibility = "";
    if ($("subMenusOrder")) {
        xHttp.request("/admin/categories/0/", genericCallback, "PUT", {"subMenusOrder": $("subMenusOrder").value});
    }

    var data = {"html": CKEDITOR.instances.text.getData(), "title": $("title") ? $("title").value : ""};
    xHttp.request("/admin/custom/" + id + "/", genericCallback, "PUT", data);
    return false;
}

function addNewItem() {
    var text = $("newItem");
    if (text.value !== "") {
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

function createListCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    location.href = "/admin/sortings/" + data.id + "/";
}

function saveListOrder(id) {
    $("loading").style.visibility = "";
    var data = {
        "title": $("listOrderNavn").value,
        "items": [],
    };
    var listOrder = $("listOrder");
    for (const item of listOrder.childNodes) {
        data.items.push(item.innerText);
    }
    if (id === null) {
        xHttp.request("/admin/sortings/", createListCallback, "POST", data);
        return;
    }

    xHttp.request("/admin/sortings/" + id + "/", genericCallback, "PUT", data);
}

function insertMailToCount(data) {
    if (!genericCallback(data)) {
        return;
    }

    $("mailToCount").innerText = data.count;
}

var contactCountRequest;
function countEmailTo() {
    $("loading").style.visibility = "";
    var query = "";
    var interestObjs = $("interests").getElementsByTagName("input");
    for (const interest of interestObjs) {
        if (interest.checked) {
            query += "interests[]=" + encodeURIComponent(interest.value) + "&";
        }
    }

    xHttp.cancel(contactCountRequest);
    contactCountRequest = xHttp.request("/admin/addressbook/count/?" + query, insertMailToCount);
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
    for (const interest of interestObjs) {
        if (interest.checked) {
            data.interests.push(interest.value);
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
    for (const interest of interestObjs) {
        if (interest.checked) {
            data.interests.push(interest.value);
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

function deleteContact(id, name) {
    if (!confirm("Vil du fjerne '" + name + "' fra adressebogen?")) {
        return false;
    }
    $("loading").style.visibility = "";
    xHttp.request("/admin/addressbook/" + id + "/", deleteContact_r, "DELETE");
}

function deleteContact_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById(data.id);
}

function sendEmail() {
    if (!confirm("Ønsker du virkelig at sende denne nyhedsmail nu?")) {
        return false;
    }
    $("loading").style.visibility = "";
    var html = CKEDITOR.instances.text.getData();
    if ($("from").value === "") {
        $("loading").style.visibility = "hidden";
        alert("Du skal vælge en afsender!");
        return false;
    }
    if ($("subject").value === "") {
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
        $("errors").innerHTML += data.html;
    }
}
var startTime;
function scan_db() {
    $("loading").style.visibility = "";
    $("errors").innerText = "";

    startTime = new Date().getTime();

    $("status").innerText = "Removing contacts that are missing vital information";
    xHttp.request("/admin/maintenance/contacts/empty/", maintainStep2, "DELETE");
}

function maintainStep2(data) {
    set_db_errors(data);
    $("status").innerText = "Searching for pages without bindings";
    xHttp.request("/admin/maintenance/pages/orphans/", maintainStep3);
}

function maintainStep3(data) {
    set_db_errors(data);
    $("status").innerText = "Searching for pages with illegal bindings";
    xHttp.request("/admin/maintenance/pages/mismatches/", maintainStep4);
}

function maintainStep4(data) {
    set_db_errors(data);
    $("status").innerText = "Searching for cirkalur linked categories";
    xHttp.request("/admin/maintenance/categories/circular/", maintainStep5);
}

function maintainStep5(data) {
    set_db_errors(data);
    $("status").innerText = "Checking the file names";
    xHttp.request("/admin/maintenance/files/names/", maintainStep6);
}

function maintainStep6(data) {
    set_db_errors(data);
    $("status").innerText = "Checking the folder names";
    xHttp.request("/admin/maintenance/files/folderNames/", maintainStep7);
}

function maintainStep7(data) {
    set_db_errors(data);
    $("status").innerText = "Sending delayed emails";
    xHttp.request("/admin/maintenance/emails/send/", maintainStep8, "POST");
}

function maintainStep8(data) {
    set_db_errors(data);
    $("status").innerText = "Getting system usage";
    xHttp.request("/admin/maintenance/usage/", maintainStep9);
}

function maintainStep9(data) {
    getUsage_r(data);
    $("errors").innerHTML += "<br />" +
                            ("The scan took %d seconds.".replace(
                                /[%]d/g, Math.round((new Date().getTime() - startTime) / 1000).toString()));
}

function get_subscriptions_with_bad_emails() {
    $("loading").style.visibility = "";
    $("errors").innerText = "";

    starttime = new Date().getTime();

    $("status").innerText = "Searching for illegal e-mail adresses";
    xHttp.request("/admin/maintenance/contacts/invalid/", subscriptionsWithBadEmails_r);
}

function subscriptionsWithBadEmails_r(data) {
    $("loading").style.visibility = "hidden";
    $("errors").innerHTML += "<br />" + data.html + "<br />" +
                            ("The scan took %d seconds.".replace(
                                /[%]d/g, Math.round((new Date().getTime() - starttime) / 1000).toString()));
    $("status").innerText = "";
}

function removeNoneExistingFiles() {
    $("loading").style.visibility = "";

    starttime = new Date().getTime();

    $("status").innerText = "Remove missing files from database";
    xHttp.request("/admin/maintenance/files/missing/", removeNoneExistingFiles_r, "DELETE");
}

function removeNoneExistingFiles_r(data) {
    var missingHtml = "";
    if (data.missingFiles) {
        missingHtml = "<b>The following files are missing:</b><a onclick=\"explorer('','')\">";
        for (const fileName of data.missingFiles) {
            missingHtml += "<br />";
            missingHtml += fileName;
        }
        missingHtml += "</a>";
    }
    $("errors").innerHTML = missingHtml + "<br />" + data.deleted + " files removed" +
                            "<br />" + ("The scan took %d seconds.".replace(
                                           /[%]d/g, Math.round((new Date().getTime() - starttime) / 1000).toString()));

    $("status").innerText = "Getting system usage";
    xHttp.request("/admin/maintenance/usage/", getUsage_r);
}

function getEmailUsage() {
    $("loading").style.visibility = "";
    $("status").innerText = "Getting email usage";
    xHttp.request("/admin/maintenance/emails/usage/", getEmailUsage_r);
}

function getEmailUsage_r(data) {
    $("mailboxsize").innerText = Math.round(data.size / 1024 / 1024 * 10) / 10 + "MB";
    $("status").innerText = "";
    $("loading").style.visibility = "hidden";
}

function getUsage_r(data) {
    $("loading").style.visibility = "hidden";
    $("status").innerText = "";
    $("wwwsize").innerText = Math.round(data.www / 1024 / 1024 * 10) / 10 + "MB";
    $("dbsize").innerText = Math.round(data.db / 1024 / 1024 * 10) / 10 + "MB";
}

function createInvoice() {
    xHttp.request("/admin/invoices/", createInvoice_r, "POST");
}

function createInvoice_r(data) {
    location.href = "/admin/invoices/" + data.id + "/";
}
