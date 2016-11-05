// Norwegian Language File
// Translation provided by Thor Skaar

// Settings
var lang = "no"; // xhtml language
var lang_direction = "ltr"; // language direction:ltr=left-to-right,rtl=right-to-left

// Buttons
var lblSubmit			= "Send"; // Button value for non-designMode() & non fullsceen RTE
var lblModeRichText		= "Skift til RichText Mode"; // Label of the Show Design view link
var lblModeHTML			= "Skift til HTML Mode"; // Label of the Show Code view link
var lblSave				= "Lagre";
var lblPrint			= "Skriv ut";
var lblSelectAll		= "Marker alt";
var lblSpellCheck		= "Stavekontroll";
var lblCut				= "Klipp";
var lblCopy				= "Kopier";
var lblPaste			= "Lim inn";
var lblPasteText		= "Paste as Plain Text";
var lblPasteWord		= "Paste From Word";
var lblUndo				= "Angre";
var lblRedo				= "Angre igjen";
var lblHR				= "Horisontal linje";
var lblInsertChar		= "Sett inn spesialtegn";
var lblBold				= "Fet";
var lblItalic			= "Kursiv";
var lblUnderline		= "Underlinjert";
var lblStrikeThrough	= "Gjennomstreket";
var lblSuperscript		= "Hevet skrift";
var lblSubscript		= "Senket skrift";
var lblAlgnLeft			= "Venstrejustert";
var lblAlgnCenter		= "Midtstilt";
var lblAlgnRight		= "Høyrejustert";
var lblJustifyFull		= "Blokkjustert";
var lblOL				= "Numrert Liste";
var lblUL				= "Punktliste";
var lblOutdent			= "Reduser Innrykk";
var lblIndent			= "Oslash;k innrykk";
var lblTextColor		= "Tekstfarge";
var lblBgColor			= "Bakgrunnsfarge";
var lblSearch			= "Søk og erstatt";
var lblInsertLink		= "Sett inn link";
var lblUnLink			 = "Fjern link";
var lblAddImage			= "Legg til bilde";
var lblInsertTable		= "Sett inn tabell";
var lblWordCount		= "Word Count";
var lblUnformat			= "Unformat";
// Dropdowns
// Format Dropdown
var lblFormat			= "<option value=\"\" selected=\"selected\">Stil</option>";
lblFormat				+= "<option value=\"&lt;h1&gt;\">Overskrift 1</option>";
lblFormat				+= "<option value=\"&lt;h2&gt;\">Overskrift 2</option>";
lblFormat				+= "<option value=\"&lt;h3&gt;\">Overskrift 3</option>";
lblFormat				+= "<option value=\"&lt;h4&gt;\">Overskrift 4</option>";
lblFormat				+= "<option value=\"&lt;h5&gt;\">Overskrift 5</option>";
lblFormat				+= "<option value=\"&lt;h6&gt;\">Overskrift 6</option>";
lblFormat				+= "<option value=\"&lt;p&gt;\">Avsnitt</option>";
lblFormat				+= "<option value=\"&lt;address&gt;\">Addresse</option>";
lblFormat				+= "<option value=\"&lt;pre&gt;\">Preformatert</option>";
// Font Dropdown
var lblFont				= "<option value=\"\" selected=\"selected\">Font</option>";
lblFont					+= "<option value=\"Arial, Helvetica, sans-serif\">Arial</option>";
lblFont					+= "<option value=\"Courier New, Courier, mono\">Courier New</option>";
lblFont					+= "<option value=\"Palatino Linotype\">Palatino Linotype</option>";
lblFont					+= "<option value=\"Times New Roman, Times, serif\">Times New Roman</option>";
lblFont					+= "<option value=\"Verdana, Arial, Helvetica, sans-serif\">Verdana</option>";
var lblApplyFont		= "Apply selected font";
// Size Dropdown
var lblSize				= "<option value=\"\">Størrelse</option>";
lblSize					+= "<option value=\"1\">1</option>";
lblSize					+= "<option value=\"2\">2</option>";
lblSize					+= "<option value=\"3\">3</option>";
lblSize					+= "<option value=\"4\">4</option>";
lblSize					+= "<option value=\"5\">5</option>";
lblSize					+= "<option value=\"6\">6</option>";
lblSize					+= "<option value=\"7\">7</option>";
//Size buttons
var lblIncreasefontsize		= "Increase Font Size";
var lblDecreasefontsize		= "Decrease Font Size";
// Alerts
var lblSearchConfirm	= "Søkeordet [SF] ble funnet [RUNCOUNT] gang(er).\n\n"; // Leave in [SF], [RUNCOUNT] and [RW]
lblSearchConfirm		+= "Vil du virkelig erstatte ordet med [RW] ?\n";
var lblSearchAbort		= "handling avbrutt.";
var lblSearchNotFound	= "ble ikke funnet.";
var lblCountTotal		= "Word Count";
var lblCountChar		= "Available Characters";
var lblCountCharWarn	= "Warning! Your content is too long and may not save correctly.";
// Dialogs
// Insert Link
var lblLinkBlank			= "new window (_blank)";
var lblLinkSelf			= "same frame (_self)";
var lblLinkParent		= "parent frame (_parent)";
var lblLinkTop			= "first frame (_top)";
var lblLinkType			= "Link Type";
var lblLinkOldA			= "eksisterende anker";
var lblLinkNewA			= "nytt anker";
var lblLinkAnchors		= "Anker";
var lblLinkAddress		= "Addresse";
var lblLinkText			= "Link Tekst";
var lblLinkOpenIn		= "Aring;pne Link I";
var lblLinkVal0			= "Vennligst skriv url.";
var lblLinkSubmit		= "OK";
var lblLinkCancel		= "Cancel";
var lblLinkRelative		= "relative";
var lblLinkEmail		= "email";
var lblLinkDefault		= "Default";
// Insert Image
var lblImageURL			= "Bilde URL";
var lblImageAltText		= "Alternativ Tekst";
var lblImageVal0		= "Vennligst indiker \"Image URL\".";
var lblImageSubmit		= "OK";
var lblImageCancel		= "Avbryt";
// Insert Table
var lblTableRows		= "Rader";
var lblTableColumns		= "Kolonner";
var lblTableWidth		= "Tabell bredde";
var lblTablePx			= "piksler";
var lblTablePercent		= "prosent";
var lblTableBorder		= "Ramme tykkelse";
var lblTablePadding		= "Celle utforing";
var lblTableSpacing		= "Celle mellomrom";
var lblTableSubmit		= "OK";
var lblTableCancel		= "Avbryt";
// Search and Replace
var lblSearchFind		= "Finn hva";
var lblSearchReplace	= "Erstatt med";
var lblSearchMatch		= "Sammenlign bokstav";
var lblSearchWholeWord	= "Finn bare hele ord";
var lblSearchVal0		= "Du må skrive inn noe \"Find what:\".";
var lblSearchSubmit		= "OK";
var lblSearchCancel		= "Avbryt";
// Paste As Plain Text
var lblPasteTextHint	= "Hint: To paste you can either right-click and choose \"Paste\" or use the key combination of Ctrl-V.";
var lblPasteTextVal0	= "Please enter text.";
var lblPasteTextSubmit	= "OK";
var lblPasteTextCancel	= "Avbryt";
// Paste from Word
var lblPasteWordHint	= "Hint: To paste you can either right-click and choose \"Paste\" or use the key combination of Ctrl-V.";
var lblPasteWordVal0	= "Please enter text.";
var lblPasteWordSubmit	= "OK";
var lblPasteWordCancel	= "Avbryt";
// non-designMode
var lblAutoBR			= "Bruk Auto Linjeskift";
var lblRawHTML			= "Bruk bare rå HTML";
var lblnon_designMode	= 'For å bruke denne tekstbehandleren, må du ha <a href="http://www.mozilla.org/" target="_blank">Mozilla 1.3+</a> (eg, <a href="http://www.getfirefox.com/" target="_blank">Firefox</a>), <a href="http://www.apple.com/safari/download/" target="_blank">Safari 1.3+</a>, <a href="http://www.opera.com/" target="_blank">Opera 9+</a> eller <a href="http://www.microsoft.com/windows/products/winfamily/ie/default.mspx" target="_blank">MS IE5.5+</a>.';
