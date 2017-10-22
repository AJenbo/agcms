/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config) {
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    config.allowedContent
        = 'span{color}(liste,nypris,pris,xpris,gray); strong; em; u; sup; ul; ol; li; a[!href,target]; img[!src,alt,width,height](imgleft,imgright); p{text-align}; h2{text-align}; h3; cite; table; tr; td; video[!src,!controls,!width,!height]; audio[!src,!controls]';
    // TODO convert audio and oembed plugin to use an <img> container instead of a simple div wrapper
    config.extraAllowedContent
        = 'div[data-*,!class](embeddedContent,oembed-provider-,oembed-provider-youtube,ckeditor-html5-audio);iframe[*]';

    config.toolbarGroups = [
        { name : 'basicstyles', groups : [ 'basicstyles', 'colors', 'align', 'cleanup', 'others', 'links', 'list' ] },
        { name : 'paragraph', groups : [ 'styles', 'insert', 'document', 'tools', 'mode' ] }
    ];

    config.removeButtons
        = 'Format,Video,Html5audio,JustifyBlock,Link,searchCode,CommentSelectedRange,UncommentSelectedRange,autoFormat,AutoComplete';

    config.tokenStart = '{{';
    config.tokenEnd = '}}';
    config.availableTokens = [];

    config.uploadUrl = '/admin/upload/';

    config.forcePasteAsPlainText = true;

    config.baseHref = '/';

    config.entities = false;
    config.entities_additional = '';
    config.entities_latin = false;
    config.entities_processNumerical = false;

    config.disableObjectResizing = true;
    config.disableNativeSpellChecker = false;

    config.language = 'da';
    config.height = 420;

    config.stylesSet = [
        { name : 'Normal', element : 'p' }, { name : 'Overskrift 2', element : 'h2' },
        { name : 'Overskrift 3', element : 'h3' },
        { name : 'nypris', element : 'span', attributes : { 'class' : 'nypris' } },
        { name : 'pris', element : 'span', attributes : { 'class' : 'pris' } },
        { name : 'xpris', element : 'span', attributes : { 'class' : 'xpris' } },
        { name : 'gray', element : 'span', attributes : { 'class' : 'gray' } }
    ];
};
