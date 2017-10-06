var files= [];
var contextMenuFileTile;
var contextMenuVideoTile;
var contextMenuswfTile;
var contextMenuflvTile;
var contextMenuImageTile;

function init()
{
    // attach context menus
    contextMenuFileTile=
        new Proto.Menu({ selector : '.filetile', "className" : 'menu desktop', menuItems : filetileContextMenu });
    contextMenuVideoTile=
        new Proto.Menu({ selector : '.videotile', "className" : 'menu desktop', menuItems : videotileContextMenu });
    contextMenuswfTile=
        new Proto.Menu({ selector : '.swftile', "className" : 'menu desktop', menuItems : swftileContextMenu });
    contextMenuflvTile=
        new Proto.Menu({ selector : '.flvtile', "className" : 'menu desktop', menuItems : flvtileContextMenu });
    contextMenuImageTile=
        new Proto.Menu({ selector : '.imagetile', "className" : 'menu desktop', menuItems : imagetileContextMenu });

    // Page fully loaded
    document.getElementById('loading').style.display= 'none';
}

function file(id, path, name, type, alt, width, height)
{
    this.id= id;
    this.path= path;
    this.name= name;
    this.alt= alt;

    this.type= 'unknown';
    if(path) {
        this.type= type;
    }

    this.width= screen.availWidth;
    if(width > 0) {
        this.width= width;
    }

    this.height= screen.availHeight;
    if(height > 0) {
        this.height= height;
    }
}

file.prototype.openfile= function() {
    viewlFile(this.type, this.id, this.width, this.height);
};

file.prototype.refreshThumb= function() {
    $('tilebox' + this.id)
        .firstChild.childNodes[1]
        .src= 'image.php?path=' + encodeURIComponent(this.path) + '&maxW=128&maxH=96&timestamp=' + unix_timestamp();
};

function unix_timestamp()
{
    return parseInt(new Date().getTime().toString().substring(0, 10))
}

function reattachContextMenus()
{
    contextMenuFileTile.reattach();
    contextMenuVideoTile.reattach();
    contextMenuswfTile.reattach();
    contextMenuflvTile.reattach();
    contextMenuImageTile.reattach();
}

var filetileContextMenu= [
    {
      "name" : 'Åbne',
      "className" : 'eye',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'filetile');
          var id= object.id.match(/[0-9]+/g);
          files[id[0]].openfile();
      }
    },
    {
      "name" : 'Flyt',
      "className" : 'folder_go',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'filetile');
          var id= object.id.match(/[0-9]+/g);
          open_file_move(id[0]);
      }
    },
    {
      "name" : 'Omdøb',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'filetile');
          var id= object.id.match(/[0-9]+/g);
          showfilename(id[0]);
      }
    },
    {
      "name" : 'Slet',
      "className" : 'delete',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'filetile');
          var id= object.id.match(/[0-9]+/g);
          deletefile(id[0]);
      }
    }
];

var videotileContextMenu= [
    {
      "name" : 'Åbne',
      "className" : 'film',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'videotile');
          var id= object.id.match(/[0-9]+/g);
          files[id[0]].openfile();
      }
    },
    {
      "name" : 'Flyt',
      "className" : 'folder_go',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'videotile');
          var id= object.id.match(/[0-9]+/g);
          open_file_move(id[0]);
      }
    },
    {
      "name" : 'Omdøb',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'videotile');
          var id= object.id.match(/[0-9]+/g);
          showfilename(id[0]);
      }
    },
    {
      "name" : 'Slet',
      "className" : 'delete',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'videotile');
          var id= object.id.match(/[0-9]+/g);
          deletefile(id[0]);
      }
    }
];

var swftileContextMenu= [
    {
      "name" : 'Åbne',
      "className" : 'film',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'swftile');
          var id= object.id.match(/[0-9]+/g);
          files[id[0]].openfile();
      }
    },
    {
      "name" : 'Flyt',
      "className" : 'folder_go',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'swftile');
          var id= object.id.match(/[0-9]+/g);
          open_file_move(id[0]);
      }
    },
    {
      "name" : 'Omdøb',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'swftile');
          var id= object.id.match(/[0-9]+/g);
          showfilename(id[0]);
      }
    },
    {
      "name" : 'Slet',
      "className" : 'delete',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'swftile');
          var id= object.id.match(/[0-9]+/g);
          deletefile(id[0]);
      }
    }
];

var flvtileContextMenu= [
    {
      "name" : 'Åbne',
      "className" : 'film',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'flvtile');
          var id= object.id.match(/[0-9]+/g);
          files[id[0]].openfile();
      }
    },
    {
      "name" : 'Flyt',
      "className" : 'folder_go',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'flvtile');
          var id= object.id.match(/[0-9]+/g);
          open_file_move(id[0]);
      }
    },
    {
      "name" : 'Omdøb',
      "className" : 'textfield_rename',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'flvtile');
          var id= object.id.match(/[0-9]+/g);
          showfilename(id[0]);
      }
    },
    {
      "name" : 'Slet',
      "className" : 'delete',
      "callback" : function(e) {
          var object= getContextMenuTarget(e.target, 'flvtile');
          var id= object.id.match(/[0-9]+/g);
          deletefile(id[0]);
      }
    }
];

if(window.location.href.match(/return=rtef/g)) {
    var imagetileContextMenu= [
        {
          "name" : 'Åbne',
          "className" : 'picture',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              files[id[0]].openfile();
          }
        },
        {
          "name" : 'Indsæt link',
          "className" : 'link',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              addfile(id[0]);
          }
        },
        {
          "name" : 'Rediger',
          "className" : 'picture_edit',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              open_image_edit(id[0]);
          }
        },
        {
          "name" : 'Beskrivelse',
          "className" : 'textfield_rename',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              edit_alt(id[0]);
          }
        },
        {
          "name" : 'Generere ikon',
          "className" : 'pictures',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              open_image_thumbnail(id[0]);
          }
        },
        {
          "name" : 'Flyt',
          "className" : 'folder_go',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              open_file_move(id[0]);
          }
        },
        {
          "name" : 'Omdøb',
          "className" : 'textfield_rename',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              showfilename(id[0]);
          }
        },
        {
          "name" : 'Slet',
          "className" : 'delete',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              deletefile(id[0]);
          }
        }
    ];
} else {
    var imagetileContextMenu= [
        {
          "name" : 'Åbne',
          "className" : 'picture',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              files[id[0]].openfile();
          }
        },
        {
          "name" : 'Rediger',
          "className" : 'picture_edit',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              open_image_edit(id[0]);
          }
        },
        {
          "name" : 'Beskrivelse',
          "className" : 'textfield_rename',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              edit_alt(id[0]);
          }
        },
        {
          "name" : 'Generere ikon',
          "className" : 'pictures',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              open_image_thumbnail(id[0]);
          }
        },
        {
          "name" : 'Flyt',
          "className" : 'folder_go',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              open_file_move(id[0]);
          }
        },
        {
          "name" : 'Omdøb',
          "className" : 'textfield_rename',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              showfilename(id[0]);
          }
        },
        {
          "name" : 'Slet',
          "className" : 'delete',
          "callback" : function(e) {
              var object= getContextMenuTarget(e.target, 'imagetile');
              var id= object.id.match(/[0-9]+/g);
              deletefile(id[0]);
          }
        }
    ];
}

function viewlFile(type, id, width, height)
{
    if(type == 'unknown') {
        var file= files[id].path;
    } else {
        var file= 'popup-' + type + '.php?url=' + encodeURIComponent(files[id].path);
    }
    popUpWin(file, 'file_view', 'toolbar=0', width, height);
}

function edit_alt(id)
{
    var newalt= prompt('Billed beskrivelse', files[id].alt);
    if(newalt != null && newalt != files[id].alt) {
        $('loading').style.visibility= '';
        x_edit_alt(id, newalt, edit_alt_r);
    }
}

function edit_alt_r(data)
{
    document.getElementById('loading').style.display= 'none';
    if(data.error) {
        alert(data.error);
        return;
    }

    files[data.id].alt= data.alt;
}

function searchfiles()
{
    document.getElementById('loading').style.display= '';
    qpath= document.getElementById('searchpath').value;
    qalt= document.getElementById('searchalt').value;
    qtype= getSelect('searchtype');

    setCookie('qpath', qpath, 360);
    setCookie('qalt', qalt, 360);
    setCookie('qtype', qtype, 360);
    // TODO only cancle requests relating to searchfiles
    sajax.cancel();
    x_searchfiles(qpath, qalt, qtype, showfiles_r);
}

function getSelect(id)
{
    object= document.getElementById(id);
    return object[object.selectedIndex].value;
}

function showfiles(dir, mode)
{
    // TODO, scroll to top.
    if(dir != '') {
        setCookie('admin_dir', dir, 360);
    } else {
        dir= getCookie('admin_dir');
    }

    document.getElementById('loading').style.display= '';
    if(mode == 0) {
        dirlist= document.getElementById('dir').getElementsByTagName('a');
        for(var i= 0; i < dirlist.length; i++) {
            dirlist[i].className= '';
        }
    }
    // TODO only cancle requests relating to showfiles
    sajax.cancel();
    x_showfiles(dir, showfiles_r);
}

function showfiles_r(data)
{
    inject_html(data);
    files= new Array();
    eval(data.javascript);
    reattachContextMenus();
}

function showfilename(id)
{
    document.getElementById('navn' + id + 'div').style.display= 'none';
    document.getElementById('navn' + id + 'form').style.display= '';
    document.getElementById('navn' + id + 'form').firstChild.firstChild.select();
    document.getElementById('navn' + id + 'form').firstChild.firstChild.focus();
}

function showdirname(nameObj)
{
    nameObj.style.display= 'none';
    nameObj.nextSibling.style.display= 'inline';
    nameObj.nextSibling.firstChild.childNodes[1].select();
    nameObj.nextSibling.firstChild.childNodes[1].focus();
}

function renamedir(newNameObj)
{
    newNameObj.parentNode.parentNode.style.display= 'none';
    newNameObj.parentNode.parentNode.previousSibling.style.display= '';
    document.getElementById('loading').style.display= '';
    x_renamefile(idToDir(newNameObj.parentNode.parentNode.parentNode.id),
        idToDir(newNameObj.parentNode.parentNode.parentNode.id), '', newNameObj.value, renamedir_r);
}

function renamedir_r(data)
{
    document.getElementById('loading').style.display= 'none';
    if(data.error) {
        var divdir= document.getElementById(dirToId(data.id));
        var form= divdir.getElementsByTagName('form');
        form[0].firstChild.childNodes[1].value= form[0].previousSibling.title;
        alert(data.error);
        return;
    }

    var formId= 'dir_' + data.id + 'form';
    if(data.yesno) {
        if(confirm(data.yesno)) {
            document.getElementById('loading').style.display= '';
            x_renamefile(data.id, document.getElementById(formId).lastChild.value, '',
                document.getElementById(formId).firstChild.value, 1, renamefile_r);
            return;
        }
        document.getElementById(formId).firstChild.value= document.getElementById('dir_' + data.id + 'name').innerHTML;
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

var popup= null;
function popUpWin(url, win, options, width, height)
{
    if(popup != null) {
        popup.close();
        popup= null;
    }
    if(options != '') {
        options+= ',';
    }
    popup= window.open(url, win, options + 'width=' + width + ',height=' + height + ',left=' +
            (screen.availWidth - width) / 2 + ',top=' + (screen.availHeight - height) / 2);
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
        x_deletefolder(deletefolder_r);
    }
}

function deletefolder_r(data)
{
    if(data.error) {
        alert(data.error);
    } else {
        setCookie('admin_dir', '/images', 360);
    }
    window.location.reload();
}

function makedir()
{
    if(name= prompt('Hvad skal mappen hede?', 'Ny mappe')) {
        document.getElementById('loading').style.display= '';
        x_makedir(name, makedir_r);
    }
}

function makedir_r(data)
{
    document.getElementById('loading').style.display= 'none';
    if(data.error) {
        alert(data.error);
        return;
    }
    // TODO make sure current folder has +- and room for subs
    // document.getElementById('loading').style.display = '';
    // x_listdirs(getCookie('admin_dir'), 0, dir_expand_r);
    setCookie(getCookie('admin_dir'), 1, 360);
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

function dir_expand(dirdiv, mode)
{
    dirdiv= dirdiv.parentNode;
    if(dirdiv.lastChild.firstChild == null) {
        document.getElementById('loading').style.display= '';
        x_listdirs(idToDir(dirdiv.id), !!mode, dir_expand_r);
        return;
    }

    dirdiv.lastChild.style.display= '';
    dirdiv.firstChild.style.display= 'none';
    dirdiv.childNodes[1].style.display= '';
    setCookie(idToDir(dirdiv.id), 1, 360);
}

function dir_expand_r(data)
{
    document.getElementById('loading').style.display= 'none';
    if(data.error) {
        alert(data.error);
        return;
    }

    var dirdiv= document.getElementById(dirToId(data.id));
    dirdiv.firstChild.style.display= 'none';
    dirdiv.childNodes[1].style.display= '';
    dirdiv.lastChild.innerHTML= data.html;
    dirdiv.lastChild.style.display= '';
    setCookie(data.id, 1, 360);
}

function dir_contract(obj)
{
    obj= obj.parentNode;
    obj.lastChild.style.display= 'none';
    obj.firstChild.style.display= '';
    obj.childNodes[1].style.display= 'none';
    setCookie(idToDir(obj.id), 0, -1);
}

function open_image_thumbnail(id)
{
    popUpWin('image-edit.php?mode=thb&path=image_thumbnail', 'toolbar=0', 740, 600);
}

/*TODO REMOVE THIS FUNCTION*/
function thumbimage(path)
{
    window.opener.document.getElementById(rte).value= path;
    generatethumbnail.close();
    window.close();
}

function open_image_edit(id)
{
    popUpWin(
        'image-edit.php?path=' + encodeURIComponent(files[id].path) + '&id=' + id, 'image_edit', 'toolbar=0', 740, 600);
}

function open_file_upload()
{
    popUpWin('file-upload.php', 'file_upload', 'toolbar=0', 640, 150);
}

function insertThumbnail(id)
{
    window.opener.document.getElementById(returnid).value= files[id].path;
    window.opener.document.getElementById(returnid + 'thb').src= files[id].path;
    if(window.opener.window.location.href.indexOf('side=redigerside') > -1) {
        window.opener.updateSide(window.opener.$('id').value);
    }
    window.close();
}

function renamefile(id)
{
    showhide('navn' + id + 'div');
    showhide('navn' + id + 'form');
    document.getElementById('loading').style.display= '';
    x_renamefile(id, files[id].path, '', document.getElementById('navn' + id + 'form').firstChild.firstChild.value,
        renamefile_r);
}

function renamefile_r(data)
{
    document.getElementById('loading').style.display= 'none';
    if(data.error) {
        document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value= files[data.id].name;
        alert(data.error);
        return;
    }

    if(data.yesno) {
        if(confirm(data.yesno)) {
            document.getElementById('loading').style.display= '';
            x_renamefile(data.id, files[data.id].path, '',
                document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value, 1, renamefile_r);
            return;
        }

        document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value= files[data.id].name;
        return;
    }

    document.getElementById('navn' + data.id + 'div').innerHTML= data.filename;
    document.getElementById('navn' + data.id + 'div').title= data.filename;
    document.getElementById('navn' + data.id + 'form').firstChild.firstChild.value= data.filename;
    files[data.id].name= data.filename;
    files[data.id].path= data.path;
}

function deletefile(id)
{
    if(confirm('vil du slette \'' + files[id].name + '\'?')) {
        document.getElementById('loading').style.display= '';
        x_deletefile(id, files[id].path, deletefile_r);
    }
}

function deletefile_r(data)
{
    document.getElementById('loading').style.display= 'none';
    if(data.error) {
        alert(data.error);
        return;
    }

    removeTagById('tilebox' + data.id);
    files[data.id]= null;
}

function addfile(id)
{
    window.opener.insertHTML('<a href="' + files[id].path + '" target="_blank">Klik her</a>');
    if(window.opener.window.location.href.indexOf('side=redigerside') > -1) {
        window.opener.updateSide(window.opener.$('id').value);
    }
    window.close();
}

function addimg(id)
{
    window.opener.insertHTML('<img src="' + files[id].path + '" alt="' + files[id].alt + '" title="" />');
    if(window.opener.window.location.href.indexOf('side=redigerside') > -1) {
        window.opener.updateSide(window.opener.$('id').value);
    }
    window.close();
}

function addflv(id, aspect, width, height)
{
    window.opener.insertHTML('<img name="placeholder" class="object" src="/admin/rtef/images/placeholder.gif" width="' +
        width + '" height="' + height + '" alt="&lt;object width=&quot;' + width + '&quot; height=&quot;' + height +
        '&quot; classid=&quot;clsid:d27cdb6e-ae6d-11cf-96b8-444553540000&quot; codebase=&quot;http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0&quot; align=&quot;middle&quot;&gt;&lt;param name=&quot;allowScriptAccess&quot; value=&quot;sameDomain&quot; /&gt;&lt;param name=&quot;movie&quot; value=&quot;/player' +
        aspect + '.swf?flvFilename=' + files[id].path +
        '&quot; /&gt;&lt;param name=&quot;allowFullScreen&quot; value=&quot;true&quot; /&gt;&lt;param name=&quot;quality&quot; value=&quot;high&quot; /&gt;&lt;param name=&quot;bgcolor&quot; value=&quot;#FFFFFF&quot; /&gt;&lt;embed src=&quot;/player' +
        aspect + '.swf?flvFilename=' + files[id].path + '&quot; width=&quot;' + width + '&quot; height=&quot;' +
        height +
        '&quot; bgcolor=&quot;#FFFFFF&quot; name=&quot;flash&quot; quality=&quot;high&quot; align=&quot;middle&quot; allowScriptAccess=&quot;sameDomain&quot; allowFullScreen=&quot;true&quot; type=&quot;application/x-shockwave-flash&quot; pluginspage=&quot;http://www.macromedia.com/go/getflashplayer&quot; /&gt;&lt;/object&gt;" />');
    if(window.opener.window.location.href.indexOf('side=redigerside') > -1) {
        window.opener.updateSide(window.opener.$('id').value);
    }
    window.close();
}

function addswf(id, width, height)
{
    window.opener.insertHTML('<img name="placeholder" class="object" src="/admin/rtef/images/placeholder.gif" width="' +
        width + '" height="' + height + '" alt="&lt;object width=&quot;' + width + '&quot; height=&quot;' + height +
        '&quot; classid=&quot;clsid:d27cdb6e-ae6d-11cf-96b8-444553540000&quot; codebase=&quot;http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0&quot; align=&quot;middle&quot;&gt;&lt;param name=&quot;allowScriptAccess&quot; value=&quot;sameDomain&quot; /&gt;&lt;param name=&quot;movie&quot; value=&quot;' +
        files[id].path +
        '&quot; /&gt;&lt;param name=&quot;allowFullScreen&quot; value=&quot;true&quot; /&gt;&lt;param name=&quot;quality&quot; value=&quot;high&quot; /&gt;&lt;param name=&quot;bgcolor&quot; value=&quot;#FFFFFF&quot; /&gt;&lt;embed src=&quot;' +
        files[id].path + '&quot; width=&quot;' + width + '&quot; height=&quot;' + height +
        '&quot; bgcolor=&quot;#FFFFFF&quot; name=&quot;flash&quot; quality=&quot;high&quot; align=&quot;middle&quot; allowScriptAccess=&quot;sameDomain&quot; allowFullScreen=&quot;true&quot; type=&quot;application/x-shockwave-flash&quot; pluginspage=&quot;http://www.macromedia.com/go/getflashplayer&quot; /&gt;&lt;/object&gt;" />');
    if(window.opener.window.location.href.indexOf('side=redigerside') > -1) {
        window.opener.updateSide(window.opener.$('id').value);
    }
    window.close();
}

function showhide(id)
{
    object= document.getElementById(id);
    object.style.display= (object.style.display == 'none') ? '' : 'none';
}

function swap_pannel(navn)
{
    // Save what mode we are in and what was searched for
    if(navn == 'search') {
        document.getElementById('files').innerHTML= '' document.getElementById('dir_bn').className= '';
        document.getElementById('dir').style.display= 'none';
        document.getElementById('search_bn').className= 'down';
        document.getElementById('search').style.display= '';
        if(document.getElementById('searchpath').value || document.getElementById('searchalt').value ||
            getSelect('searchtype')) {
            searchfiles();
        }
    } else if(navn == 'dir') {
        document.getElementById('files').innerHTML= '' setCookie('qpath', 0, -1);
        setCookie('qalt', 0, -1);
        setCookie('qtype', 0, -1);
        document.getElementById('search_bn').className= '';
        document.getElementById('search').style.display= 'none';
        document.getElementById('dir_bn').className= 'down';
        document.getElementById('dir').style.display= '';
        showfiles(getCookie('admin_dir'), 1);
    }
    return false;
}
