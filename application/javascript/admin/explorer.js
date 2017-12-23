var files = [];
var activeDir = getCookie("admin_dir");

var contextMenuFileTile;
var contextMenuImageTile;

function init() {
    // attach context menus
    contextMenuFileTile =
        new Proto.Menu({"selector": ".filetile", "className": "menu desktop", "menuItems": fileTileContextMenu});
    contextMenuImageTile =
        new Proto.Menu({"selector": ".imagetile", "className": "menu desktop", "menuItems": imageTileContextMenu});

    if (!activeDir || !document.getElementById(dirToId(activeDir))) {
        activeDir = "/images";
    }

    showfiles(activeDir);
}

function file(data) {
    this.id = data.id;
    this.path = data.path;
    this.name = data.name;
    this.description = data.description;
    this.mime = data.mime;
    this.width = data.width ? data.width : screen.availWidth;
    this.height = data.height ? data.height : screen.availHeight;
}

file.prototype.openfile = function() {
    var url = this.path;
    var width = this.width;
    var height = this.height;
    var type = popupType(this.mime);
    if (type) {
        url = "/admin/explorer/files/" + this.id + "/";
        if (type === "audio") {
            width = 300;
            height = 40;
        }
    }
    popUpWin(url, "file_view", "toolbar=0", width, height);
};

function popupType(mime) {
    if (mime === "image/gif" || mime === "image/jpeg" || mime === "image/png") {
        return "image";
    }

    if (mime.match(/^audio\//g)) {
        return "audio";
    }

    if (mime.match(/^video\//g)) {
        return "video";
    }

    return "";
}

function getContextMenuTarget(object, className) {
    while (object.className !== className) {
        object = object.parentNode;
    }

    return object;
}

file.prototype.addToEditor = function() {
    var html = "<a href=\"" + htmlEncode(this.path) + "\" target=\"_blank\">" + htmlEncode(this.name) + "</a>";
    switch (popupType(this.mime)) {
        case "image":
            html = "<img src=\"" + htmlEncode(this.path) + "\" title=\"\" alt=\"" + htmlEncode(this.description) +
                   "\" width=\"" + this.width + "\" height=\"" + this.height + "\" />";
            break;
        case "audio":
            var data = {"classes": {"ckeditor-html5-audio": 1}, "src": this.path};
            data = JSON.stringify(data);
            data = encodeURIComponent(data);
            html =
                "<div class=\"ckeditor-html5-audio cke_widget_element\" data-cke-widget-keep-attr=\"0\" data-widget=\"html5audio\" data-cke-widget-data=\"" +
                data + "\"><audio controls=\"controls\" src=\"" + this.path + "\"></audio></div>";
            break;
        case "video":
            var data = "<cke:video width=\"" + this.width + "\" height=\"" + this.height + "\" src=\"" +
                       htmlEncode(this.path) + "\" controls=\"controls\"></cke:video>";
            data = encodeURIComponent(data);
            html =
                "<img class=\"cke-video\" data-cke-realelement=\"" + data +
                "\" data-cke-real-node-type=\"1\" alt=\"Video\" title=\"Video\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22320%22%20height%3D%22240%22%3E%3C%2Fsvg%3E\" data-cke-real-element-type=\"video\" align=\"\">";
            break;
    }
    var element = window.opener.CKEDITOR.dom.element.createFromHtml(html);
    var CKEDITOR = window.opener.CKEDITOR;
    for (var i in CKEDITOR.instances) {
        var currentInstance = i;
        break;
    }
    var oEditor = window.opener.CKEDITOR.instances[currentInstance];
    oEditor.insertElement(element);
    window.close();
};

file.prototype.refresh = function() {
    var img = $("tilebox" + this.id).firstChild.childNodes[1];
    var fullSizeUrl = this.path;
    $("reloader").onload = function() {
        this.onload = function() {
            this.onload = function() {
                this.onload = null;                       // Stop event
                this.contentWindow.location.reload(true); // Refresh cache for full size image
            };
            img.src = this.src;     // Display new thumbnail
            this.src = fullSizeUrl; // Start reloading of full size image
        };
        this.contentWindow.location.reload(true); // Refresh cache for thumb image
    };
    var url = img.src;
    img.src = url + "#";     // Set image to a temp path so we can reload it later
    $("reloader").src = url; // Start cache refreshing
};

function reattachContextMenus() {
    contextMenuFileTile.reattach();
    contextMenuImageTile.reattach();
}

var fileTileContextMenu = [
    {
      "name": "Åbne",
      "className": "eye",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          files[id].openfile();
      }
    },
    {
      "name": "Flyt",
      "className": "folder_go",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          fileMoveDialog(id);
      }
    },
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          showfilename(id);
      }
    },
    {
      "name": "Slet",
      "className": "delete",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "filetile").id.match(/[0-9]+/g)[0];
          deletefile(id);
      }
    }
];

var imageTileContextMenu = [{
    "name": "Åbne",
    "className": "picture",
    "callback": function(e) {
        var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
        files[id].openfile();
    }
}];

if (window.location.href.match(/return=ckeditor/g)) {
    imageTileContextMenu.push({
        "name": "Indsæt link",
        "className": "link",
        "callback": function(e) {
            var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
            files[id].addToEditor();
        }
    });
}
imageTileContextMenu = imageTileContextMenu.concat([
    {
      "name": "Rediger",
      "className": "picture_edit",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          open_image_edit(id);
      }
    },
    {
      "name": "Beskrivelse",
      "className": "textfield_rename",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          editDescription(id);
      }
    },
    {
      "name": "Generere ikon",
      "className": "pictures",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          openImageThumbnail(id);
      }
    },
    {
      "name": "Flyt",
      "className": "folder_go",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          fileMoveDialog(id);
      }
    },
    {
      "name": "Omdøb",
      "className": "textfield_rename",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          showfilename(id);
      }
    },
    {
      "name": "Slet",
      "className": "delete",
      "callback": function(e) {
          var id = getContextMenuTarget(e.target, "imagetile").id.match(/[0-9]+/g)[0];
          deletefile(id);
      }
    }
]);

var editDescriptionRequest;
function editDescription(id) {
    var newalt = prompt("Billed beskrivelse", files[id].description);
    if (newalt === null || newalt === files[id].description) {
        return;
    }

    document.getElementById("loading").style.visibility = "";

    var data = {"description": newalt};
    xHttp.cancel(editDescriptionRequest);
    editDescriptionRequest =
        xHttp.request("/admin/explorer/files/" + id + "/description/", editDescriptionCallback, "PUT", data);
}

function editDescriptionCallback(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    files[data.id].description = data.description;
}

var searchfilesRequest = null;
function searchfiles() {
    document.getElementById("loading").style.visibility = "";
    qpath = document.getElementById("searchpath").value;
    qalt = document.getElementById("searchalt").value;
    qtype = getSelect("searchtype");

    xHttp.cancel(searchfilesRequest);
    searchfilesRequest = xHttp.request("/admin/explorer/search/?qpath=" + encodeURIComponent(qpath) + "&qalt=" +
                                           encodeURIComponent(qalt) + "&qtype=" + encodeURIComponent(qtype) +
                                           "&return=" + encodeURIComponent(returnType),
                                       showfiles_r);
}

function getSelect(id) {
    object = document.getElementById(id);
    return object[object.selectedIndex].value;
}

var showFilesRequest = null;
function showfiles(dir) {
    // TODO, scroll to top.
    activeDir = dir;
    setCookie("admin_dir", activeDir, 360);

    document.getElementById("loading").style.visibility = "";
    dirlist = document.getElementById("dir").getElementsByTagName("a");
    for (var i = 0; i < dirlist.length; i++) {
        dirlist[i].className = "";
    }
    document.getElementById(dirToId(dir)).getElementsByTagName("a")[0].className = "active";

    xHttp.cancel(showFilesRequest);
    showFilesRequest = xHttp.request(
        "/admin/explorer/files/?path=" + encodeURIComponent(dir) + "&return=" + encodeURIComponent(returnType),
        showfiles_r);
}

function showfiles_r(data) {
    inject_html(data);
    files = [];
    eval(data.javascript);
    reattachContextMenus();
}

function showfilename(id) {
    document.getElementById("navn" + id + "div").style.display = "none";
    document.getElementById("navn" + id + "form").style.display = "";
    document.getElementById("navn" + id + "form").firstChild.firstChild.select();
    document.getElementById("navn" + id + "form").firstChild.firstChild.focus();
}

function showdirname(nameObj) {
    nameObj.style.display = "none";
    nameObj.nextSibling.style.display = "inline";
    nameObj.nextSibling.firstChild.childNodes[1].select();
    nameObj.nextSibling.firstChild.childNodes[1].focus();
}

function renamedir(newNameObj) {
    newNameObj.parentNode.parentNode.style.display = "none";
    newNameObj.parentNode.parentNode.previousSibling.style.display = "";
    document.getElementById("loading").style.visibility = "";
    var payload = {"path": idToDir(newNameObj.parentNode.parentNode.parentNode.id), "name": newNameObj.value};
    xHttp.request("/admin/explorer/folders/", renamedir_r, "PUT", payload);
}

function renamedir_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    var form = document.getElementById(dirToId(data.path)).getElementsByTagName("form")[0];
    if (data.error) {
        form.firstChild.childNodes[1].value = form.previousSibling.title;
        return;
    }

    if (data.yesno) {
        if (confirm(data.yesno)) {
            document.getElementById("loading").style.visibility = "";
            var payload = {
                "path": data.path,
                "name": form.firstChild.childNodes[1].value,
                "overwrite": true,
            };
            xHttp.request("/admin/explorer/folders/", renamedir_r, "PUT", payload);
            return;
        }
        form.firstChild.childNodes[1].value = form.previousSibling.title;
        return;
    }

    if (data.newPath !== data.path) {
        setCookie("admin_dir", data.newPath, 360);
        window.location.reload();
    }
}

var popup = null;
function popUpWin(url, win, options, width, height) {
    if (popup !== null) {
        popup.close();
        popup = null;
    }
    if (options !== "") {
        options += ",";
    }
    var left = (screen.availWidth - width) / 2;
    var top = (screen.availHeight - height) / 2;
    popup = window.open(url, win, options + "width=" + width + ",height=" + height + ",left=" + left + ",top=" + top);
}

function fileMoveDialog(id) {
    popUpWin("/admin/explorer/move/" + id + "/", "file_move", "toolbar=0", 322, 512);
}

function deleteFolder() {
    // TODO hvilket folder?
    if (confirm("Er du sikker på du vil slette denne mappe og dens indhold?")) {
        xHttp.request("/admin/explorer/folders/?path=" + encodeURIComponent(activeDir), deleteFolderCallback, "DELETE");
        setCookie("admin_dir", "", 360);
    }
}

function deleteFolderCallback(data) {
    window.location.reload();
}

function makedir() {
    if (name = prompt("Hvad skal mappen hede?", "Ny mappe")) {
        document.getElementById("loading").style.visibility = "";
        xHttp.request(
            "/admin/explorer/folders/?path=" + encodeURIComponent(activeDir) + "&name=" + encodeURIComponent(name),
            makedir_r, "POST");
    }
}

function makedir_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    window.location.reload();
}

function dirToId(dir) {
    return "dir_" + dir.replace(/\//g, ".");
}

function idToDir(id) {
    return id.substr(4).replace(/[.]/g, "/");
}

function dir_expand(dirdiv, move) {
    dirdiv = dirdiv.parentNode;
    if (dirdiv.lastChild.firstChild === null) {
        document.getElementById("loading").style.visibility = "";
        xHttp.request(
            "/admin/explorer/folders/?path=" + encodeURIComponent(idToDir(dirdiv.id)) + "&move=" + (move ? 1 : 0),
            dir_expand_r);
        return;
    }

    dirdiv.lastChild.style.display = "";
    dirdiv.firstChild.style.display = "none";
    dirdiv.childNodes[1].style.display = "";
}

function dir_expand_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    var dirdiv = document.getElementById(dirToId(data.id));
    dirdiv.firstChild.style.display = "none";
    dirdiv.childNodes[1].style.display = "";
    dirdiv.lastChild.innerHTML = data.html;
    dirdiv.lastChild.style.display = "";
}

function dir_contract(obj) {
    obj = obj.parentNode;
    obj.lastChild.style.display = "none";
    obj.firstChild.style.display = "";
    obj.childNodes[1].style.display = "none";
}

function openImageThumbnail(id) {
    popUpWin("/admin/explorer/files/" + id + "/image/edit/?mode=thb", "image_thumbnail", "scrollbars=1,toolbar=0", 740,
             600);
}

function open_image_edit(id) {
    popUpWin("/admin/explorer/files/" + id + "/image/edit/", "image_edit", "scrollbars=1,toolbar=0", 740, 600);
}

function open_file_upload() {
    popUpWin("/admin/explorer/upload/?path=" + encodeURIComponent(activeDir), "file_upload", "toolbar=0", 640, 150);
}

function insertThumbnail(id) {
    window.opener.document.getElementById(returnid).value = id;
    window.opener.document.getElementById(returnid + "thb").src = files[id].path;
    window.close();
}

// TODO if force, refresh folder or we might have duplicates displaying in the folder.
function renamefile(id) {
    document.getElementById("navn" + id + "form").style.display = "none";
    document.getElementById("navn" + id + "div").style.display = "";
    var payload = {"name": document.getElementById("navn" + id + "form").firstChild.firstChild.value};
    document.getElementById("loading").style.visibility = "";
    xHttp.request("/admin/explorer/files/" + id + "/", renamefile_r, "PUT", payload);
}

function renamefile_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        document.getElementById("navn" + data.id + "form").firstChild.firstChild.value = files[data.id].name;
        return;
    }

    if (data.yesno) {
        if (confirm(data.yesno)) {
            document.getElementById("loading").style.visibility = "";
            var payload = {
                "name": document.getElementById("navn" + data.id + "form").firstChild.firstChild.value,
                "overwrite": true
            };
            xHttp.request("/admin/explorer/files/" + data.id + "/", renamefile_r, "PUT", payload);
            return;
        }

        document.getElementById("navn" + data.id + "form").firstChild.firstChild.value = files[data.id].name;
        return;
    }

    document.getElementById("navn" + data.id + "div").innerHTML = data.filename;
    document.getElementById("navn" + data.id + "div").title = data.filename;
    document.getElementById("navn" + data.id + "form").firstChild.firstChild.value = data.filename;
    files[data.id].name = data.filename;
    files[data.id].path = data.path;
}

var moveFileGlobal;
function movefile(dir) {
    moveFileGlobal = dir;
    window.opener.document.getElementById("loading").style.display = "";
    var data = {"dir": dir};
    xHttp.request("/admin/explorer/files/" + fileId + "/", movefile_r, "PUT", data);
}

function movefile_r(data) {
    window.opener.document.getElementById("loading").style.display = "none";

    if (data.newPath === data.path) {
        return;
    }

    if (data.error) {
        return;
    }
    if (data.yesno) {
        if (confirm(data.yesno)) {
            document.getElementById("loading").style.display = "";
            var data = {"dir": moveFileGlobal, "overwrite": true};
            xHttp.request("/admin/explorer/files/" + fileId + "/", movefile_r, "PUT", data);
        }
        return;
    }

    window.opener.document.getElementById("files").removeChild(
        window.opener.document.getElementById("tilebox" + data.id));
    window.close();
}

function deletefile(id) {
    if (confirm("Vil du slette '" + files[id].name + "'?")) {
        document.getElementById("loading").style.visibility = "";
        xHttp.request("/admin/explorer/files/" + id + "/", deletefile_r, "DELETE");
    }
}

function deletefile_r(data) {
    document.getElementById("loading").style.visibility = "hidden";
    if (data.error) {
        return;
    }

    removeTagById("tilebox" + data.id);
    files[data.id] = null;
}

function swap_pannel(navn) {
    // Save what mode we are in and what was searched for
    if (navn === "search") {
        document.getElementById("files").innerHTML = "";
        document.getElementById("dir_bn").className = "";
        document.getElementById("dir").style.display = "none";
        document.getElementById("search_bn").className = "down";
        document.getElementById("search").style.display = "";
        if (document.getElementById("searchpath").value || document.getElementById("searchalt").value ||
            getSelect("searchtype")) {
            searchfiles();
        }
    } else if (navn === "dir") {
        document.getElementById("files").innerHTML = "";
        document.getElementById("search_bn").className = "";
        document.getElementById("search").style.display = "none";
        document.getElementById("dir_bn").className = "down";
        document.getElementById("dir").style.display = "";
        showfiles(activeDir);
    }
    return false;
}
