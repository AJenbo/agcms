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

// TODO only for getSiteTree
var activeSideContextMenu = [
    {
      "name": "Rediger",
      "className": "edit",
      // TODO update to use getContextMenuTarget()
      "callback": function(e) {
          if (e.element().tagName.toLowerCase() == "a") {
              location.href = "/admin/editpage/" + e.target.parentNode.className.replace(/^side/, "") + "/";
              return;
          }
          location.href = "/admin/editpage/" + e.target.parentNode.parentNode.className.replace(/^side/, "") + "/";
      }
    },
    {
      "name": "Fjern",
      "className": "unlink",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          var element = e.target.parentNode;
          if (e.element().tagName.toLowerCase() != "a") {
              element = element.parentNode;
          }
          var name = element.parentNode.previousSibling.lastChild.nodeValue.trim();
          var ids = element.id.match(/\d+/g);
          removeBindingFromTree(name, ids[1], ids[0]);
      }
    }
];

// TODO only for getSiteTree
var inactiveSideContextMenu = [
    {
      "name": "Rediger",
      "className": "edit",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              location.href = "/admin/editpage/" + e.target.parentNode.className.replace(/^side/, "") + "/";
              return;
          }
          location.href = "/admin/editpage/" + e.target.parentNode.parentNode.className.replace(/^side/, "") + "/";
      }
    },
    {
      "name": "Slet",
      "className": "delete",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              slet("side", e.target.lastChild.nodeValue.trim(), e.target.parentNode.className.replace(/^side/, ""));
              return;
          }
          slet("side", e.target.parentNode.lastChild.nodeValue.trim(),
               e.target.parentNode.parentNode.className.replace(/^side/, ""));
      }
    }
];

// TODO only for getSiteTree
var activeKatContextMenu = [
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              renamekat(e.target.parentNode.id.replace(/^kat/, ""), e.target.lastChild.nodeValue.trim());
              return;
          }
          renamekat(e.target.parentNode.parentNode.id.replace(/^kat/, ""),
                    e.target.parentNode.lastChild.nodeValue.trim());
      }
    },
    {
      "name": "Rediger",
      "className": "edit",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              location.href = "/admin/?side=redigerkat&id=" + e.target.parentNode.id.replace(/^kat/, "");
              return;
          }
          location.href = "/admin/?side=redigerkat&id=" + e.target.parentNode.parentNode.id.replace(/^kat/, "");
      }
    },
    {
      "name": "fjern",
      "className": "unlink",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              movekat(e.target.lastChild.nodeValue.trim(), e.target.parentNode.id.replace(/^kat/, ""), -1, true);
              return;
          }
          movekat(e.target.parentNode.lastChild.nodeValue.trim(), e.target.parentNode.parentNode.id.replace(/^kat/, ""),
                  -1, true);
      }
    }
];

// TODO only for getSiteTree
var inactiveKatContextMenu = [
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              renamekat(e.target.parentNode.id.replace(/^kat/, ""), e.target.lastChild.nodeValue.trim());
              return;
          }
          renamekat(e.target.parentNode.parentNode.id.replace(/^kat/, ""),
                    e.target.parentNode.lastChild.nodeValue.trim());
      }
    },
    {
      "name": "Rediger",
      "className": "edit",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              location.href = "/admin/?side=redigerkat&id=" + e.target.parentNode.id.replace(/^kat/, "");
              return;
          }
          location.href = "/admin/?side=redigerkat&id=" + e.target.parentNode.parentNode.id.replace(/^kat/, "");
      }
    },
    {
      "name": "Slet",
      "className": "delete",
      "callback": function(e) {
          // TODO update to use getContextMenuTarget()
          if (e.element().tagName.toLowerCase() == "a") {
              slet("kat", e.target.lastChild.nodeValue.trim(), e.target.parentNode.id.replace(/^kat/, ""));
              return;
          }
          slet("kat", e.target.parentNode.lastChild.nodeValue.trim(),
               e.target.parentNode.parentNode.id.replace(/^kat/, ""));
      }
    }
];

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
        x_sogogerstat(sog, erstat, sogogerstat_r);
    }
}

function sogogerstat_r(affected_rows) {
    $("loading").style.visibility = "hidden";
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

    var icon = parseInt($("icon_id").value) || null;

    if (!id) {
        x_save_ny_kat($("navn").value, getRadio("kat"), $("vis").value, $("email").value, icon, save_ny_kat_r);
        return false;
    }

    var hasWeightedChildren = $("custom_sort_subs").value ? 1 : 0;

    x_updateKat(id, $("navn").value, $("vis").value, $("email").value, hasWeightedChildren, $("subMenusOrder").value,
                getRadio("kat"), icon, generic_r);
    return false;
}

function updatemaerke(id) {
    $("loading").style.visibility = "";

    var icon = parseInt(document.getElementById("icon_id").value) || null;

    x_updatemaerke(id, document.getElementById("navn").value, document.getElementById("link").value, icon,
                   id ? inject_html : updatemaerke_r);

    return false;
}

var savePage = null;
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

    xHttp.cancel(savePage);
    if (!id) {
        page.categoryId = parseInt(getRadio("kat"));
        savePage = xHttp.request("/admin/editpage/", opretSide_r, "POST", page);

        return false;
    }

    savePage = xHttp.request("/admin/editpage/" + id + "/", generic_r, "PUT", page);

    return false;
}

function opretSide_r(data) {
    if (!generic_r(data)) {
        return;
    }

    window.location.href = "/admin/editpage/" + data.id + "/";
}

function updateSpecial(id) {
    $("loading").style.visibility = "";
    if ($("subMenusOrder")) {
        x_updateKatOrder($("subMenusOrder").value, generic_r);
    }

    var html = CKEDITOR.instances.text.getData();

    var title = $("title") ? $("title").value : "";

    x_updateSpecial(id, html, title, generic_r);
    return false;
}

function save_ny_kat_r(data) {
    if (data.error) {
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
    x_saveListOrder(id, $("listOrderNavn").value, newListOrder, generic_r);
}

function makeNewList() {
    var name = prompt("Ny liste");
    if (name != null) {
        $("loading").style.visibility = "";
        x_makeNewList(name, makeNewList_r);
    }
}

function makeNewList_r(data) {
    $("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    location.href = "/admin/?side=listsort-edit&id=" + data.id;
}

function countEmailTo() {
    $("loading").style.visibility = "";
    // Cancle all othere ajax requests to avoide reponce order mix up
    // TODO only cancle requests relating to countEmailTo
    sajax.cancel();
    var interestObjs = $("interests").getElementsByTagName("input");
    var interests = [];
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            interests.push(interestObjs[i].value);
        }
    }
    x_countEmailTo(interests, countEmailTo_r)
}

function countEmailTo_r(data) {
    $("loading").style.visibility = "hidden";
    $("mailToCount").innerHTML = data;
}

function saveEmail() {
    $("loading").style.visibility = "";
    var html = CKEDITOR.instances.text.getData();
    var interestObjs = $("interests").getElementsByTagName("input");
    var interests = "";
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            if (interests != "") {
                interests += "<";
            }
            interests += interestObjs[i].value;
        }
    }
    var id = $("id").value;
    id = id ? id : null;
    x_saveEmail($("from").value, interests, $("subject").value, html, id, generic_r);
}

function updateContact(id) {
    $("loading").style.visibility = "";
    var interestObjs = $("interests").getElementsByTagName("input");
    var interests = "";
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            if (interests != "") {
                interests += "<";
            }
            interests += interestObjs[i].value;
        }
    }
    x_updateContact($("navn").value, $("email").value, $("adresse").value, $("land").value, $("post").value,
                    $("by").value, $("tlf1").value, $("tlf2").value, $("kartotek").checked, interests, id,
                    updateContact_r);
}

function updateContact_r(data) {
    $("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }
    location.href = "/admin/?side=addressbook";
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
    var interestObjs = $("interests").getElementsByTagName("input");
    var interests = "";
    for (var i = 0; i < interestObjs.length; i++) {
        if (interestObjs[i].checked) {
            if (interests != "") {
                interests += "<";
            }
            interests += interestObjs[i].value;
        }
    }
    x_sendEmail($("id").value, $("from").value, interests, $("subject").value, html, sendEmail_r);
}

function sendEmail_r(data) {
    if (data.error) {
        $("loading").style.visibility = "hidden";
        return;
    }

    location.href = "/admin/?side=emaillist";
}

function deleteuser(id, name) {
    if (confirm("Do you want to delete the user '" + name + "'?")) {
        x_deleteuser(id, reload_r);
    }
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
    x_updateuser(id, update, reload_r);
    return false;
}
