function htmlEncode(value) {
    var div = document.createElement("div")
    div.innerText = value;
    return div.innerHTML;
}

function getContextMenuTarget(object, className) {
    while (object.className != className) {
        object = object.parentNode;
    }

    return object;
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
    if (data.error) {
        return false;
    }

    return true;
}

function checkForInt(evt) {
    return (evt.which >= 48 && evt.charCode <= 57) || evt.charCode == 8 || evt.charCode == 0 || evt.charCode == 13;
}

function inject_html(data) {
    if (!genericCallback(data)) {
        return;
    }

    $(data.id).innerHTML = data.html;
}

function isInteger(s) {
    var i;
    for (i = 0; i < s.length; i++) {
        // Check that current character is number.
        var c = s.charAt(i);
        if (c < "0" || c > "9") {
            return false;
        }
    }

    // All characters are numbers.
    return true;
}

function setCookie(name, value, expires) {
    var today = new Date();
    today.setTime(today.getTime());
    if (expires) {
        expires = expires * 1000 * 60 * 60 * 24;
    }

    var expires_date = new Date(today.getTime() + (expires));
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) +
                      ((expires) ? "; expires=" + expires_date.toGMTString() : "");
}

function getCookie(cookieName) {
    cookieName = encodeURIComponent(cookieName);
    if (document.cookie.length > 0) {
        begin = document.cookie.indexOf(cookieName + "=");
        if (begin != -1) {
            begin += cookieName.length + 1;
            end = document.cookie.indexOf(";", begin);
            if (end == -1) {
                end = document.cookie.length;
            }

            return decodeURIComponent(document.cookie.substring(begin, end));
        }
    }

    return null;
}

function showhide(id) {
    var obj = $(id);
    if (obj.style.display == "") {
        obj.style.display = "none";
        setCookie("hide" + id, "1", 360);

        return;
    }

    obj.style.display = "";
    setCookie("hide" + id, "", 0);
}

function showhidekats(id, thisobj) {
    var obj = $(id);
    if (obj.style.display == "") {
        obj.style.display = "none";
        setCookie("hide" + id, "1", 360);
        $("loading").style.visibility = "";
        xHttp.request("/admin/sitetree/" + getRadio("kat") + "/lable/", inject_html);

        return;
    }

    obj.style.display = "";
    thisobj.innerHTML = "Vælg placering:";
    setCookie("hide" + id, "", 0);
}

function getRadio(name) {
    var objs = document.getElementsByName(name);
    for (var i = 0; i < objs.length; i++) {
        if (objs[i].checked) {
            return objs[i].value;
        }
    }

    return null;
}

function getSelectValue(id) {
    var select = $(id);
    if (select) {
        var options = select.getElementsByTagName("option");
        for (var i = 0; i < options.length; i++) {
            if (options[i].selected) {
                return options[i].value;
            }
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

function kat_contract(id) {
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

function expandCategory(id, input = "") {
    if (input === "") {
        setCookie("activekat", id, 360);
    }
    if ($("kat" + id + "content").innerHTML === "") {
        $("loading").style.visibility = "";
        x_expandCategory(id, input, expandCategory_r);

        return;
    }

    $("kat" + id + "content").style.display = "";
    $("kat" + id + "expand").style.display = "none";
    $("kat" + id + "contract").style.display = "";
    appendOpenCatCookie(id);
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

function expandCategory_r(data) {
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

function init() {
    $("loading").style.visibility = "hidden";
    attachContextMenus();
}

function save_krav() {
    $("loading").style.visibility = "";

    var data = {
        "title": $("navn").value,
        "html": CKEDITOR.instances.text.getData(),
    };

    var id = $("id").value;
    if (id) {
        xHttp.request("/admin/requirement/" + id + "/", save_krav_r, "PUT", data);
        return false;
    }

    xHttp.request("/admin/requirement/", save_krav_r, "POST", data);
    return false
}

function deleteRequirement(id, navn) {
    if (!confirm("Vil du slette kravet '" + navn + "'?")) {
        return false;
    }
    $("loading").style.visibility = "";
    xHttp.request("/admin/requirement/" + id + "/", slet_r, "DELETE");

    return false;
}

function save_krav_r(data) {
    location.href = "/admin/requirement/list/";
}

function updatemaerke_r(data) {
    location.href = "/admin/brands/";
}

function bind(id) {
    $("loading").style.visibility = "";
    x_bind(id, parseInt(getRadio("kat")), binding_r);

    return false;
}

function addAccessory(pageId) {
    $("loading").style.visibility = "";
    var accessoryId = $("accessoryFrame").contentWindow.getRadio("side");
    if (!accessoryId) {
        alert("Du skal vælge en side som tilbehør.");
        return false;
    }

    xHttp.request("/admin/page/" + pageId + "/accessories/" + accessoryId + "/", addAccessory_r, "POST");

    return false;
}

function addAccessory_r(data) {
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

function removeAccessory(navn, pageId, accessoryId) {
    if (confirm("Vil du fjerne '" + navn + "' som tilbehor?")) {
        $("loading").style.visibility = "";
        xHttp.request("/admin/page/" + pageId + "/accessories/" + accessoryId + "/", slet_r, "DELETE");
    }
    return false;
}

function removeBinding(navn, pageId, categoryId, callback = null) {
    callback = callback ? callback : binding_r;
    if (confirm("Vil du fjerne siden fra '" + navn + "'?")) {
        $("loading").style.visibility = "";
        x_sletbind(pageId, categoryId, callback);
    }
}

function binding_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    for (i = 0; i < data.deleted.length; i++) {
        removeTagById("bind" + data.deleted[i]);
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

function removeBindingFromTree(navn, pageId, categoryId) {
    removeBinding(navn, pageId, categoryId, bindTree_r)
}

function bindTree_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById("bind" + data.deleted[0] + "p" + data.pageId);

    if (data.added && $("kat" + data.added.categoryId + "content").innerHTML != "") {
        var display = $("kat" + data.added.categoryId + "content").style.display;
        x_siteList_expand(data.added.categoryId, 0, expandCategory_r);
        $("kat" + data.added.categoryId + "content").style.display = display;
    }
}

function objToArray(obj) {
    var r = [], x;
    for (x in obj) {
        if (obj.hasOwnProperty(x) && !isNaN(parseInt(x))) {
            r[x] = obj[x];
        }
    }

    return r;
}

function removeTagById(id) {
    var obj = $(id);
    if (!obj) {
        return;
    }
    obj.parentNode.removeChild(obj);
}

function removeTagByClass(className) {
    var objs = $$("." + className);
    for (var i = 0; i < objs.length; i++) {
        objs[i].parentNode.removeChild(objs[i]);
    }
}

function slet(type, navn, id) {
    switch (type) {
        case "side":
            if (confirm("Vil du slette '" + navn + "'?")) {
                $("loading").style.visibility = "";
                x_sletSide(id, sletClass_r);
            }
            break;
        case "maerke":
            if (confirm("Vil du slette mærket '" + navn + "'?")) {
                $("loading").style.visibility = "";
                x_sletmaerke(id, slet_r);
            }
            break;
        case "kat":
            if (confirm("Vil du slette katagorien '" + navn + "'?")) {
                $("loading").style.visibility = "";
                x_sletkat(id, slet_r);
            }
            break;
    }
}

function movekat(navn, id, toId, confirmMove) {
    if (!confirmMove || confirm("Vil du fjerne kategorien '" + navn + "'?")) {
        $("loading").style.visibility = "";
        x_movekat(id, toId, movekat_r);
    }
}

function movekat_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    if (data) {
        removeTagById(data.id);
        if ($("kat" + data.update + "content").innerHTML != "") {
            var display = $("kat" + data.update + "content").style.display;
            x_expandCategory(data.update, "categories", expandCategory_r);
            $("kat" + data.update + "content").style.display = display;
        }
    }
}

function renamekat(id, name) {
    var newname = prompt("Omdøb kategori", name);
    if (newname != null && newname != name) {
        $("loading").style.visibility = "";
        x_renamekat(id, newname, renamekat_r);
    }
}

function renamekat_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    if ($(data.id).childNodes.length == 4) {
        $(data.id).childNodes[2].lastChild.nodeValue = " " + data.name;

        return;
    }

    $(data.id).firstChild.lastChild.nodeValue = " " + data.name;
}

function sletClass_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagByClass(data.class);
}

function slet_r(data) {
    if (!genericCallback(data)) {
        return;
    }

    removeTagById(data.id);
}

function jumpto() {
    var jumptoid = $("jumptoid").value;
    if (!jumptoid && isInteger(jumptoid)) {
        alert("Du skal indtaste et korrekt side nummer");

        return false;
    }

    location.href = "/admin/page/" + jumptoid + "/";
}

function sogsearch() {
    var sogtext = $("sogtext").value;
    if (!sogtext) {
        alert("Du skal indtaste et søge ord.");

        return false;
    }

    $("loading").style.visibility = "";
    xHttp.request("/admin/page/search/?text=" + encodeURIComponent(sogtext), inject_html);
}

function confirm_faktura_validate(id) {
    return confirm("Mente du '" + id + "'?");
}
