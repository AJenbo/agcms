/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config) {
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    config.allowedContent =
        "span{color}(liste,NyPris,Pris,XPris,gray); strong; em; u; sup; ul; ol; li; a[!href,target]; img[!src,width,height,alt,data-*]; p{text-align}; h2{text-align}; h3; cite; table; tr; td; video[!src,!controls,!width,!height]; audio[!src,!controls]";
    // TODO convert audio and oembed plugin to use an <img> container instead of a simple div wrapper
    config.extraAllowedContent =
        "div[data-*,!class](embeddedContent,oembed-provider-,oembed-provider-youtube,ckeditor-html5-audio);iframe[*]";

    config.toolbarGroups = [
        {name: "basicstyles", groups: ["basicstyles", "colors", "align", "cleanup", "others", "links", "list"]},
        {name: "paragraph", groups: ["styles", "insert", "document", "tools", "mode"]}
    ];

    config.removeButtons =
        "Video,Html5audio,JustifyBlock,Link,searchCode,CommentSelectedRange,UncommentSelectedRange,autoFormat,AutoComplete";

    config.tokenStart = "{{";
    config.tokenEnd = "}}";
    config.availableTokens = [];

    config.uploadUrl = "/admin/explorer/files/";

    config.forcePasteAsPlainText = true;

    config.baseHref = "/";

    config.entities = false;
    config.entities_additional = "";
    config.entities_latin = false;
    config.entities_processNumerical = false;

    config.disableObjectResizing = true;
    config.disableNativeSpellChecker = false;

    config.language = "da";
    config.height = 420;

    config.stylesSet = [
        {"name": "Normal", "element": "p"}, {"name": "Overskrift 2", "element": "h2"},
        {"name": "Overskrift 3", "element": "h3"},
        {"name": "NyPris", "element": "span", "attributes": {"class": "NyPris"}},
        {"name": "Pris", "element": "span", "attributes": {"class": "Pris"}},
        {"name": "XPris", "element": "span", "attributes": {"class": "XPris"}},
        {"name": "gray", "element": "span", "attributes": {"class": "gray"}}
    ];
};
