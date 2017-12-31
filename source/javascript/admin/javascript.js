function genericCallback(data) {
    $("loading").style.visibility = "hidden";
    if (data === null || data.error) {
        return false;
    }

    return true;
}

function reloadCallback(data) {
    window.location.reload();
}

function injectHtml(data) {
    if (!genericCallback(data)) {
        return;
    }

    $(data.id).innerHTML = data.html;
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

function htmlEncode(value) {
    var div = document.createElement("div");
    div.innerText = value;
    return div.innerHTML;
}

function removeTagById(id) {
    var obj = $(id);
    if (!obj) {
        return;
    }
    obj.parentNode.removeChild(obj);
}

export {genericCallback, reloadCallback, injectHtml, setCookie, getCookie, getSelectValue, htmlEncode, removeTagById};
