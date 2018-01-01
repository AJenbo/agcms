import openPopup from "./openPopup.js";
import {htmlEncode} from "./javascript.js";

class File {
    constructor(data) {
        this.id = data.id;
        this.path = data.path;
        this.name = data.name;
        this.description = data.description;
        this.mime = data.mime;
        this.width = data.width ? data.width : screen.availWidth;
        this.height = data.height ? data.height : screen.availHeight;
    }
    openfile() {
        let url = this.path;
        let width = this.width;
        let height = this.height;
        const type = this.type();
        if (type) {
            url = "/admin/explorer/files/" + this.id + "/";
            if (type === "audio") {
                width = 300;
                height = 40;
            }
        }
        openPopup(url, "fileView", width, height);
    }
    type() {
        if (this.mime === "image/gif" || this.mime === "image/jpeg" || this.mime === "image/png") {
            return "image";
        }

        if (this.mime.match(/^audio\//g)) {
            return "audio";
        }

        if (this.mime.match(/^video\//g)) {
            return "video";
        }

        return "";
    }
    addToEditor() {
        let data = "";
        let html = "<a href=\"" + htmlEncode(this.path) + "\" target=\"_blank\">" + htmlEncode(this.name) + "</a>";
        switch (this.type()) {
            case "image":
                html = "<img src=\"" + htmlEncode(this.path) + "\" title=\"\" alt=\"" + htmlEncode(this.description) +
                       "\" width=\"" + this.width + "\" height=\"" + this.height + "\" />";
                break;
            case "audio":
                data = {"classes": {"ckeditor-html5-audio": 1}, "src": this.path};
                data = JSON.stringify(data);
                data = encodeURIComponent(data);
                html =
                    "<div class=\"ckeditor-html5-audio cke_widget_element\" data-cke-widget-keep-attr=\"0\" data-widget=\"html5audio\" data-cke-widget-data=\"" +
                    data + "\"><audio controls=\"controls\" src=\"" + this.path + "\"></audio></div>";
                break;
            case "video":
                data = "<cke:video width=\"" + this.width + "\" height=\"" + this.height + "\" src=\"" +
                       htmlEncode(this.path) + "\" controls=\"controls\"></cke:video>";
                data = encodeURIComponent(data);
                html =
                    "<img class=\"cke-video\" data-cke-realelement=\"" + data +
                    "\" data-cke-real-node-type=\"1\" alt=\"Video\" title=\"Video\" src=\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22320%22%20height%3D%22240%22%3E%3C%2Fsvg%3E\" data-cke-real-element-type=\"video\" align=\"\">";
                break;
        }
        const element = window.opener.CKEDITOR.dom.element.createFromHtml(html);
        window.opener.CKEDITOR.instances.text.insertElement(element);
        window.close();
    }
    refresh() {
        const img = $("tilebox" + this.id).firstChild.childNodes[1];
        const fullSizeUrl = this.path;
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
        const url = img.src;
        img.src = url + "#";     // Set image to a temp path so we can reload it later
        $("reloader").src = url; // Start cache refreshing
    }
}

export default File;
