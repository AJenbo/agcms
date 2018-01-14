import xHttp from "../xHttp.js";
import {
    genericCallback,
    reloadCallback,
    injectHtml,
    setCookie,
    getCookie,
    getSelectValue,
    htmlEncode,
    removeTagById
} from "./javascript.js";
import {listlink, listInsertRow, listUpdateRow, listEditRow, listSizeFooter, listRemoveRow} from "./list.js";
import {
    copytonew,
    prisUpdate,
    removeRow,
    valideMail,
    getInvoiceAddress,
    getAltAddress,
    numberFormat,
    reloadPage,
    pbsconfirm,
    annul,
    save,
    sendReminder,
    setShippingAddressVisability,
    setPaymentTransferred,
    confirmPaymentValidate
} from "./invoice.js";
import openPopup from "./openPopup.js";
import {changeZipCode} from "../getAddress.js";

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

function contractCategory(id) {
    $("kat" + id + "content").style.display = "none";
    $("kat" + id + "contract").style.display = "none";
    $("kat" + id + "expand").style.display = "";
    var openkat = getCookie("openkat");
    openkat = openkat.split("<");
    if (openkat.indexOf(id + "") < 0) {
        return;
    }
    openkat.splice(openkat.indexOf(id + ""), 1);
    openkat = openkat.join("<");
    setCookie("openkat", openkat, 360);
}

function appendOpenCatCookie(id) {
    var openkat = getCookie("openkat");
    openkat = openkat ? openkat : "";
    openkat = openkat.split("<");
    openkat.push(id);
    openkat = openkat.uniq();
    openkat = openkat.join("<");
    setCookie("openkat", openkat, 360);
}

function expandCategoryCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    $("kat" + data.id + "expand").style.display = "none";
    $("kat" + data.id + "contract").style.display = "";
    $("kat" + data.id + "content").innerHTML = data.html;
    $("kat" + data.id + "content").style.display = "";
    appendOpenCatCookie(data.id);
    reattachContextMenus();
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

function expandCategory(id, input = "") {
    if (input === "") {
        setCookie("activekat", id, 360);
    }
    if ($("kat" + id + "content").innerText === "") {
        $("loading").style.visibility = "";
        xHttp.request("/admin/sitetree/" + id + "/?type=" + encodeURIComponent(input), expandCategoryCallback);

        return;
    }

    $("kat" + id + "content").style.display = "";
    $("kat" + id + "expand").style.display = "none";
    $("kat" + id + "contract").style.display = "";
    appendOpenCatCookie(id);
}

function removeTagByClass(className) {
    var objs = $$("." + className);
    for (const obj of objs) {
        obj.parentNode.removeChild(obj);
    }
}

function removeElementByClass(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagByClass(data.class);
}

function deletePage(navn, id) {
    if (confirm("Vil du slette '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/page/" + id + "/", removeElementByClass, "DELETE");
    }
}

function renameCategoryCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    if ($(data.id).childNodes.length === 4) {
        $(data.id).childNodes[2].lastChild.nodeValue = " " + data.title;

        return;
    }

    $(data.id).firstChild.lastChild.nodeValue = " " + data.title;
}

function renameCategory(id, title) {
    var newTitle = prompt("Omdøb kategori", title);
    if (newTitle !== null && newTitle !== title) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/categories/" + id + "/", renameCategoryCallback, "PUT", {"title": newTitle});
    }
}

function moveCategoryCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById(data.id);
    if ($("kat" + data.parentId + "content").innerText !== "") {
        xHttp.request("/admin/sitetree/" + data.parentId + "/", expandCategoryCallback);
    }
}

function moveCategory(navn, id, toId, confirmMove) {
    if (!confirmMove || confirm("Vil du fjerne kategorien '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/categories/" + id + "/", moveCategoryCallback, "PUT", {"parentId": toId});
    }
}

function deleteCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById(data.id);
}

function deleteCategory(navn, id) {
    if (confirm("Vil du slette katagorien '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/categories/" + id + "/", deleteCallback, "DELETE");
    }
}

function removeBinding(navn, id, categoryId, callback = null) {
    callback = callback || bindingsCallback;
    if (confirm("Vil du fjerne siden fra '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/page/" + id + "/categories/" + categoryId + "/", callback, "DELETE");
    }
}

function bindingsCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    for (const id of data.deleted) {
        removeTagById("bind" + id);
    }

    if (data.added) {
        var p = document.createElement("p");
        p.setAttribute("id", "bind" + data.added.categoryId);
        var img = document.createElement("img");
        img.setAttribute("src", "/theme/default/images/admin/cross.png");
        img.setAttribute("alt", "X");
        img.setAttribute("height", "16");
        img.setAttribute("width", "16");
        img.setAttribute("title", "Fjern binding");
        img.onclick = function() {
            removeBinding(data.added.path, data.pageId, data.added.categoryId);
        };
        p.appendChild(img);
        p.appendChild(document.createTextNode(" " + data.added.path));
        $("bindinger").appendChild(p);
    }
}

function getRadio(name) {
    var objs = document.getElementsByName(name);
    for (const obj of objs) {
        if (obj.checked) {
            return obj.value;
        }
    }

    return null;
}

function bind(id) {
    $("loading").style.visibility = "";
    var categoryId = parseInt(getRadio("kat"));
    xHttp.request("/admin/page/" + id + "/categories/" + categoryId + "/", bindingsCallback, "POST");

    return false;
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
        moveCategory(element.lastChild.nodeValue.trim(), element.parentNode.id.replace(/^kat/, ""), -1, true);
    }
});
var inactiveKatContextMenu = katContextMenu.slice(0);
inactiveKatContextMenu.push({
    "name": "Slet",
    "className": "delete",
    callback(e) {
        var element = getNodeFromContextMenuEvent(e);
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

function saveRequirementCallback(data) {
    location.href = "/admin/requirement/list/";
}

function saveRequirement() {
    $("loading").style.visibility = "";

    var data = {
        "title": $("navn").value,
        "html": CKEDITOR.instances.text.getData(),
    };

    var id = $("id").value;
    if (id) {
        xHttp.request("/admin/requirement/" + id + "/", saveRequirementCallback, "PUT", data);
        return false;
    }

    xHttp.request("/admin/requirement/", saveRequirementCallback, "POST", data);
    return false;
}

function deleteBrand(navn, id) {
    if (confirm("Vil du slette mærket '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/brands/" + id + "/", deleteCallback, "DELETE");
    }

    return false;
}

function deleteRequirement(id, navn) {
    if (!confirm("Vil du slette kravet '" + navn + "'?")) {
        return false;
    }
    $("loading").style.visibility = "";
    xHttp.request("/admin/requirement/" + id + "/", deleteCallback, "DELETE");

    return false;
}

function removeAccessory(navn, pageId, accessoryId) {
    if (confirm("Vil du fjerne '" + navn + "' som tilbehor?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/page/" + pageId + "/accessories/" + accessoryId + "/", deleteCallback, "DELETE");
    }
    return false;
}

function addAccessoryCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    var elementId = "accessory" + data.accessoryId;
    if ($(elementId)) {
        return;
    }

    var p = document.createElement("p");
    p.setAttribute("id", elementId);
    var img = document.createElement("img");
    img.setAttribute("src", "/theme/default/images/admin/cross.png");
    img.setAttribute("alt", "X");
    img.setAttribute("height", "16");
    img.setAttribute("width", "16");
    img.setAttribute("title", "Fjern tilbehør");
    img.onclick = function() {
        removeAccessory(data.title, data.pageId, data.accessoryId);
    };
    p.appendChild(img);
    p.appendChild(document.createTextNode(" " + data.title));
    $("accessories").appendChild(p);
}

function addAccessory(pageId) {
    $("loading").style.visibility = "";
    var accessoryId = $("accessoryFrame").contentWindow.getRadio("side");
    if (!accessoryId) {
        alert("Du skal vælge en side som tilbehør.");
        return false;
    }

    xHttp.request("/admin/page/" + pageId + "/accessories/" + accessoryId + "/", addAccessoryCallback, "POST");

    return false;
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
        li.id = "item_" + window.items;
        window.items++;
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

function sendEmailCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    location.href = "/admin/newsletters/";
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

    xHttp.request("/admin/newsletters/", sendEmailCallback, "POST", data);
}

function saveContactCallback(data) {
    if (!genericCallback(data)) {
        return;
    }
    location.href = "/admin/addressbook/list/";
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
        saveRequest = xHttp.request("/admin/addressbook/" + id + "/", saveContactCallback, "PUT", data);
        return false;
    }
    saveRequest = xHttp.request("/admin/addressbook/", saveContactCallback, "POST", data);
    return false;
}

function deleteContactCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById(data.id);
}

function deleteContact(id, name) {
    if (!confirm("Vil du fjerne '" + name + "' fra adressebogen?")) {
        return false;
    }
    $("loading").style.visibility = "";
    xHttp.request("/admin/addressbook/" + id + "/", deleteContactCallback, "DELETE");
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
    saveEmail(sendEmailCallback, true);
    return false;
}

function deleteuser(id, name) {
    if (!confirm("Do you want to delete the user '" + name + "'?")) {
        return;
    }

    xHttp.request("/admin/users/" + id + "/", reloadCallback, "DELETE");
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
    xHttp.request("/admin/users/" + id + "/", reloadCallback, "PUT", update);
    return false;
}

var startTime;
function addErrorReport(data) {
    if (data.html) {
        $("errors").innerHTML += data.html;
    }
}

function byteToHuman(bytes) {
    var sizes = ["B", "KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB", "BiB"];
    for (const size of sizes) {
        if (bytes < 1024 || size === "BiB") {
            return (Math.round(bytes * 10) / 10 + size).replace(/\./, ",");
        }

        bytes /= 1024;
    }
}

function getUsageCallback(data) {
    $("loading").style.visibility = "hidden";
    $("status").innerText = "";
    $("wwwsize").innerText = byteToHuman(data.www);
    $("dbsize").innerText = byteToHuman(data.db);
}

function maintainStep9(data) {
    getUsageCallback(data);
    addErrorReport({
        "html": "<br />" + ("The scan took %d seconds.".replace(
                               /[%]d/g, Math.round((new Date().getTime() - startTime) / 1000).toString()))
    });
}

function maintainStep8(data) {
    addErrorReport(data);
    $("status").innerText = "Getting system usage";
    xHttp.request("/admin/maintenance/usage/", maintainStep9);
}

function maintainStep7(data) {
    addErrorReport(data);
    $("status").innerText = "Sending delayed emails";
    xHttp.request("/admin/maintenance/emails/send/", maintainStep8, "POST");
}

function maintainStep6(data) {
    addErrorReport(data);
    $("status").innerText = "Checking the folder names";
    xHttp.request("/admin/maintenance/files/folderNames/", maintainStep7);
}

function maintainStep5(data) {
    addErrorReport(data);
    $("status").innerText = "Checking the file names";
    xHttp.request("/admin/maintenance/files/names/", maintainStep6);
}

function maintainStep4(data) {
    addErrorReport(data);
    $("status").innerText = "Searching for cirkalur linked categories";
    xHttp.request("/admin/maintenance/categories/circular/", maintainStep5);
}

function maintainStep3(data) {
    addErrorReport(data);
    $("status").innerText = "Searching for pages with illegal bindings";
    xHttp.request("/admin/maintenance/pages/mismatches/", maintainStep4);
}

function maintainStep2(data) {
    addErrorReport(data);
    $("status").innerText = "Searching for pages without bindings";
    xHttp.request("/admin/maintenance/pages/orphans/", maintainStep3);
}

function runMaintenance() {
    $("loading").style.visibility = "";
    $("errors").innerText = "";

    startTime = new Date().getTime();

    $("status").innerText = "Removing contacts that are missing vital information";
    xHttp.request("/admin/maintenance/contacts/empty/", maintainStep2, "DELETE");
}

function subscriptionsWithBadEmailsCallback(data) {
    $("loading").style.visibility = "hidden";
    addErrorReport({
        "html": "<br />" + data.html + "<br />" +
                    ("The scan took %d seconds.".replace(
                        /[%]d/g, Math.round((new Date().getTime() - startTime) / 1000).toString()))
    });
    $("status").innerText = "";
}

function getSubscriptionsWithBadEmails() {
    $("loading").style.visibility = "";
    $("errors").innerText = "";

    startTime = new Date().getTime();

    $("status").innerText = "Searching for illegal e-mail adresses";
    xHttp.request("/admin/maintenance/contacts/invalid/", subscriptionsWithBadEmailsCallback);
}

function removeNoneExistingFilesCallback(data) {
    var missingHtml = "";
    if (data.missingFiles) {
        missingHtml = "<b>The following files are missing:</b><a onclick=\"explorer('','')\">";
        for (const fileName of data.missingFiles) {
            missingHtml += "<br />";
            missingHtml += fileName;
        }
        missingHtml += "</a>";
    }

    addErrorReport({
        "html": missingHtml + "<br />" + data.deleted + " files removed" +
                    "<br />" + ("The scan took %d seconds.".replace(
                                   /[%]d/g, Math.round((new Date().getTime() - startTime) / 1000).toString()))
    });

    $("status").innerText = "Getting system usage";
    xHttp.request("/admin/maintenance/usage/", getUsageCallback);
}

function removeNoneExistingFiles() {
    $("loading").style.visibility = "";

    startTime = new Date().getTime();

    $("status").innerText = "Remove missing files from database";
    xHttp.request("/admin/maintenance/files/missing/", removeNoneExistingFilesCallback, "DELETE");
}

function getEmailUsageCallback(data) {
    $("mailboxsize").innerText = byteToHuman(data.size);
    $("status").innerText = "";
    $("loading").style.visibility = "hidden";
}

function getEmailUsage() {
    $("loading").style.visibility = "";
    $("status").innerText = "Getting email usage";
    xHttp.request("/admin/maintenance/emails/usage/", getEmailUsageCallback);
}

function injectText(data) {
    if (!genericCallback(data)) {
        return;
    }

    $(data.id).innerText = data.text;
}

function showhide(id) {
    var obj = $(id);
    if (obj.style.display === "") {
        obj.style.display = "none";
        setCookie("hide" + id, "1", 360);

        return;
    }

    obj.style.display = "";
    setCookie("hide" + id, "", 0);
}

function showhidekats(id, thisobj) {
    var obj = $(id);
    if (obj.style.display === "") {
        obj.style.display = "none";
        setCookie("hide" + id, "1", 360);
        $("loading").style.visibility = "";
        xHttp.request("/admin/sitetree/" + getRadio("kat") + "/lable/", injectText);

        return;
    }

    obj.style.display = "";
    thisobj.innerText = "Vælg placering:";
    setCookie("hide" + id, "", 0);
}

function checkForInt(evt) {
    return (evt.which >= 48 && evt.charCode <= 57) || evt.charCode === 8 || evt.charCode === 0 || evt.charCode === 13;
}

function isInteger(string) {
    return parseInt(string).toString() === string;
}

function jumpto() {
    var jumptoid = $("jumptoid").value;
    if (!jumptoid || !isInteger(jumptoid)) {
        alert("Du skal indtaste et korrekt side nummer");

        return false;
    }

    location.href = "/admin/page/" + jumptoid + "/";
    return false;
}

function sogsearch() {
    var sogtext = $("sogtext").value;
    if (!sogtext) {
        alert("Du skal indtaste et søge ord.");

        return false;
    }

    $("loading").style.visibility = "";
    xHttp.request("/admin/page/search/?text=" + encodeURIComponent(sogtext), injectHtml);
    return false;
}

function setThb(id, value, src) {
    $(id).value = value;
    $(id + "thb").src = src;
}

function explorer(returntype, returnid) {
    openPopup("/admin/explorer/?return=" + returntype + "&returnid=" + returnid, "explorer");
}

function showimage(obj, img) {
    $("imagelogo").innerHTML = "<img src=\"" + htmlEncode(img) + "\" />";
    $("imagelogo").style.left = obj.offsetLeft + 17 + "px";
    $("imagelogo").style.top = obj.offsetTop + 17 + "px";
    $("imagelogo").style.display = "";
}

function prisHighlight() {
    if ($("for").value - $("pris").value < 0) {
        $("pris").className = "Pris";

        return;
    }

    $("pris").className = "NyPris";
}

window.addEventListener("DOMContentLoaded", function(event) {
    window.displaySubMenus = displaySubMenus;
    window.updateKat = updateKat;
    window.saveBrand = saveBrand;
    window.updateSide = updateSide;
    window.updateSpecial = updateSpecial;
    window.addNewItem = addNewItem;
    window.saveListOrder = saveListOrder;
    window.countEmailTo = countEmailTo;
    window.saveEmail = saveEmail;
    window.updateContact = updateContact;
    window.deleteContact = deleteContact;
    window.sendEmail = sendEmail;
    window.deleteuser = deleteuser;
    window.updateuser = updateuser;
    window.runMaintenance = runMaintenance;
    window.getSubscriptionsWithBadEmails = getSubscriptionsWithBadEmails;
    window.removeNoneExistingFiles = removeNoneExistingFiles;
    window.getEmailUsage = getEmailUsage;
    window.prisHighlight = prisHighlight;
    window.setThb = setThb;
    window.explorer = explorer;
    window.checkForInt = checkForInt;
    window.showhide = showhide;
    window.showhidekats = showhidekats;
    window.showimage = showimage;
    window.contractCategory = contractCategory;
    window.expandCategory = expandCategory;
    window.expandCategoryCallback = expandCategoryCallback;
    window.saveRequirement = saveRequirement;
    window.removeBinding = removeBinding;
    window.bind = bind;
    window.deleteBrand = deleteBrand;
    window.removeAccessory = removeAccessory;
    window.addAccessory = addAccessory;
    window.deleteRequirement = deleteRequirement;
    window.jumpto = jumpto;
    window.sogsearch = sogsearch;
    window.setPaymentTransferred = setPaymentTransferred;
    window.confirmPaymentValidate = confirmPaymentValidate;

    // Lists
    window.listlink = listlink;
    window.listInsertRow = listInsertRow;
    window.listUpdateRow = listUpdateRow;
    window.listEditRow = listEditRow;
    window.listSizeFooter = listSizeFooter;
    window.listRemoveRow = listRemoveRow;

    // Invoice
    window.copytonew = copytonew;
    window.prisUpdate = prisUpdate;
    window.removeRow = removeRow;
    window.valideMail = valideMail;
    window.getInvoiceAddress = getInvoiceAddress;
    window.getAltAddress = getAltAddress;
    window.numberFormat = numberFormat;
    window.reloadPage = reloadPage;
    window.pbsconfirm = pbsconfirm;
    window.annul = annul;
    window.save = save;
    window.sendReminder = sendReminder;
    window.setShippingAddressVisability = setShippingAddressVisability;
    window.setPaymentTransferred = setPaymentTransferred;
    window.confirmPaymentValidate = confirmPaymentValidate;
    window.changeZipCode = changeZipCode;

    window.openPopup = openPopup;

    attachContextMenus();
});

window.addEventListener("load", function(event) {
    $("loading").style.visibility = "hidden";
});
