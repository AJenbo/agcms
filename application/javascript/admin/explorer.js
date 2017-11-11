var files = [];
var activeDir = getCookie('admin_dir');
// todo open this folder
var activeDir = activeDir ? activeDir : '/images';
var contextMenuFileTile;
var contextMenuImageTile;

function init()
{
    // attach context menus
    contextMenuFileTile
        = new Proto.Menu({ selector : '.filetile', "className" : 'menu desktop', menuItems : filetileContextMenu });
    contextMenuImageTile
        = new Proto.Menu({ selector : '.imagetile', "className" : 'menu desktop', menuItems : imagetileContextMenu });

    showfiles(activeDir);
}

function file(data)
{
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
    if(type) {
        url = '/admin/explorer/files/' + this.id + '/view/';
        if(type === 'audio') {
            width = 300;
            height = 40;
        }
    }
    popUpWin(url, 'file_view', 'toolbar=0', width, height);
};

function popupType(mime)
{
    if(mime === 'image/gif' || mime === 'image/jpeg' || mime === 'image/png') {
        return 'image';
    }

    if(mime.match(/^audio\//g)) {
        return 'audio';
    }

    if(mime.match(/^video\//g)) {
        return 'video';
    }

    return '';
}

file.prototype.addToEditor = function() {
    var html = '<a href="' + htmlEncode(this.path) + '" target="_blank">' + htmlEncode(this.name) + '</a>';
    switch(popupType(this.mime)) {
        case 'image':
            html = '<img src="' + htmlEncode(this.path) + '" title="" alt="' + htmlEncode(this.description)
                + '" width="' + this.width + '" height="' + this.height + '" />';
            break;
        case 'audio':
            var data = { "classes" : { "ckeditor-html5-audio" : 1 }, "src" : this.path };
            data = JSON.stringify(data);
            data = encodeURIComponent(data);
            html
                = '<div class="ckeditor-html5-audio cke_widget_element" data-cke-widget-keep-attr="0" data-widget="html5audio" data-cke-widget-data="'
                + data + '"><audio controls="controls" src="' + this.path + '"></audio></div>';
            break;
        case 'video':
            var data = '<cke:video width="' + this.width + '" height="' + this.height + '" src="'
                + htmlEncode(this.path) + '" controls="controls"></cke:video>';
            data = encodeURIComponent(data);
            html
                = '<img class="cke-video" data-cke-realelement="' + data + '" data-cke-real-node-type="1" alt="Video" title="Video" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22320%22%20height%3D%22240%22%3E%3C%2Fsvg%3E" data-cke-real-element-type="video" align="">';
            break;
    }
    var element = window.opener.CKEDITOR.dom.element.createFromHtml(html);
    var CKEDITOR = window.opener.CKEDITOR;
    for(var i in CKEDITOR.instances) {
        var currentInstance = i;
        break;
    }
    var oEditor = window.opener.CKEDITOR.instances[currentInstance];
    oEditor.insertElement(element);
    window.close();
};

file.prototype.refreshThumb = function() {
    $('tilebox' + this.id).firstChild.childNodes[1].src
        = 'image.php?path=' + encodeURIComponent(this.path) + '&maxW=128&maxH=96&timestamp=' + unix_timestamp();
};

function unix_timestamp()
{
    return parseInt(new Date().getTime().toString().substring(0, 10));
}

function reattachContextMenus()
{
    contextMenuFileTile.reattach();
    contextMenuImageTile.reattach();
}

var filetileContextMenu = [
    {
      "name" : 'Åbne',
      "className" : 'eye',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'filetile').id.match(/[0-9]+/g)[0];
          files[id].openfile();
      }
    },
    {
      "name" : 'Flyt',
      "className" : 'folder_go',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'filetile').id.match(/[0-9]+/g)[0];
          open_file_move(id);
      }
    },
    {
      "name" : 'Omdøb',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'filetile').id.match(/[0-9]+/g)[0];
          showfilename(id);
      }
    },
    {
      "name" : 'Slet',
      "className" : 'delete',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'filetile').id.match(/[0-9]+/g)[0];
          deletefile(id);
      }
    }
];

var imagetileContextMenu = [ {
    "name" : 'Åbne',
    "className" : 'picture',
    "callback" : function(e) {
        var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
        files[id].openfile();
    }
} ];

if(window.location.href.match(/return=ckeditor/g)) {
    imagetileContextMenu.push({
        "name" : 'Indsæt link',
        "className" : 'link',
        "callback" : function(e) {
            var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
            files[id].addToEditor();
        }
    });
}
imagetileContextMenu = imagetileContextMenu.concat([
    {
      "name" : 'Rediger',
      "className" : 'picture_edit',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
          open_image_edit(id);
      }
    },
    {
      "name" : 'Beskrivelse',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
          edit_alt(id);
      }
    },
    {
      "name" : 'Generere ikon',
      "className" : 'pictures',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
          openImageThumbnail(id);
      }
    },
    {
      "name" : 'Flyt',
      "className" : 'folder_go',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
          open_file_move(id);
      }
    },
    {
      "name" : 'Omdøb',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
          showfilename(id);
      }
    },
    {
      "name" : 'Slet',
      "className" : 'delete',
      "callback" : function(e) {
          var id = getContextMenuTarget(e.target, 'imagetile').id.match(/[0-9]+/g)[0];
          deletefile(id);
      }
    }
]);

function edit_alt(id)
{
    var newalt = prompt('Billed beskrivelse', files[id].alt);
    if(newalt != null && newalt != files[id].alt) {
        document.getElementById('loading').style.visibility = '';
        x_edit_alt(id, newalt, edit_alt_r);
    }
}

function edit_alt_r(data)
{
    document.getElementById('loading').style.visibility = 'hidden';
    if(data.error) {
        alert(data.error);
        return;
    }

    files[data.id].alt = data.alt;
}

var searchfilesRequest = null;
function searchfiles()
{
    document.getElementById('loading').style.visibility = '';
    qpath = document.getElementById('searchpath').value;
    qalt = document.getElementById('searchalt').value;
    qtype = getSelect('searchtype');

    xHttp.cancel(searchfilesRequest);
    searchfilesRequest = xHttp.request('/admin/explorer/search/?qpath=' + encodeURIComponent(qpath) + '&qalt='
            + encodeURIComponent(qalt) + '&qtype=' + encodeURIComponent(qtype),
        showfiles_r);
}

function getSelect(id)
{
    object = document.getElementById(id);
    return object[object.selectedIndex].value;
}

var showFilesRequest = null;
function showfiles(dir)
{
    // TODO, scroll to top.
    activeDir = dir;
    setCookie('admin_dir', activeDir, 360);

    document.getElementById('loading').style.visibility = '';
    dirlist = document.getElementById('dir').getElementsByTagName('a');
    for(var i = 0; i < dirlist.length; i++) {
        dirlist[i].className = '';
    }
    document.getElementById(dirToId(dir)).getElementsByTagName('a')[0].className = 'active';

    xHttp.cancel(showFilesRequest);
    showFilesRequest = xHttp.request('/admin/explorer/files/?path=' + encodeURIComponent(dir), showfiles_r);
}

function showfiles_r(data)
{
    inject_html(data);
    files = [];
    eval(data.javascript);
    reattachContextMenus();
}

function showfilename(id)
{
    document.getElementById('navn' + id + 'div').style.display = 'none';
    document.getElementById('navn' + id + 'form').style.display = '';
    document.getElementById('navn' + id + 'form').firstChild.firstChild.select();
    document.getElementById('navn' + id + 'form').firstChild.firstChild.focus();
}

function showdirname(nameObj)
{
    nameObj.style.display = 'none';
    nameObj.nextSibling.style.display = 'inline';
    nameObj.nextSibling.firstChild.childNodes[1].select();
    nameObj.nextSibling.firstChild.childNodes[1].focus();
}

function renamedir(newNameObj)
{
    newNameObj.parentNode.parentNode.style.display = 'none';
    newNameObj.parentNode.parentNode.previousSibling.style.display = '';
    document.getElementById('loading').style.visibility = '';
    x_renamefile(idToDir(newNameObj.parentNode.parentNode.parentNode.id),
        idToDir(newNameObj.parentNode.parentNode.parentNode.id), '', newNameObj.value, renamedir_r);
}

function renamedir_r(data)
{
    document.getElementById('loading').style.visibility = 'hidden';
    if(data.error) {
        var divdir = document.getElementById(dirToId(data.id));
        var form = divdir.getElementsByTagName('form');
        form[0].firstChild.childNodes[1].value = form[0].previousSibling.title;
        alert(data.error);
        return;
    }

    var formId = 'dir_' + data.id + 'form';
    if(data.yesno) {
        if(confirm(data.yesno)) {
            document.getElementById('loading').style.visibility = '';
            x_renamefile(data.id, document.getElementById(formId).lastChild.value, '',
                document.getElementById(formId).firstChild.value, 1, renamefile_r);
            return;
        }
        document.getElementById(formId).firstChild.value = document.getElementById('dir_' + data.id + 'name').innerHTML;
        return;
    }

    window.location.reload();
    /*
    document.getElementById('dir_'+data.id+'name').innerHTML = data.filename;
    document.getElementById('dir_'+data.id+'name').parentNode.title = data.filename;
    document.getElementById('dir_'+data.id+'form').firstChild.value = data.filename;
    document.getElementById('dir_'+data.id+'form').lastChild.value = data.path;
    */
}

var popup = null;
function popUpWin(url, win, options, width, height)
{
    if(popup != null) {
        popup.close();
        popup = null;
    }
    if(options != '') {
        options += ',';
    }
    var left = (screen.availWidth - width) / 2;
    var top = (screen.availHeight - height) / 2;
    popup = window.open(url, win, options + 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top);
}

function open_file_move(id)
{
    popUpWin(
        'file-move.php?id=' + id + '&path=' + encodeURIComponent(files[id].path), 'file_move', 'toolbar=0', 322, 512);
}

function deletefolder()
{
    // TODO hvilket folder?
    if(confirm('Er du sikker på du vil slette denne mappe og dens indhold?')) {
        xHttp.request('/admin/explorer/folders/?path=' + encodeURIComponent(activeDir), deletefolder_r, "DELETE");
        setCookie('admin_dir', '', 360);
    }
}

function deletefolder_r(data)
{
    if(data.error) {
        alert(data.error);
    }
    window.location.reload();
}

function makedir()
{
    if(name = prompt('Hvad skal mappen hede?', 'Ny mappe')) {
        document.getElementById('loading').style.visibility = '';
        xHttp.request(
            '/admin/explorer/folders/?path=' + encodeURIComponent(activeDir) + '&name=' + encodeURIComponent(name),
            makedir_r, "POST");
    }
}

function makedir_r(data)
{
    document.getElementById('loading').style.visibility = 'hidden';
    if(data.error) {
        alert(data.error);
        return;
    }

    window.location.reload();
}

function dirToId(dir)
{
    return 'dir_' + dir.replace(/\//g, '.');
}

function idToDir(id)
{
    return id.substr(4).replace(/[.]/g, '/');
}

function dir_expand(dirdiv, move)
{
    dirdiv = dirdiv.parentNode;
    if(dirdiv.lastChild.firstChild == null) {
        document.getElementById('loading').style.visibility = '';
        xHttp.request(
            '/admin/explorer/folders/?path=' + encodeURIComponent(idToDir(dirdiv.id)) + '&move=' + (move ? 1 : 0),
            dir_expand_r);
        return;
    }

    dirdiv.lastChild.style.display = '';
    dirdiv.firstChild.style.display = 'none';
    dirdiv.childNodes[1].style.display = '';
}

function dir_expand_r(data)
{
    document.getElementById('loading').style.visibility = 'hidden';
    if(data.error) {
        alert(data.error);
        return;
    }

    var dirdiv = document.getElementById(dirToId(data.id));
    dirdiv.firstChild.style.display = 'none';
    dirdiv.childNodes[1].style.display = '';
    dirdiv.lastChild.innerHTML = data.html;
    dirdiv.lastChild.style.display = '';
}

function dir_contract(obj)
{
    obj = obj.parentNode;
    obj.lastChild.style.display = 'none';
    obj.firstChild.style.display = '';
    obj.childNodes[1].style.display = 'none';
}

function openImageThumbnail(id)
{
    popUpWin('image-edit.php?mode=thb&path=' + encodeURIComponent(files[id].path) + '&id=' + id, 'image_thumbnail',
        'scrollbars=1,toolbar=0', 740, 600);
}

function open_image_edit(id)
{
    popUpWin('image-edit.php?path=' + encodeURIComponent(files[id].path) + '&id=' + id, 'image_edit',
        'scrollbars=1,toolbar=0', 740, 600);
}

function open_file_upload()
{
    popUpWin('file-upload.php?path=' + encodeURIComponent(activeDir), 'file_upload', 'toolbar=0', 640, 150);
}

function insertThumbnail(id)
{
    window.opener.document.getElementById(returnid).value = files[id].path;
    window.opener.document.getElementById(returnid + 'thb').src = files[id].path;
    if(window.opener.window.location.href.indexOf('id=') > -1) {
        window.opener.updateSide(window.opener.$('id').value);
    }
    window.close();
}

function renamefile(id)
{
    showhide('navn' + id + 'div');
    showhide('navn' + id + 'form');
    document.getElementById('loading').style.visibility = '';
    x_renamefile(id, files[id].path, '', document.getElementById('navn' + id + 'form').firstChild.firstChild.value,
        renamefile_r);
}

function renamefile_r(data)
{
    document.getElementById('loading').style.visibility = 'hidden';
    if(data.error) {
        document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value = files[data.id].name;
        alert(data.error);
        return;
    }

    if(data.yesno) {
        if(confirm(data.yesno)) {
            document.getElementById('loading').style.visibility = '';
            x_renamefile(data.id, files[data.id].path, '',
                document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value, 1, renamefile_r);
            return;
        }

        document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value = files[data.id].name;
        return;
    }

    document.getElementById('navn' + data.id + 'div').innerHTML = data.filename;
    document.getElementById('navn' + data.id + 'div').title = data.filename;
    document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value = data.filename;
    files[data.id].name = data.filename;
    files[data.id].path = data.path;
}

function deletefile(id)
{
    if(confirm('Vil du slette \'' + files[id].name + '\'?')) {
        document.getElementById('loading').style.visibility = '';
        xHttp.request('/admin/explorer/files/' + id + "/", deletefile_r, "DELETE");
    }
}

function deletefile_r(data)
{
    document.getElementById('loading').style.visibility = 'hidden';
    if(data.error) {
        alert(data.error);
        return;
    }

    removeTagById('tilebox' + data.id);
    files[data.id] = null;
}

function showhide(id)
{
    object = document.getElementById(id);
    object.style.display = (object.style.display === 'none') ? '' : 'none';
}

function swap_pannel(navn)
{
    // Save what mode we are in and what was searched for
    if(navn === 'search') {
        document.getElementById('files').innerHTML = '';
        document.getElementById('dir_bn').className = '';
        document.getElementById('dir').style.display = 'none';
        document.getElementById('search_bn').className = 'down';
        document.getElementById('search').style.display = '';
        if(document.getElementById('searchpath').value || document.getElementById('searchalt').value
            || getSelect('searchtype')) {
            searchfiles();
        }
    } else if(navn === 'dir') {
        document.getElementById('files').innerHTML = '';
        document.getElementById('search_bn').className = '';
        document.getElementById('search').style.display = 'none';
        document.getElementById('dir_bn').className = 'down';
        document.getElementById('dir').style.display = '';
        showfiles(activeDir);
    }
    return false;
}
