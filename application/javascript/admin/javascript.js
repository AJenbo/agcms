function htmlEncode(value) {
    var div = document.createElement("div");
    div.innerText = value;
    return div.innerHTML;
}

function prisHighlight() {
    if ($("for").value - $("pris").value < 0) {
        $("pris").className = "Pris";

        return;
    }

    $("pris").className = "NyPris";
}

function setThb(id, value, src) {
    $(id).value = value;
    $(id + "thb").src = src;
}

function explorer(returntype, returnid) {
    window.open("/admin/explorer/?return=" + returntype + "&returnid=" + returnid, "explorer", "toolbar=0");
}

function genericCallback(data) {
    $("loading").style.visibility = "hidden";
    if (data === null || data.error) {
        return false;
    }

    return true;
}

function checkForInt(evt) {
    return (evt.which >= 48 && evt.charCode <= 57) || evt.charCode === 8 || evt.charCode === 0 || evt.charCode === 13;
}

function isInteger(string) {
    return parseInt(string).toString() === string;
}

function setCookie(name, value, expiresInDayes) {
    var now = new Date();
    now.setTime(now.getTime() + (expiresInDayes * 24 * 60 * 60 * 1000));
    var expires = "expires=" + now.toUTCString();
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + ";" + expires + ";path=/admin/";
}

function getCookie(cookieName) {
    cookieName = encodeURIComponent(cookieName);
    if (!document.cookie.length) {
        return null;
    }

    var begin = document.cookie.indexOf(cookieName + "=");
    if (begin === -1) {
        return null;
    }

    begin += cookieName.length + 1;
    var end = document.cookie.indexOf(";", begin);
    if (end === -1) {
        end = document.cookie.length;
    }

    return decodeURIComponent(document.cookie.substring(begin, end));
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

function injectText(data) {
    if (!genericCallback(data)) {
        return;
    }

    $(data.id).innerText = data.text;
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

function getSelectValue(id) {
    var select = $(id);
    if (!select) {
        return null;
    }

    var options = select.getElementsByTagName("option");
    for (const option of options) {
        if (option.selected) {
            return option.value;
        }
    }

    return null;
}

function showimage(obj, img) {
    $("imagelogo").innerHTML = "<img src=\"" + htmlEncode(img) + "\" />";
    $("imagelogo").style.left = obj.offsetLeft + 17 + "px";
    $("imagelogo").style.top = obj.offsetTop + 17 + "px";
    $("imagelogo").style.display = "";
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

function init() {
    $("loading").style.visibility = "hidden";
}

var arrayZipcode = {};
function loadZipCodesDk(data) {
    arrayZipcode = data;
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

function removeTagById(id) {
    var obj = $(id);
    if (!obj) {
        return;
    }
    obj.parentNode.removeChild(obj);
}

function deleteCallback(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById(data.id);
}

function deleteRequirement(id, navn) {
    if (!confirm("Vil du slette kravet '" + navn + "'?")) {
        return false;
    }
    $("loading").style.visibility = "";
    xHttp.request("/admin/requirement/" + id + "/", deleteCallback, "DELETE");

    return false;
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

function bind(id) {
    $("loading").style.visibility = "";
    var categoryId = parseInt(getRadio("kat"));
    xHttp.request("/admin/page/" + id + "/categories/" + categoryId + "/", bindingsCallback, "POST");

    return false;
}

function removeBinding(navn, id, categoryId, callback = null) {
    callback = callback || bindingsCallback;
    if (confirm("Vil du fjerne siden fra '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/page/" + id + "/categories/" + categoryId + "/", callback, "DELETE");
    }
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

function removeTagByClass(className) {
    var objs = $$("." + className);
    for (const obj of objs) {
        obj.parentNode.removeChild(obj);
    }
}

function deleteBrand(navn, id) {
    if (confirm("Vil du slette mærket '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/brands/" + id + "/", deleteCallback, "DELETE");
    }

    return false;
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

function deleteCategory(navn, id) {
    if (confirm("Vil du slette katagorien '" + navn + "'?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/categories/" + id + "/", deleteCallback, "DELETE");
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
    var newTitle = prompt("OmdÃ¸b kategori", title);
    if (newTitle !== null && newTitle !== title) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/categories/" + id + "/", renameCategoryCallback, "PUT", {"title": newTitle});
    }
}

function jumpto() {
    var jumptoid = $("jumptoid").value;
    if (!jumptoid && isInteger(jumptoid)) {
        alert("Du skal indtaste et korrekt side nummer");

        return false;
    }

    location.href = "/admin/page/" + jumptoid + "/";
}

function injectHtml(data) {
    if (!genericCallback(data)) {
        return;
    }

    $(data.id).innerHTML = data.html;
}

function sogsearch() {
    var sogtext = $("sogtext").value;
    if (!sogtext) {
        alert("Du skal indtaste et søge ord.");

        return false;
    }

    $("loading").style.visibility = "";
    xHttp.request("/admin/page/search/?text=" + encodeURIComponent(sogtext), injectHtml);
}

function reloadCallback(data) {
    window.location.reload();
}

function setPaymentTransferred(id, transferred) {
    xHttp.request("/admin/invoices/payments/" + id + "/", reloadCallback, "PUT", {"transferred": transferred});
    return false;
}

function confirmPaymentValidate(id) {
    if (!confirm("Mente du '" + id + "'?")) {
        return false;
    }

    return setPaymentTransferred(id, true);
}

function createTableCallback(data) {
    window.opener.location.reload();
    window.close();
}

function createTable() {
    var data = {
        "page_id": parseInt(document.getElementById("page_id").value),
        "title": document.getElementById("title").value,
        "columns": [],
        "order_by": parseInt(document.getElementById("dsort").value),
        "has_links": document.getElementById("link").checked
    };

    var cellsObjs = document.getElementsByName("cell");
    var cellNamesObjs = document.getElementsByName("cell_name");
    var sortsObjs = document.getElementsByName("sort");

    for (var i = 0; i < cellsObjs.length; i++) {
        if (cellsObjs[i].value === "" || cellNamesObjs[i].value === "" || sortsObjs[i].value === "") {
            continue;
        }

        data.columns.push({
            "title": cellNamesObjs[i].value.toString(),
            "type": cellsObjs[i].value.toString(),
            "sorting": sortsObjs[i].value.toString()
        });
    }

    xHttp.request("/admin/tables/", createTableCallback, "POST", data);

    return false;
}
