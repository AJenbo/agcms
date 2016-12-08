////////////////////////////////////////////////////////////////////////////////
//
// Richtext Editor: Fork (RTEF) VERSION: 0.006
// Released: 19/06/2008
// For the latest release visit http://rtef.info
// For support visit http://rtef.info/deluxebb
//
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
//
// The MIT License
//
// Copyright (c) 2006 Timothy Bell
//
// Permission is hereby granted, free of charge, to any person obtaining a copy 
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights 
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
// copies of the Software, and to permit persons to whom the Software is 
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in 
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
// SOFTWARE.
//
////////////////////////////////////////////////////////////////////////////////

//TODO Look for TODO's in html files
//TODO Search for TODO in this file
//TODO make it resizable
//TODO status bar is 1 pixel abouve 0 in IE5.5 & IE6 if only one RTEF is pressent.
//TODO Insert base url parameter in iframe so that pages from a different domain can be edited.

// Constants
var minWidth = 130;					// minumum width
var wrapWidth = 1268;				// width at which all icons will appear on one bar
var maxchar = 64000;				// maximum number of characters per save
var zeroBorder = "#c0c0c0";			// guideline color - see showGuidelines()

// Pointers
var InsertChar;
var InsertTable;
var InsertLink;
var InsertImg;
var dlgReplace;
var dlgPasteText;
var dlgPasteWord;

// Strings
var selectedText = '';

//Init Variables & Attributes
var ua = navigator.userAgent.toLowerCase();
var isIE = ((ua.indexOf("msie") != -1) && (ua.indexOf("opera") == -1) && (ua.indexOf("webtv") == -1))? true:false;
var IEVersion = parseFloat(ua.substring(ua.lastIndexOf("msie ")+5));
var	isOpera = (ua.indexOf("opera") != -1)? true:false;
//if(isOpera) var OperaVersion = parseFloat(ua.substring(ua.lastIndexOf("Opera/") + 7));
//needs testing on iPhone and othere webkit based browsers
var	isSafari = (ua.indexOf("webkit") != -1)? true:false;
var SafariVersion = parseFloat(ua.substring(ua.lastIndexOf("webkit/") + 7));
if(!isSafari) var isGecko = (ua.indexOf("gecko") != -1)? true:false;
else var isGecko = false;
//Konqueror still doen't support designmode as of 11/2007
//var isKonqueror = (ua.indexOf("konqueror") != -1)? true:false;

if(isSafari) var safariDummyRange = null;
if(isIE && IEVersion < 7) var fullscreenint = false;

var rng;
var currentRTE;
var allRTEs = "";
var obj_width;
var obj_height;
var imagesPath;
var includesPath;
var cssFile;
var generateXHTML = true;
var isRichText = false;
//check to see if designMode mode is available
//RTEF needs some work to get it working with IE 5 if ever
if(document.getElementById && document.designMode && !(isIE && IEVersion < 5.5))
	isRichText = true;
//for testing standard textarea, uncomment the following line
//isRichText = false;
/*
function rteSafe(html) {
	//escape or convert chars that could break RTE
	var replacements = new Array (
	new RegExp(String.fromCharCode(145),'g'), "'",
	new RegExp(String.fromCharCode(146),'g'), "'",
	new RegExp("'"), "&#39;",
	//convert all types of double quotes
	new RegExp(String.fromCharCode(147),'g'), "\"",
	new RegExp(String.fromCharCode(148),'g'), "\"",
	//new RegExp("\""), "&#34;",
	//replace carriage returns & line feeds
	new RegExp("[\r\n]",'g'), " ");
	html = trim(html);
	for (i=0; i<replacements.length; i = i+2)
		html = html.replace(replacements[i], replacements[i+1]);
	return html;
}
*/
function initRTE(imgPath, incPath, css, genXHTML) {
	// CM 05/04/05 check args for compatibility with old RTE implementations
	if (arguments.length == 3)
		genXHTML = generateXHTML;
	//set paths vars
	imagesPath = imgPath;
	includesPath = incPath;
	cssFile = css;
	generateXHTML = genXHTML;
	if(isRichText)
		document.write('<style type="text/css">@import "' + includesPath + 'rte.css";</style>');
}

function writeRichText(rte, html, css, width, height, buttons, resizable, fullscreen) {
	//generate the RTE interface
	currentRTE = rte;
	var rtehtml = '';
	if(allRTEs.length > 0) allRTEs += ";";
	allRTEs += rte;
	// CM 06/04/05 stops single quotes from messing everything up
	html=replaceIt(html,'\'','&apos;');
	html=html.replace(/\s/,' ');
	// CM 05/04/05 a bit of juggling for compatibility with old RTE implementations
	if (arguments.length == 6) {
		fullscreen = false;
		buttons = height;
		height = width;
		width = css;
		css = "";
	}
	if(isIE && IEVersion < 7) fullscreenint = fullscreen;
	var width;
	if(fullscreen) {
		buttons = true;
		if(isRichText) {
			window.onresize = function() { resizeRTE(rte, true) };
		}
		document.body.style.margin = "0px";
		document.body.style.overflow = "hidden";
		//adjust maximum table widths
		findSize("");
		width = obj_width;
	} else {
		fullscreen = false;
		if(IEVersion == 5.5) {
			width = width+2;
			height = height+2;
		}
	}
	if(width < minWidth) {
		width = minWidth;
	}
	if(isRichText) {
		/* TPOST MODIFICATION - applet */
			html = unfillPlaceholders(html);
		/* END MODIFICATION */
		var rte_css = "";
		if(css.length > 0) {
			rte_css = css;
		} else {
			rte_css = cssFile;
		}
		//preload of the design icon (dosn't work in Opera)
		rtehtml += '<img style="display:none" src="'+imagesPath+'design.gif" alt="preload" />';
		if(!fullscreen) {
			rtehtml += '<div class="rteDiv" id="'+rte+'Div" style="width:'+width+'px;height:'+height+'px;position:relative"';
			if(isIE) rtehtml += ' unselectable="on"';
			rtehtml += '>';
		} else {
			rtehtml += '<div class="rteDiv" id="'+rte+'Div" style="position:absolute;left:0px;right:0px;top:0px;bottom:0px;border:0px"';
			if(isIE) rtehtml += ' unselectable="on"';
			rtehtml += '>';
		}
		if(buttons) {
			rtehtml += '<div class="buttons" id="buttons_'+rte+'"';
			if(isIE) rtehtml += ' unselectable="on"';
			rtehtml += '>';
			rtehtml += insertBar();
			if(fullscreen) {
				rtehtml += '<input accesskey="s" type="image" src="'+imagesPath+'save.gif" alt="'+lblSave+'" title="'+lblSave+'" onmouseover="this.className=\'rteImgUp\'" onmouseout="this.className=\'\'" />';
			}
			if(!isOpera && !(isIE && (IEVersion == 7 || IEVersion > 7)) && !(isSafari && SafariVersion > 500)) {
				//Opera 9.23, IE7, Safari 3 prints the enitre rtef as it looks on screen instead of just the content
				//Firefox 2.0.0.9 and Safari 2 does it correctly
				rtehtml += insertBn(lblPrint,"print.gif","rtePrint('"+rte+"')");
			}
			rtehtml += insertBn(lblUndo,"undo.gif","rteCommand('"+rte+"','Undo')");
			rtehtml += insertBn(lblRedo,"redo.gif","rteCommand('"+rte+"','Redo')");
/*			if(isIE || isSafari) {
				rtehtml += insertBn(lblCut,"cut.gif","rteCommand('"+rte+"','Cut')");
				rtehtml += insertBn(lblCopy,"copy.gif","rteCommand('"+rte+"','Copy')");
			}
			if(isIE) rtehtml += insertBn(lblPaste,"paste.gif","rteCommand('"+rte+"','Paste')");
*/			rtehtml += insertBn(lblPasteText,"pastetext.gif","dlgLaunch('"+rte+"','text', 355, 395)");
//			rtehtml += insertBn(lblPasteWord,"pasteword.gif","dlgLaunch('"+rte+"','word', 355, 395)");
			rtehtml += insertSep();
			//works in safari 3
			if(!(isSafari && SafariVersion < 500)) {
//				rtehtml += insertBn(lblSelectAll,"selectall.gif","toggleSelection('"+rte+"')");
				rtehtml += insertBn(lblUnformat,"unformat.gif","rteCommand('"+rte+"','RemoveFormat');rteCommand('"+rte+"','JustifyNone');rteCommand('"+rte+"','FormatBlock', '<p>')");
//				rtehtml += insertSep();
			}
			if(isSafari && SafariVersion < 500)rtehtml += insertBn(lblUnformat,"unformat.gif","insertHTML(getText('"+rte+"'))");
			rtehtml += insertSep();
			rtehtml += insertBn(lblBold,"bold.gif","rteCommand('"+rte+"','Bold')");
			rtehtml += insertBn(lblItalic,"italic.gif","rteCommand('"+rte+"','Italic')");
			rtehtml += insertBn(lblUnderline,"underline.gif","rteCommand('"+rte+"','Underline')");
//			if(!(isSafari && SafariVersion < 500)) rtehtml += insertBn(lblStrikeThrough,"strikethrough.gif","rteCommand('"+rte+"','StrikeThrough')");
//			else rtehtml += insertBn(lblStrikeThrough,"strikethrough.gif","insertHTML('<strike>'+getText('"+rte+"')+'</strike>')");
			rtehtml += insertSep();
			if(!isIE && !isSafari) {
				rtehtml += insertBn(lblIncreasefontsize,"increasefontsize.gif","rteCommand('"+rte+"','IncreaseFontSize')");
				rtehtml += insertBn(lblDecreasefontsize,"decreasefontsize.gif","rteCommand('"+rte+"','DecreaseFontSize')");
			}
			rtehtml += insertBn(lblSuperscript,"superscript.gif","rteCommand('"+rte+"','Superscript')");
			rtehtml += insertBn(lblSubscript,"subscript.gif","rteCommand('"+rte+"','Subscript')");
			rtehtml += insertSep();
			rtehtml += insertBn(lblAlgnLeft,"left_just.gif","rteCommand('"+rte+"','JustifyLeft')");
			rtehtml += insertBn(lblAlgnCenter,"centre.gif","rteCommand('"+rte+"','JustifyCenter')");
			rtehtml += insertBn(lblAlgnRight,"right_just.gif","rteCommand('"+rte+"','JustifyRight')");
//			rtehtml += insertBn(lblJustifyFull,"justifyfull.gif","rteCommand('"+rte+"','JustifyFull')");
			rtehtml += insertSep();
			rtehtml += insertBn(lblTextColor,"textcolor.gif","dlgColorPalette('"+rte+"','ForeColor')","ForeColor_"+rte);
			rtehtml += insertBn(lblBgColor,"bgcolor.gif","dlgColorPalette('"+rte+"','hilitecolor')","hilitecolor_"+rte);
			rtehtml += '<br />';
			rtehtml += insertBar();
			if(!(isSafari && SafariVersion < 500)) {
				rtehtml += '<select id="FormatBlock_'+rte+'" onchange="selectFont(\''+rte+'\', this.id)">';
				rtehtml += lblFormat;
				rtehtml += '</select>';
			}
/*
			if(!(isSafari && SafariVersion < 500))
				rtehtml += '<select id="FontName_'+rte+'" onchange="selectFont(\''+rte+'\', this.id);">';
			else
				rtehtml += '<select id="FontName_'+rte+'">';
			rtehtml += lblFont;
			rtehtml += '</select>';
			if(isSafari && SafariVersion < 500)rtehtml += insertBn(lblApplyFont,"applyfont.gif","selectFont('"+rte+"', 'FontName_"+rte+"')");
			if(!(isSafari && SafariVersion < 500)) {
				rtehtml += '<select id="FontSize_'+rte+'" onchange="selectFont(\''+rte+'\', this.id)">';
				rtehtml += lblSize;
				rtehtml += '</select>';
			}
*/
			if(!(isSafari && SafariVersion < 500)) {
				rtehtml += insertBn(lblOL,"numbered_list.gif","rteCommand('"+rte+"','InsertOrderedList', '')");
				rtehtml += insertBn(lblUL,"list.gif","rteCommand('"+rte+"','InsertunOrderedList', '')");
				rtehtml += insertBn(lblOutdent,"outdent.gif","rteCommand('"+rte+"','Outdent')");
				rtehtml += insertBn(lblIndent,"indent.gif","rteCommand('"+rte+"','Indent')");
				rtehtml += insertSep();
			}
			if(!(isSafari && SafariVersion < 500)) rtehtml += insertBn(lblHR,"hr.gif","rteCommand('"+rte+"','InsertHorizontalRule', '')");
			else rtehtml += insertBn(lblHR,"hr.gif","insertHTML('<hr /><br />')");
			rtehtml += insertSep();
			rtehtml += insertBn(lblInsertChar,"special_char.gif","dlgLaunch('"+rte+"','char', 445, 112)");
			rtehtml += insertBn(lblInsertLink,"hyperlink.gif","dlgLaunch('"+rte+"','link', 510, 180)");
			if(!(isSafari && SafariVersion < 500)) rtehtml += insertBn(lblUnLink,"unlink.gif","rteCommand('"+rte+"','Unlink')");
			rtehtml += insertBn(lblAddImage,"image.gif","dlgLaunch('"+rte+"','image', 750, 512)");
			rtehtml += insertBn(lblInsertTable,"insert_table.gif","dlgLaunch('"+rte+"','table', 450, 155)");
			rtehtml += insertSep();
			rtehtml += insertBn(lblSearch,"replace.gif","dlgLaunch('"+rte+"','replace', 535, 175)");
//			rtehtml += insertBn(lblWordCount,"word_count.gif","countWords('"+rte+"')");
//			if(isIE) rtehtml += '<img src="'+imagesPath+'spellcheck.gif" alt="'+lblSpellCheck+'" title="'+lblSpellCheck+'" onmousedown="checkspell();this.className=\'rteImgDn\'" onmouseover="this.className=\'rteImgUp\'" onmouseout="this.className=\'\'" />';
			rtehtml += '</div>';
		}
		rtehtml += '<iframe onfocus="dlgCleanUp();" id="iframe'+rte+'" frameborder="0" src="' + includesPath + 'blank.htm" style="top:0px;';
		if(!fullscreen && (isIE || isSafari)) {
			rtehtml += 'margin-left:-1px;border-left:1px solid #d2d2d2;height:';
			if(!buttons)
				rtehtml += height+'px;';
			else
				rtehtml += '0px;';
			if(isIE) {
				rtehtml += 'border-right:1px solid #d2d2d2; border-bottom:1px solid #d2d2d2;';
				if(IEVersion == 5.5)rtehtml += 'width:'+(width-2)+'px;';
			}
		} else {
			rtehtml += 'bottom:26px';
		}
		rtehtml += '"></iframe>';
		rtehtml += '<div class="statusbar" id="vs'+rte+'"';
		if(isIE)rtehtml += ' unselectable="on"';
		rtehtml += '><img class="rteBar" src="'+imagesPath+'bar.gif" alt="" /><a href="#" onclick="toggleHTMLSrc(\''+rte+'\', ' + buttons + '); return false;"><img id="imgSrc'+rte+'" src="'+imagesPath+'code.gif" alt="" /><span id="_xtSrc'+rte+'" style="font-family:tahoma,sans-serif">'+lblModeHTML+'</span></a></div>';
		rtehtml += '<iframe id="cp'+rte+'" src="' + includesPath + 'palette.htm" scrolling="no" frameborder=0 style="margin:-26px 0 0 0;display:none;width:142px;height:98px"></iframe>';
		rtehtml += '<input type="hidden" value="" name="'+rte+'" id="'+rte+'" />';
		rtehtml += '</div>';
		document.write(rtehtml);
		enableDesignMode(rte, html, rte_css);
		document.getElementById(rte).value = html;
	} else {
		buttons = false;
		if(fullscreen && height > 90) {
			height = (height - 75);
		}
		// CM non-designMode() UI
		html = parseBreaks(html);
		rtehtml += '<div style="font:12px Verdana, Arial, Helvetica, sans-serif;width: ' + (width+2) + 'px;padding:15px;">';
		rtehtml += '<div style="color:gray">'+lblnon_designMode+'</div><br />';
		rtehtml += '<input type="radio" name="' + rte + '_autobr" value="1" checked="checked" onclick="autoBRon(\'' + rte + '\');" /> '+lblAutoBR+'<input type="radio" name="' + rte + '_autobr" value="0" onclick="autoBRoff(\'' + rte + '\');" />'+lblRawHTML+'<br />';
		rtehtml += '<textarea name="'+rte+'" id="'+rte+'" style="width:100%; height: ' + (height-80) + 'px;">' + html + '</textarea>';

		if(fullscreen) rtehtml += '<br /><input type="submit" value="'+lblSubmit+'" />';
		rtehtml += '</div>';
		document.write(rtehtml);
	}
	//Give the browser some time to load the menu
//	setTimeout("resizeRTE('"+rte+"')",350);
}

function insertBar() {
	//insert the start of a new tool bar
	return '<img class="rteBar" src="'+imagesPath+'bar.gif" alt="" />';
}

function insertSep() {
	//insert a seporation line in the tool bar
	return '<img class="rteSep" src="'+imagesPath+'blackdot.gif" alt="" />';
}

function insertBn(name, image, command, id) {
	//Insert a button in the tool bar
	var img = "<img";
	if(id!=null) {
		img = "<img id='"+id+"'";
	}
		img += ' onmousemove="return false" src="'+imagesPath+image+'" alt="'+name+'" title="'+name+'" onmousedown="'+command+';';
		if(isIE && IEVersion < 7) img += 'this.className=\'rteImgDn\';';
		img += 'return false"';
		if(isIE && IEVersion < 7) img += ' onmouseover="this.className=\'rteImgUp\'" onmouseout="this.className=\'\'"';
		img += ' >';
		return img;
}

function enableDesignMode(rte, html, css) {
	//Write the content of the iframe and make it editable
	var frameHtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	frameHtml += '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'+ lang +'" dir="'+ lang_direction +'" id="'+ rte +'">';
	frameHtml += '<head>';
	frameHtml += '<title>'+rte+'</title>';
	frameHtml += '<meta name="generator" content="RTEF 0.007 (WYSIWYG editor)">';
	frameHtml += '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	frameHtml += '<meta http-equiv="Content-Language" content="' + lang + '" />';
	if(css.length > 0) {
		frameHtml += '<style type="text/css">@charset "utf-8"; .object {background-color:#c0c0c0; background-image:url(\''+imagesPath+'object.gif\'); background-position:center; background-repeat:no-repeat;}</style>';
		frameHtml += '<link media="all" type="text/css" href="' + css + '" rel="stylesheet" title="design" />';
		frameHtml += '<link media="all" type="text/css" href="' + includesPath + 'html.css" rel="stylesheet" title="html" />';
	} else {
		frameHtml += '<style type="text/css">@charset "utf-8"; .object {background-color:#c0c0c0; background-image:url(\''+imagesPath+'object.gif\'); background-position:center; background-repeat:no-repeat;} body {background:#FFFFFF;margin:8px;padding:0px;}</style>';
		frameHtml += '<link media="all" type="text/css" href="' + includesPath + 'html.css" rel="stylesheet" title="html" />';
	}
	frameHtml += '</head><body style="overflow-y:scroll">'+html+'</body></html>';
	if (isIE) {
		var oRTE = returnRTE(rte).document;
		oRTE.open("text/html","replace");
		oRTE.write(frameHtml);
		oRTE.close();
		oRTE.designMode = "On";
		showGuidelines(rte);
		addLoadEvent(function() { resizeRTE(rte); swapCssLink(rte, "html") });
		
		//time based function
		//Tested on 1.8mhz Semptron using IE 5.5 - 7 and 3Ghz Core 2 duo using IE 7
		//setTimeout('swapCssLink("'+rte+'", "html")',1);
		rteCommand(rte, "LiveResize", true);
	} else {
		try {
			if(!isSafari) {
				addLoadEvent(function() { document.getElementById('iframe'+rte).contentDocument.designMode = "on"; });
			} else {
				document.getElementById('iframe'+rte).contentDocument.designMode = "on";
			}
			var oRTE = returnRTE(rte).document;
			oRTE.open("text/html","replace");
			oRTE.write(frameHtml);
			oRTE.close();
			if(isGecko) {
				//attach a keyboard handler for gecko browsers to make keyboard shortcuts work
				oRTE.addEventListener("keypress", geckoKeyPress, true);
				oRTE.addEventListener("focus", function () {dlgCleanUp(); }, false);
			}
			showGuidelines(rte);
			//needed for editing some attributes
			if(isSafari)applySafarijunk(rte);
			if(!isSafari)resizeRTE(rte);
			//Tested on 350mhz PPC using Safari 2 and 3Ghz Core 2 duo Vista using Safari 3
			else setTimeout("resizeRTE('"+rte+"')",64);
			if(!isOpera)swapCssLink(rte, "html");
			//Tested on 1.8mhz Semptron and 3Ghz Core 2 duo using Opera 9.23
			else setTimeout('swapCssLink("'+rte+'", "html")',175);
			//Tested on 1.8mhz Semptron and 3Ghz Core 2 duo using FF 2.0.0.9
			//this is the old way to disable CSS styleing it was replaced by styleWithCSS but i don't know in what version this was changed - Anders Jenbo
			if(isGecko) setTimeout('rteCommand("'+rte+'", "useCSS", true)',750);
			if(isGecko) setTimeout('rteCommand("'+rte+'", "styleWithCSS", false)', 750);
		}
		catch(e) {
			//gecko and Safari may take some time to enable design mode.
			//Keep looping until able to set.
			if(isGecko || isSafari) {
				setTimeout("enableDesignMode('"+rte+"', '"+html+"', '"+css+"');", 200);
			} else {
				return false;
			}
		}
	}
}

function swapCssLink(rte, title) {
	//disable or enable the css files loaded in the iframe
	var linkNodes = returnRTE(rte).document.getElementsByTagName("link");
	for ( i = 0; i < linkNodes.length; i++ ) {
		if(linkNodes[i].getAttribute("title") == title)
			linkNodes[i].disabled = true;
		else
			linkNodes[i].disabled = false;
	}
}

function addLoadEvent(func) {
	//add and onload even to the page with out destrying the original
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else {
		window.onload = function() {
			oldonload();
			func();
		};
	}
}

function returnRTE(rte) {
	//return the object of the document in the iframe
	if(isIE || isOpera)
		return frames['iframe'+rte];
	else
		return document.getElementById('iframe'+rte).contentWindow;
}

function updateRTE(rte) {
	//Update a specific RTE
	if(isRichText) {
		dlgCleanUp();			// Closes Pop-ups
		stripGuidelines(rte);	// Removes Table Guidelines
		if(isSafari)stripSafarijunk(rte);
	}
	parseRTE(rte);
}

function updateRTEs() {
	//update all RTEs
	var vRTEs = allRTEs.split(";");
	for(var i=0; i<vRTEs.length; i++) {
		updateRTE(vRTEs[i]);
	}
}

function parseRTE(rte) {
	//Sync rte.value with iframe
	if (!isRichText) {
		autoBRoff(rte); // sorts out autoBR
		return false;
	}
	var oRTE = returnRTE(rte);
	if(isSafari) oRTE.focus();
	if(isRichText) {
		//if viewing source, switch back to design view
		if(document.getElementById("_xtSrc"+rte).innerHTML == lblModeRichText) {
			if(document.getElementById("buttons_"+rte)) {
				toggleHTMLSrc(rte, true);
			} else {
				toggleHTMLSrc(rte, false);
			}
			stripGuidelines(rte);
		}
		setHiddenVal(rte);
	}
}

function setHiddenVal(rte) {
	//set hidden form field value for current rte
	var oHdnField = document.getElementById(rte);
	//convert html output to xhtml (thanks Timothy Bell and Vyacheslav Smolin!)
	if(oHdnField.value==null) {
		oHdnField.value = "";
	}
	var sRTE = returnRTE(rte).document.body;
	var output = sRTE.innerHTML;
	
	//properly escape attribute values for Opera and Safari
	//<tag att="\""> => <tag alt="&quot;">
	//<tag att="<"> => <tag alt="&lt;">
	//<tag att=">"> => <tag alt="&gt;">
	//TODO this also affects test\"test
	if(isOpera || isSafari) {
		if(isOpera) {
			output = output.replace(/\\"/g, '&quot;');
			output = output.replace(/\\'/g, '&#39;');
		}
		
		//TODO use revers and lookahead to include escaped quotes in the attribute value and then move the two line abouve insiside of the one below
		//var output = 'testt'.reverse().replace(/t(?=se)/g, 'x').reverse();,
		
		var attributes = output.match(/\s[a-z]+=("|').*?(\1)/gi);
		if (attributes != null) {
			for (var i=0; i < attributes.length; i++) {
				var attribute = attributes[i];
				if(isOpera)
					var attribute = attribute.replace(/</g, '&lt;');
				var attribute = attribute.replace(/>/g, '&gt;');
				output = output.replace(attributes[i], attribute);
			}
		}
	}
	
	if(generateXHTML) {
		try {
			//Generate nice XHTML
			output = getXHTML(output);
		}
		catch(e) {
		}
	}
	
	//Convert URI to URN, makes links the same for all browsers (if Firefox gets URNs to begin with) - Anders Jenbo
	//TODO Drag and dropping in FireFox will change and break the url
	var re = new RegExp('([a-z]+=["]{1})http[s]*://'+document.domain+'(.*?["]{1})', "gi");
	output = output.replace(re, '$1$2').replace(/\s/g, ' ');
	oHdnField.value = output;

	// fix to replace special characters added here (not needed in utf8):
	//oHdnField.value = replaceSpecialChars(oHdnField.value);

	//if there is no content (other than formatting) set value to nothing
	if(stripHTML(oHdnField.value.replace("&nbsp;", " ")) == "" &&
	oHdnField.value.toLowerCase().search("<hr") == -1 &&
	oHdnField.value.toLowerCase().search("<img") == -1) {
		oHdnField.value = "";
	}

	/* TPOST MODIFICATION - applet*/
	//TODO this is dependant on getXHTML for IE
	oHdnField.value = fillPlaceholders(oHdnField.value);
	/* END TPOST MODIFICATION */
}

function rteCommand(rte, command, option) {
	//Fire execCommands apropiratly
	currentRTE = rte;
	dlgCleanUp();
  //function to perform command
	var oRTE = returnRTE(rte);
	try {
		oRTE.focus();
		oRTE.document.execCommand(command, false, option);
		oRTE.focus();
	} catch(e) {
//		alert(e);
//		setTimeout("rteCommand('" + rte + "', '" + command + "', '" + option + "');", 10);
	}
}

function toggleHTMLSrc(rte, buttons) {
	//switch betwean code editing and visual editing
	dlgCleanUp();
	//contributed by Bob Hutzel (thanks Bob!)
	var cRTE = document.getElementById('iframe'+rte);
	var hRTE = document.getElementById(rte);
	var tRTE = document.getElementById("_xtSrc"+rte);
	var iRTE = document.getElementById("imgSrc"+rte);
	var oRTE = returnRTE(rte).document;
	var htmlSrc;
	if(tRTE.innerHTML == lblModeHTML) {
		//We are going in to HTML mode
		tRTE.innerHTML = lblModeRichText;
		iRTE.src = imagesPath+'design.gif';
		stripGuidelines(rte);
		if(isSafari)stripSafarijunk(rte);
		if(buttons) {
			showHideElement("buttons_" + rte, "hide", true);
			resizeRTE(rte);
		}
		setHiddenVal(rte);
		oRTE.body.innerHTML = formathtml(hRTE.value);
		if(isIE) {
			// This resets the undo/redo buffer.
			document.getElementById(rte).value = returnRTE(rte).document.body.innerHTML;
		}
		swapCssLink(rte, "design");
	} else {
		//we going in to design mode
		obj_height = parseInt(cRTE.style.height);
		tRTE.innerHTML = lblModeHTML;
		iRTE.src = imagesPath+'code.gif';
		if(buttons) {
			showHideElement("buttons_" + rte, "show", true);
			resizeRTE(rte);
		}
		if(isIE) {
/* TPOST MODIFICATION - applet */
		    var htmlSrc = oRTE.body.innerText.replace(/[\r\n]/g, '').replace(/\s/g, ' ');
		    htmlSrc = unfillPlaceholders(htmlSrc);
			oRTE.body.innerHTML = htmlSrc;
			// ORIGINAL
			//oRTE.body.innerHTML = oRTE.body.innerText.replace(/[\r\n]/g, '').replace(/\s/g, ' ');
/* END MODIFICATION */
		} else {
			htmlSrc = oRTE.body.ownerDocument.createRange();
			htmlSrc.selectNodeContents(oRTE.body);
/* TPOST MODIFICATION - applet */
			htmlSrc = htmlSrc.toString();
			htmlSrc = unfillPlaceholders(htmlSrc);
			htmlSrc = htmlSrc.replace(/\s/g, ' ');
			oRTE.body.innerHTML = htmlSrc;
			// ORIGINAL
			//oRTE.body.innerHTML = htmlSrc.toString().replace(/\s/g, ' ');
/* END MODIFICATION */
		}
//		oRTE.body.innerHTML = replaceSpecialChars(oRTE.body.innerHTML);
		showGuidelines(rte);
		//needed for editing some attributes
		if(isSafari)applySafarijunk(rte);
		// (IE Only)This prevents an undo operation from displaying a pervious HTML mode
		if(isIE) {
			document.getElementById(rte).value = returnRTE(rte).document.body.innerHTML;
		}
		swapCssLink(rte, "html");
	}
}

function formathtml(html) {
	//convert html encode html tags so that they show up, then color and format them
	
	//strip white space
	html = html.replace(/\s/g, ' ');
	//convert html to text
	html = html.replace(/&/g, '&amp;');
	html = html.replace(/</g, '&lt;');
	html = html.replace(/>/g, '&gt;');
	//change all attributes " to &quot; so they can be distinguished from the html we are adding
	html = html.replace(/="/g, '=&quot;');
	html = html.replace(/=&quot;(.*?)"/g, '=&quot;$1&quot;');
	//search for opening tags
	html = html.replace(/&lt;([a-z](?:[^&|^<]+|&(?!gt;))*?)&gt;/gi, "<span class=\"tag\">&lt;$1&gt;</span><blockquote>");
	//Search for closing tags
	html = html.replace(/&lt;\/([a-z].*?)&gt;/gi, "</blockquote><span class=\"tag\">&lt;/$1&gt;</span>");
	//search for self closing tags
	html = html.replace(/\/&gt;<\/span><blockquote>/gi, "/&gt;</span>");
	//Search for values
	html = html.replace(/&quot;(.*?)&quot;/gi, "<span class=\"literal\">\"$1\"</span>");
	//search for comments
	html = html.replace(/&lt;!--(.*?)--&gt;/gi, "<span class=\"comment\">&lt;!--$1--&gt;</span>");
	//search for html entities
	html = html.replace(/&amp;(.*?);/g, '<b>&amp;$1;</b>');
	return html;
}

/*
function toggleSelection(rte) {
	//Select or deselect all text
	var oRTE = returnRTE(rte).document;
	var rng = setRange(rte);
	if(isSafari) oRTE.body.focus();
	var length1;
	var length2;
	if(isIE) {
		length1 = rng.text.length;
		length2 = oRTE.body.innerText.length;
	} else {
		length1 = rng.toString().length;
		var htmlSrc = oRTE.body.ownerDocument.createRange();
		htmlSrc.selectNodeContents(oRTE.body);
		length2 = htmlSrc.toString().length;
	}
	//Opera is always of by one
	if(isOpera) length2=length2-1;
	//initialy with preloaded content safari is off by 2, once user enters content it is of by 1
	if(isSafari) length2=length2-2;
	if(length1 < length2) {
		rteCommand(rte,'SelectAll','');
	} else {
		if(isGecko) {
			oRTE.designMode = "off";
			oRTE.designMode = "on";
			rteCommand(rte, 'styleWithCSS', false);
		} else {
			rteCommand(rte,'Unselect','');
		}
	}
}
*/

function dlgColorPalette(rte, command) {
	// function to display or hide color palettes
	setRange(rte);
	// get dialog position
	var oDialog = document.getElementById('cp' + rte);
	var buttonElement = document.getElementById(command+"_"+rte);
	var iLeftPos = buttonElement.offsetLeft+5;
	var iTopPos = buttonElement.offsetTop+53;
	oDialog.style.left = iLeftPos + "px";
	oDialog.style.top = iTopPos + "px";
	if((command == parent.command)&&(rte == currentRTE)) {
		// if current command dialog is currently open, close it
		if(oDialog.style.display == "none") {
			showHideElement(oDialog, 'show', false);
		} else {
			showHideElement(oDialog, 'hide', false);
		}
	} else {
		// if opening a new dialog, close all others
		var vRTEs = allRTEs.split(";");
		for(var i = 0; i<vRTEs.length; i++) {
			showHideElement('cp' + vRTEs[i], 'hide', false);
		}
		showHideElement(oDialog, 'show', false);
	}
	// save current values
	currentRTE = rte;
	parent.command = command;
}

function dlgLaunch(rte, command, width, height) {
	//Prepair to open a popup
	
	// save current values
	parent.command = command;
	currentRTE = rte;
	switch(command) {
		case "char":
			InsertChar = popUpWin(includesPath+'insert_char.htm', 'InsertChar', width, height, 'status=yes,');
		break;
		case "table":
			InsertTable = popUpWin(includesPath + 'insert_table.htm', 'InsertTable', width, height, 'status=yes,');
		break;
		case "image":
			setRange(rte);
			parseRTE(rte);
			InsertImg = popUpWin('explorer.php?return=rtef','AddImage', null, null, 'resize=yes,');
		break;
		case "link":
			selectedText = getText(rte);
			InsertLink = popUpWin(includesPath + 'insert_link.htm', 'InsertLink', width, height, 'status=yes,');
		break;
		case "replace":
			selectedText = getText(rte);
			dlgReplace = popUpWin(includesPath + 'replace.htm', 'dlgReplace', width, height, 'status=yes,');
		break;
		case "text":
			dlgPasteText = popUpWin(includesPath + 'paste_text.htm', 'dlgPasteText', width, height, 'status=yes,');
		break;
		case "word":
			dlgPasteWord = popUpWin(includesPath + 'paste_word.htm', 'dlgPasteWord', width, height, 'status=yes,');
		break;
	}
}

function getText(rte) {
	// get currently highlighted text and set link text value
	var oRTE = returnRTE(rte);
	if(isIE)  {
		setRange(rte);
		var rtn = stripHTML(rng.htmlText);
		parseRTE(rte);
		return rtn.replace("'","\'");
//		rtn = rtn.replace("'","\\\\\\'");
	} else
	//This little hack is the only one that works consistantly in Safari
		return oRTE.getSelection()+'';
}

function dlgCleanUp() {
	//Close any open popup window
	var vRTEs = allRTEs.split(";");
	for(var i = 0; i < vRTEs.length; i++) {
		showHideElement('cp' + vRTEs[i], 'hide', false);
	}
	if(InsertChar != null) {
		try{
			InsertChar.close();
			InsertChar=null;
		}
		catch(e) {
			InsertChar=null;
		}
	}
	if(InsertTable != null) {
		try{
			InsertTable.close();
			InsertTable=null;
		}
		catch(e) {
			InsertTable=null;
		}
	}
	if(InsertLink != null) {
		try{
			InsertLink.close();
			InsertLink=null;
		}
		catch(e) {
			InsertLink=null;
		}
	}
	if(InsertImg != null) {
		try{
			InsertImg.close();
			InsertImg=null;
		}
		catch(e) {
			InsertImg=null;
		}
	}
	if(dlgReplace != null) {
		try{
			dlgReplace.close();
			dlgReplace=null;
		}
		catch(e) {
			dlgReplace=null;
		}
	}
	if(dlgPasteText != null) {
		try{
			dlgPasteText.close();
			dlgPasteText=null;
		}
		catch(e) {
			dlgPasteText=null;
		}
	}
	if(dlgPasteWord != null) {
		try{
			dlgPasteWord.close();
			dlgPasteWord=null;
		}
		catch(e) {
			dlgPasteWord=null;
		}
	}
}

function popUpWin (url, win, width, height, options) {
	//Open a popup window
	//TODO this is bloced by popup blockers.
	dlgCleanUp();
	var leftPos = (screen.availWidth - width) / 2;
	var topPos = (screen.availHeight - height) / 2;
	options += 'width=' + width + ',height=' + height + ',left=' + leftPos + ',top=' + topPos;
	return window.open(url, win, options);
}

function setColor(color) {
	// function to set color
	var rte = currentRTE;
	var parentCommand = parent.command;
	if(isIE || isSafari) {
		if(parentCommand == "hilitecolor") {
			parentCommand = "BackColor";
		}
	}
	// retrieve selected range
	if(isIE)rng.select();
	if(isGecko) rteCommand(rte, "useCSS", false);
	if(isGecko) rteCommand(rte, "styleWithCSS", true);
	var oRTE = returnRTE(rte);
	if(isSafari && SafariVersion < 500)oRTE.document.execCommand(parentCommand, false, color);
	else rteCommand(rte, parentCommand, color);
	if(isGecko) rteCommand(rte, "useCSS", true);
	if(isGecko) rteCommand(rte, "styleWithCSS", false);
	if(!(isSafari && SafariVersion < 500))showHideElement('cp'+rte, "hide", false);
}
/*
We insert html directly now as this was used inproperly - Anders Jenbo
function addImage(rte) {
	dlgCleanUp();
	// function to add image
	imagePath = prompt('Enter Image URL:', 'http://');
	if((imagePath != null)&&(imagePath != "")) {
		rteCommand(rte, 'InsertImage', imagePath);
	}
}
*/
function rtePrint(rte) {
	//Print the content of the RTEF iFrame
	dlgCleanUp();
	if(isIE) {
		rteCommand(rte, 'Print');
	} else {
		returnRTE(rte).print();
	}
}

function selectFont(rte, selectname) {
	// function to handle font changes
	var idx = document.getElementById(selectname).selectedIndex;
	// First one is always a label
	if(idx != 0) {
		var selected = document.getElementById(selectname).options[idx].value;
		var cmd = selectname.replace('_'+rte, '');
		rteCommand(rte, cmd, selected);
		document.getElementById(selectname).selectedIndex = 0;
	}
}

function insertHTML(html) {
	if(!(isSafari && SafariVersion < 500)) {
		//function to add HTML -- thanks dannyuk1982
		var rte = currentRTE;
		var oRTE = returnRTE(rte);
		oRTE.focus();
		if(isIE) {
			var oRng = oRTE.document.selection.createRange();
			oRng.pasteHTML(html);
			oRng.collapse(false);
			oRng.select();
		} else {
			oRTE.document.execCommand('InsertHTML', false, html);
		}
	} else {
		//In Safari 1.3 and 2.x we insert a text string and then do search and replace in the code - Anders Jenbo.
		var searchFor = 'SafariHTMLReplaceString';
		var rte = currentRTE;
		rteCommand(rte,'InsertText', searchFor);
		var oRTE = returnRTE(rte);
		var tmpContent = oRTE.document.body.innerHTML.replace("'", "\'").replace('"', '\"');
		var strRegex = "/(?!<[^>]*)(" + searchFor + ")(?![^<]*>)/g";
		var cmpRegex=eval(strRegex);
		var runCount = 0;
		var tmpNext = tmpContent;
		var intFound = tmpNext.search(cmpRegex);
		while(intFound > -1) {
			runCount = runCount+1;
			tmpNext = tmpNext.substr(intFound + searchFor.length);
			intFound = tmpNext.search(cmpRegex);
		}
		if (runCount > 0) {
			tmpContent=tmpContent.replace(cmpRegex,html);
			oRTE.document.body.innerHTML = tmpContent.replace("\'", "'").replace('\"', '"');
			updateRTEs();
		}
	}
}
/*
function replaceSpecialChars(html) {
	var specials = new Array("&cent;","&euro;","&pound;","&curren;","&yen;","&copy;","&reg;","&trade;","&divide;","&times;","&plusmn;","&frac14;","&frac12;","&frac34;","&deg;","&sup1;","&sup2;","&sup3;","&micro;","&laquo;","&raquo;","&lsquo;","&rsquo;","&lsaquo;","&rsaquo;","&sbquo;","&bdquo;","&ldquo;","&rdquo;","&iexcl;","&brvbar;","&sect;","&not;","&macr;","&para;","&middot;","&cedil;","&iquest;","&fnof;","&mdash;","&ndash;","&bull;","&hellip;","&permil;","&ordf;","&ordm;","&szlig;","&dagger;","&Dagger;","&eth;","&ETH;","&oslash;","&Oslash;","&thorn;","&THORN;","&oelig;","&OElig;","&scaron;","&Scaron;","&acute;","&circ;","&tilde;","&uml;","&agrave;","&aacute;","&acirc;","&atilde;","&auml;","&aring;","&aelig;","&Agrave;","&Aacute;","&Acirc;","&Atilde;","&Auml;","&Aring;","&AElig;","&ccedil;","&Ccedil;","&egrave;","&eacute;","&ecirc;","&euml;","&Egrave;","&Eacute;","&Ecirc;","&Euml;","&igrave;","&iacute;","&icirc;","&iuml;","&Igrave;","&Iacute;","&Icirc;","&Iuml;","&ntilde;","&Ntilde;","&ograve;","&oacute;","&ocirc;","&otilde;","&ouml;","&Ograve;","&Oacute;","&Ocirc;","&Otilde;","&Ouml;","&ugrave;","&uacute;","&ucirc;","&uuml;","&Ugrave;","&Uacute;","&Ucirc;","&Uuml;","&yacute;","&yuml;","&Yacute;","&Yuml;");
	var unicodes = new Array("\u00a2","\u20ac","\u00a3","\u00a4","\u00a5","\u00a9","\u00ae","\u2122","\u00f7","\u00d7","\u00b1","\u00bc","\u00bd","\u00be","\u00b0","\u00b9","\u00b2","\u00b3","\u00b5","\u00ab","\u00bb","\u2018","\u2019","\u2039","\u203a","\u201a","\u201e","\u201c","\u201d","\u00a1","\u00a6","\u00a7","\u00ac","\u00af","\u00b6","\u00b7","\u00b8","\u00bf","\u0192","\u2014","\u2013","\u2022","\u2026","\u2030","\u00aa","\u00ba","\u00df","\u2020","\u2021","\u00f0","\u00d0","\u00f8","\u00d8","\u00fe","\u00de","\u0153","\u0152","\u0161","\u0160","\u00b4","\u02c6","\u02dc","\u00a8","\u00e0","\u00e1","\u00e2","\u00e3","\u00e4","\u00e5","\u00e6","\u00c0","\u00c1","\u00c2","\u00c3","\u00c4","\u00c5","\u00c6","\u00e7","\u00c7","\u00e8","\u00e9","\u00ea","\u00eb","\u00c8","\u00c9","\u00ca","\u00cb","\u00ec","\u00ed","\u00ee","\u00ef","\u00cc","\u00cd","\u00ce","\u00cf","\u00f1","\u00d1","\u00f2","\u00f3","\u00f4","\u00f5","\u00f6","\u00d2","\u00d3","\u00d4","\u00d5","\u00d6","\u00f9","\u00fa","\u00fb","\u00fc","\u00d9","\u00da","\u00db","\u00dc","\u00fd","\u00ff","\u00dd","\u0178");
	for(var i=0; i<specials.length; i++) {
		html = replaceIt(html,unicodes[i],specials[i]);
	}
	return html;
}
*/
function SearchAndReplace(searchFor, replaceWith, matchCase, wholeWord) {
	//TODO in IE question appears below the popup, so move the question to the popup and colose the popup from here when that has been answered
	var cfrmMsg = lblSearchConfirm.replace("SF",searchFor).replace("RW",replaceWith);
	var rte = currentRTE;
	stripGuidelines(rte);
	if(isSafari)stripSafarijunk(rte);
	var oRTE = returnRTE(rte);
	var tmpContent = oRTE.document.body.innerHTML.replace("'", "\'").replace('"', '\"');
	var strRegex;
	if (matchCase && wholeWord) {
		strRegex = "/(?!<[^>]*)(\\b(" + searchFor + ")\\b)(?![^<]*>)/g";
	} else if (matchCase) {
		strRegex = "/(?!<[^>]*)(" + searchFor + ")(?![^<]*>)/g";
	} else if (wholeWord) {
		strRegex = "/(?!<[^>]*)(\\b(" + searchFor + ")\\b)(?![^<]*>)/gi";
	} else {
		strRegex = "/(?!<[^>]*)(" + searchFor + ")(?![^<]*>)/gi";
	}
	var cmpRegex=eval(strRegex);
	var runCount = 0;
	var tmpNext = tmpContent;
	var intFound = tmpNext.search(cmpRegex);
	while(intFound > -1) {
		runCount = runCount+1;
		tmpNext = tmpNext.substr(intFound + searchFor.length);
		intFound = tmpNext.search(cmpRegex);
	}
	if (runCount > 0) {
		cfrmMsg = cfrmMsg.replace("[RUNCOUNT]",runCount);
		if(confirm(cfrmMsg)) {
			tmpContent=tmpContent.replace(cmpRegex,replaceWith);
			oRTE.document.body.innerHTML = tmpContent.replace("\'", "'").replace('\"', '"');
		} else {
			alert(lblSearchAbort);
		}
		showGuidelines(rte);
		//needed for editing some attributes
		if(isSafari)applySafarijunk(rte);
	} else {
		showGuidelines(rte);
		//needed for editing some attributes
		if(isSafari)applySafarijunk(rte);
		alert("["+searchFor+"] "+lblSearchNotFound);
	}
}

function showHideElement(element, showHide, rePosition) {
	// function to show or hide elements
	// element variable can be string or object
	if(document.getElementById(element)) {
		element = document.getElementById(element);
	}
	if(showHide == "show") {
		element.style.display = "";
		if(rePosition) {
			element.style.position = "relative";
			element.style.left = "auto";
			element.style.top = "auto";
		}
	} else if(showHide == "hide") {
		element.style.display = "none";
	}
}

function setRange(rte) {
	// function to store range of current selection
	var oRTE = returnRTE(rte);
	if(isSafari) oRTE.focus();
	var selection;
	if(isIE) {
		selection = oRTE.document.selection;
		if(selection != null) {
			rng = selection.createRange();
		}
	} else {
		selection = oRTE.getSelection();
		/*
		//TODO Safari 1.3 dosn't support getRangeAt() http://www.quirksmode.org/dom/range_intro.html
		if(isSafari) {
			var rng = selection.createRange();
			rng.setStart(selection.anchorNode,selection.anchorOffset);
			rng.setEnd(selection.focusNode,selection.focusOffset);
		} else {
*/
			//This solves a strange issue in Safari 3 where the folowing command stops the script after an unslect has been fired - Anders Jenbo
			if(selection.rangeCount == 0 && isSafari && safariDummyRange != null) {
				rng = safariDummyRange;
			} else {
				if(isSafari && safariDummyRange == null) {
					safariDummyRange=oRTE.document.createRange();
					safariDummyRange.setStart(oRTE.document.body,0);
					safariDummyRange.setEnd(oRTE.document.body,0);
				}
				if(!(isSafari && SafariVersion < 500))
					rng = selection.getRangeAt(0).cloneRange();
				else {
					rng = safariDummyRange;
					/*
					rng = selection.createRange();
					rng.setStart(selection.anchorNode,selection.anchorOffset);
					rng.setEnd(selection.focusNode,selection.focusOffset);
					*/
				}
			}
//		}
	}
	return rng;
}

function stripHTML(strU) {
	// strip all html
	var strN = strU.replace(/(<([^>]+)>)/ig,"");
	// replace carriage returns and line feeds
	strN = strN.replace(/\r\n/g," ");
	strN = strN.replace(/\n/g," ");
	strN = strN.replace(/\r/g," ");
	strN = trim(strN);
	return strN;
}

function trim(inputString) {
	//Remove white space at the start and end of a string
	if (typeof inputString != "string") {
		return inputString;
	}
	inputString = inputString.replace(/^\s+|\s+$/g, "").replace(/\s{2,}/g, "");
	return inputString;
}

function showGuidelines(rte) {
	//Add guide lines to tabels with no margin
	//TODO the inner lines are not always visible in Safari
	if(rte.length == 0) rte = currentRTE;
	var oRTE = returnRTE(rte);
	var tables = oRTE.document.getElementsByTagName("table");
	var sty = "dashed 1px "+zeroBorder;
	for(var i=0; i<tables.length; i++) {
		if(tables[i].getAttribute("border") == 0) {
			if(isIE || isOpera) {
				var trs = tables[i].getElementsByTagName("tr");
				for(var j=0; j<trs.length; j++) {
					var tds = trs[j].getElementsByTagName("td");
					for(var k=0; k<tds.length; k++) {
						if(j == 0 && k == 0) {
							tds[k].style.border = sty;
						} else if(j == 0 && k != 0) {
							tds[k].style.borderBottom = sty;
							tds[k].style.borderTop = sty;
							tds[k].style.borderRight = sty;
						} else if(j != 0 && k == 0) {
							tds[k].style.borderBottom = sty;
							tds[k].style.borderLeft = sty;
							tds[k].style.borderRight = sty;
						} else if(j != 0 && k != 0) {
							tds[k].style.borderBottom = sty;
							tds[k].style.borderRight = sty;
						}
					}
				}
			} else {
				tables[i].removeAttribute("border");
				tables[i].setAttribute("style","border: " + sty);
				tables[i].setAttribute("rules", "all");
			}
		}
	}
}

function stripGuidelines(rte) {
	//strip any guide lines from tabels befor outputtin the source.
	var oRTE = returnRTE(rte);
	var tbls = oRTE.document.getElementsByTagName("table");
	for(var j=0; j<tbls.length; j++) {
		if(tbls[j].getAttribute("border") == 0 || tbls[j].getAttribute("border") == null) {
			if(isIE || isOpera) {
				var tds = tbls[j].getElementsByTagName("td");
				for(var k=0; k<tds.length; k++) {
					tds[k].removeAttribute("style");
				}
			} else {
				tbls[j].removeAttribute("style");
				tbls[j].removeAttribute("rules");
				tbls[j].setAttribute("border","0");
			}
		}
	}
}

function applySafarijunk(rte) {
	//Theas are needed in safari for it to behave correctly
	if(rte.length == 0) rte = currentRTE;
	var oRTE = returnRTE(rte);
	var blockquotes = oRTE.document.getElementsByTagName("blockquote");
	for(var i=0; i<blockquotes.length; i++) {
		if(!blockquotes[i].getAttribute("Class")) {
			blockquotes[i].setAttribute("Class", "webkit-indent-blockquote");
		}
	}
}

function stripSafarijunk(rte) {
	//Safari generates some internal classes and dosn't clean it up
	var oRTE = returnRTE(rte);
	var brbls = oRTE.document.getElementsByTagName("br");
	for(var j=0; j<brbls.length; j++) {
		brbls[j].removeAttribute("Class");
	}
	var spanbls = oRTE.document.getElementsByTagName("span");
	for(var j=0; j<spanbls.length; j++) {
		if(spanbls[j].getAttribute("Class") == 'Apple-style-span') {
			spanbls[j].removeAttribute("Class");
		}
	}
	var fontbls = oRTE.document.getElementsByTagName("font");
	for(var j=0; j<fontbls.length; j++) {
		if(fontbls[j].getAttribute("Class") == 'Apple-style-span') {
			fontbls[j].removeAttribute("Class");
		}
	}
	var blockquotebls = oRTE.document.getElementsByTagName("blockquote");
	for(var j=0; j<blockquotebls.length; j++) {
		if(blockquotebls[j].getAttribute("Class") == 'webkit-indent-blockquote') {
			blockquotebls[j].removeAttribute("Class");
		}
	}
}

function findSize(obj) {
	if(obj.length > 0) {
		obj = returnRTE(obj);
	} else {
		obj = this;
	}
	if ( typeof( obj.window.innerWidth ) == 'number' ) {
		// Non-IE
		obj_width = obj.window.innerWidth;
		obj_height = obj.window.innerHeight;
	} else if( obj.document.documentElement && ( obj.document.documentElement.clientWidth || obj.document.documentElement.clientHeight ) ) {
		// IE 6+ in 'standards compliant mode'
		obj_width = document.documentElement.clientWidth;
		obj_height = document.documentElement.clientHeight;
	} else if( obj.document.body && ( obj.document.body.clientWidth || obj.document.body.clientHeight ) ) {
		// IE 4 - 5.5 compatible
		obj_width = obj.document.body.clientWidth;
		obj_height = obj.document.body.clientHeight;
	}
}

function resizeRTE(rte) {
	//used at load to size the iframe to the content div and to resize in fullscreen mode
	if(!rte || typeof(rte) == 'object')rte = currentRTE;
	if(document.getElementById('buttons_'+rte))
		var bRTE = document.getElementById('buttons_'+rte).offsetHeight;
	else 
		var bRTE = 0;
	document.getElementById('iframe'+rte).style.top = bRTE+'px';
	
	if(isIE || isSafari || isGecko) {
		if(!(fullscreenint && isIE && IEVersion < 7)) {
			var hRTE = document.getElementById(rte+'Div').offsetHeight;
		} else if(document.documentElement && document.documentElement.clientHeight) {
			var hRTE = document.documentElement.clientHeight;
		} else {
			var hRTE = document.body.clientHeight;
		}
		if(document.getElementById('vs'+rte))
			var sRTE = document.getElementById('vs'+rte).offsetHeight;
		else
			var sRTE = 0;
		document.getElementById('iframe'+rte).style.height = Math.max(hRTE-sRTE-bRTE-2, 0)+'px';
	}
}

function replaceIt(string,text,by) {
	// CM 19/10/04 custom replace function
	var strLength = string.length, _xtLength = text.length;
	if ((strLength == 0) || (_xtLength == 0)) {
		return string;
	}
	var i = string.indexOf(text);
	if ((!i) && (text != string.substring(0,_xtLength))) {
		return string;
	}
	if(i == -1) {
		return string;
	}
	var newstr = string.substring(0,i) + by;
	if(i+_xtLength < strLength) {
		newstr += replaceIt(string.substring(i+_xtLength,strLength),text,by);
	}
	return newstr;
}

/* MODIFICATIONS - Tarun Pondicherry */
/* Copyright (c) 2008 Tarun Pondicherry */
/* Released under MIT license (same as RTEF) */
/* 
   These functions are used for a placeholders so that applet's
   and other objects that are not designtime renderable can be
   incorporated into rte.
   
   This code has been tested in FF2, IE6, Safari 3
*/

//Encodes Special characters using HTML char codes
function htmlEncode(code)
{
	code = code.replace(/&/g, '&amp;');
	code = code.replace(/</g, '&lt;');
	code = code.replace(/>/g, '&gt;');
	code = code.replace(/"/g, '&quot;');
	code = code.replace(/'/g, '&#39;'); //look up entity for single quote
	//There are not HTML specs, but must be removed
	code = code.replace(/\r/g, ' ');
	code = code.replace(/\n/g, ' ');
	
	return code;
}

//Decodes Special characters from HTML char codes
function htmlDecode(code)
{
	code = code.replace(/\&lt;/g, '<');
	code = code.replace(/\&gt;/g, '>');
	code = code.replace(/\&quot;/g, '"');
	code = code.replace(/\&#39;/g, '\'');
	code = code.replace(/\&amp;/g, '&');
	
	return code;
}

//Insert a Placeholder at the current cursor position
//Currently an image with the true code in the alt attribute
function insertPlaceholder(width, height, code)
{
	//encode code into a format acceptable by an alt attribute
	code = htmlEncode(code);
	//create the placeholder code
	var html = '<img name="placeholder" class="object" src="'+imagesPath+'placeholder.gif" width="'+width+'" height="'+height+'" alt="'+code+'" />';
	//insert the placeholder
	insertHTML(html);
}

//Fills placeholders with the html code that they're holding the place for
function fillPlaceholders(html) {
	//find all placeholders and replace with code
	//TODO look at reducing this mess
	var object_re = new RegExp(/<img((\s+\w+(\s*=\s*(?:".*?"|'.*?'|[^'">\s]+))?)+\s*|\s*)\/+>/gi);
	
	var object = html.match(object_re);
	if (object != null)
	for (var p=0; p < object.length; p++) {
		if (!object[p].match(/name=("|')placeholder(\1)/i))
			continue;
		var code = object[p];
		//IE returns escaped qouts instead of html entities
		if(isIE)
			var code = code.replace(/\\"/gi, '&quot;');
		//TODO this dosn't work with IE as it uses style="WIDTH: 300px; HEIGHT: 300px;"
		//find the width attribute
		var width = code.match(/width=("|')[0-9]*?(\1)/i);
		if (width == null)
			width = '';
		else
			width = width[0];
		
		//find the height attribute
		var height = code.match(/height=("|')[0-9]*?(\1)/i);
		if (height == null)
			height = '';
		else
			height = height[0];
		
		//find the style attribute
		var style = code.match(/style=("|').*?(\1)/i);
		if (style == null)
			style = '';
		else
			style = style[0];
		
		
		//find the code in the alt attribute
		code = code.match(/alt=("|').+?(\1)/i);
		//should clean up the code
		if (code == null)
			continue;
		code = code[0];
		
		//IE decodes < > inside attributes
		if(isIE) {
			code = code.replace(/</gi, '&lt;');
			code = code.replace(/>/gi, '&gt;');
		}
		//TODO this isn't early enough
		if(isSafari) {
			code = code.replace(/>/gi, '&gt;');
		}
		
		//decode that into HTML
		var q = code.match(/alt=("|')/i)[0].replace('alt=', '');
		code = code.replace(new RegExp('(alt=' + q + '|' + q + ')', 'gi'), '');
		code = htmlDecode(code);
		
		//Update the width, height and style to match that of the image
		if(code.match(/width=("|').*?(\1)/i)) {
			var code = code.replace(/width=("|')[0-9]*?(\1)/gi, width);
		} else if(width) {
			var code = code.replace('<object', '<object '+width);
			var code = code.replace('<embed', '<embed '+width);
		}
		if(code.match(/height=("|').*?(\1)/i)) {
			var code = code.replace(/height=("|')[0-9]*?(\1)/gi, height);
		} else if(height) {
			var code = code.replace('<object', '<object '+height);
			var code = code.replace('<embed', '<embed '+height);
		}
		if(code.match(/style=("|').*?(\1)/i)) {
			var code = code.replace(/style=("|').*?(\1)/gi, style);
		} else if(style) {
			var code = code.replace('<object', '<object '+style);
			var code = code.replace('<embed', '<embed '+style);
		}		
		
		//replace the placeholder with the object code
		html = html.replace(object[p], code);
	}
	return html;
}

//Replaces html code that was originally from a placeholder by a placeholder
function unfillPlaceholders(html) {
	//find all placeholders that have been replaced
	var object_re = /<object.+?<\/object>/gi;
	var object = html.match(object_re);
	if (object != null)
	for (var p=0; p < object.length; p++)
	{
		code = object[p];
		
		//find the width attribute
		var width = object[p].match(/width=("|')[0-9]*?(\1)/i);
		//should clean up the code
		if (width == null)
			width = '';
		else
			width = width[0];
		
		//find the height attribute
		var height = object[p].match(/height=("|')[0-9]*?(\1)/i);
		//should clean up the code
		if (height == null)
			height = '';
		else
			height = height[0];
		
		//find the style attribute
		var style = object[p].match(/style=("|').*?(\1)/i);
		//should clean up the code
		if (style == null)
			style = '';
		else
			style = style[0];
		
		code = htmlEncode(code);
		//replace the object code with a placeholder
		place = '<img name="placeholder" class="object" alt="' + code + '" src="'+imagesPath+'placeholder.gif" '+width+' '+height+' '+style+' />';
		//TODO THIS REPLACES ALL THE OBJECTS WITH THE FIRST
		html = html.replace(object[p], place);
		//html = html.replace(object_re, place);
	}
	
	return html;
}
/* END MODIFICATION */
/*
function countWords(rte) {
//TODO, Complex content results in incorrect word count, Opera is pariculare sensitive!
	parseRTE(rte);
	var words = document.getElementById(rte).value;
	var str = stripHTML(words);
	var chars = trim(words);
	chars = chars.length;
	chars = maxchar - chars;
	str = str+" a ";	// word added to avoid error
	str = trim(str.replace(/&nbsp;/gi,' ').replace(/([\n\r\t])/g,' ').replace(/&(.*);/g,' '));
	var count = 0;
	for(x=0;x<str.length;x++) {
		if(str.charAt(x)==" " && str.charAt(x-1)!=" ") {
			count++;
		}
	}
	if(str.charAt(str.length-1) != " ") {
		count++;
	}
	count = count - 1;	// extra word removed
	var alarm = "";
	if(chars<0) {
		alarm = "\n\n"+lblCountCharWarn;
	}
	alert(lblCountTotal+": "+count+ "\n\n"+lblCountChar+": "+chars+alarm);
}
*/
//********************
// Non-designMode() Functions
//********************
function autoBRon(rte) {
	// CM 19/10/04 used for non RTE browsers to deal with auto <br /> (and clean up other muck)
	if(isIE && IEVersion < 5)
		var oRTE = document.all[rte];
	else
		var oRTE = document.getElementById(rte);
	oRTE.value=parseBreaks(oRTE.value);
	oRTE.value=replaceIt(oRTE.value,'&apos;','\'');
}

function autoBRoff(rte) {
	// CM 19/10/04 used for non RTE browsers to deal with auto <br /> (auto carried out when the form is submitted)
	if(isIE && IEVersion < 5)
		var oRTE = document.all[rte];
	else
		var oRTE = document.getElementById(rte);
	oRTE.value=replaceIt(oRTE.value,'\n','<br />');
	oRTE.value=replaceIt(oRTE.value,'\'','&apos;');
}

function parseBreaks(argIn) {
	// CM 19/10/04 used for non RTE browsers to deal with auto <br /> (and clean up other muck)
	argIn=replaceIt(argIn,'<br>','\n');
	argIn=replaceIt(argIn,'<BR>','\n');
	argIn=replaceIt(argIn,'<br/>','\n');
	argIn=replaceIt(argIn,'<br />','\n');
	argIn=replaceIt(argIn,'\t',' ');
	argIn=replaceIt(argIn,'\n ','\n');
	argIn=replaceIt(argIn,' <p>','<p>');
//Not safe if previues <p> was styled differently - Anders Jenbo 30/10/07
//	argIn=replaceIt(argIn,'</p><p>','\n\n');
	argIn=replaceIt(argIn,'&apos;','\'');
	argIn = trim(argIn);
	return argIn;
}

//********************
//Gecko-Only Functions
//********************
function geckoKeyPress(evt) {
	// function to add bold, italic, and underline shortcut commands to gecko RTEs
	// contributed by Anti Veeranna (thanks Anti!)
	var rte = evt.target.id;
	if (evt.ctrlKey) {
		var key = String.fromCharCode(evt.charCode).toLowerCase();
		var cmd = '';
		switch (key) {
			case 'b': cmd = "bold"; break;
			case 'i': cmd = "italic"; break;
			case 'u': cmd = "underline"; break;
			//To more resembel IE -- Anders Jenbo 14/11/07
			case 'k': cmd = "link"; break;
		}
		if (cmd) {
			if(cmd == 'link')
				dlgLaunch(rte,'link');
			else
				rteCommand(rte, cmd, null);
			// stop the event bubble
			evt.preventDefault();
			evt.stopPropagation();
		}
	}
}
/*
//*****************
//IE-Only Functions
//*****************
function checkspell() {
	dlgCleanUp();
	//function to perform spell check
	try {
		var tmpis = new ActiveXObject("ieSpell.ieSpellExtension");
		tmpis.CheckAllLinkedDocuments(document);
	}
	catch(exception) {
		if(exception.number==-2146827859) {
			if(confirm("ieSpell not detected. Click Ok to go to download page.")) {
				window.open("http://www.iespell.com/download.php","DownLoad");
			}
		} else {
			alert("Error Loading ieSpell: Exception " + exception.number);
		}
	}
}
*/
/*	
function getHTML(rte) {
	setRange(rte);
	var rtn = rng.htmlText;
	parseRTE(rte);
	return rtn;
}
*/
