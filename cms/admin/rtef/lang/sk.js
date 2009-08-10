// Slovak Language File
// Translation provided by Rolf Cleis, www.cleis.net

// Settings
var lang = "sk"; // xhtml language
var lang_direction = "ltr"; // language direction:ltr=left-to-right,rtl=right-to-left

// Buttons
var lblSubmit			= "Potvrdit"; // Button value for non-designMode() & non fullsceen RTE
var lblModeRichText		= "Prepnút do RichText režimu"; // Label of the Show Design view link
var lblModeHTML			= "Prepnút do HTML režimu"; // Label of the Show Code view link
var lblSave				= "Uložit";
var lblPrint			= "Tlacit";
var lblSelectAll		= "Oznacit/Odznacit všetko";
var lblSpellCheck		= "Kontrola pravopisu";
var lblCut				= "Vymazat";
var lblCopy				= "Kopírovat";
var lblPaste			= "Vložit";
var lblPasteText		= "Paste as Plain Text";
var lblPasteWord		= "Paste From Word";
var lblUndo				= "Vrátit naspät";
var lblRedo				= "Vrátit dopredu";
var lblHR				= "Horizontálne pravidlo";
var lblInsertChar		= "Vložit špeciálny znak";
var lblBold				= "Tucné písmo";
var lblItalic			= "Šikmé písmo";
var lblUnderline		= "Podciarknuté písmo";
var lblStrikeThrough	= "Preciarknút";
var lblSuperscript		= "Index";
var lblSubscript		= "Prípona";
var lblAlgnLeft			= "Zarovnat zlava";
var lblAlgnCenter		= "Vycentrovat";
var lblAlgnRight		= "Zarovnat sprava";
var lblJustifyFull		= "Potvrdit všetko";
var lblOL				= "Usporiadaný zoznam";
var lblUL				= "Neusporiadaný zoznam";
var lblOutdent			= "Odsek dolava";
var lblIndent			= "Odsek doprava";
var lblTextColor		= "Farba písma";
var lblBgColor			= "Farba pozadia";
var lblSearch			= "Nájst a nahradit";
var lblInsertLink		= "Vložit odkaz";
var lblUnLink			= "Remove link";
var lblAddImage			= "Vložit obrázok";
var lblInsertTable		= "Vložit tabulku";
var lblWordCount		= "Word Count";
var lblUnformat			= "Unformat";
// Dropdowns
// Format Dropdown
var lblFormat			= "<option value=\"\" selected=\"selected\">Formát</option>";
lblFormat				+= "<option value=\"&lt;h1&gt;\">Nadpis 1</option>";
lblFormat				+= "<option value=\"&lt;h2&gt;\">Nadpis 2</option>";
lblFormat				+= "<option value=\"&lt;h3&gt;\">Nadpis 3</option>";
lblFormat				+= "<option value=\"&lt;h4&gt;\">Nadpis 4</option>";
lblFormat				+= "<option value=\"&lt;h5&gt;\">Nadpis 5</option>";
lblFormat				+= "<option value=\"&lt;h6&gt;\">Nadpis 6</option>";
lblFormat				+= "<option value=\"&lt;p&gt;\">Odstavec</option>";
lblFormat				+= "<option value=\"&lt;address&gt;\">Adresa</option>";
lblFormat				+= "<option value=\"&lt;pre&gt;\">Vopred naformátované</option>";
// Font Dropdown
var lblFont				= "<option value=\"\" selected=\"selected\">Písmo</option>";
lblFont					+= "<option value=\"Arial, Helvetica, sans-serif\">Arial</option>";
lblFont					+= "<option value=\"Courier New, Courier, mono\">Courier New</option>";
lblFont					+= "<option value=\"Palatino Linotype\">Palatino Linotype</option>";
lblFont					+= "<option value=\"Times New Roman, Times, serif\">Times New Roman</option>";
lblFont					+= "<option value=\"Verdana, Arial, Helvetica, sans-serif\">Verdana</option>";
var lblApplyFont		= "Apply selected font";
// Size Dropdown
var lblSize				= "<option value=\"\">Velkost</option>";
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
var lblSearchConfirm	= "Hladaný výraz [SF] bol nájdený [RUNCOUNT] krát.\n\n"; // Nechat v [SF], [RUNCOUNT] a [RW]
lblSearchConfirm		+= "Naozaj chcete zamenit tieto vstupy s [RW] ?\n";
var lblSearchAbort		= "Operácia zrušená.";
var lblSearchNotFound	= "nenájdené.";
var lblCountTotal		= "Word Count";
var lblCountChar		= "Available Characters";
var lblCountCharWarn	= "Warning! Your content is too long and may not save correctly.";
// Dialogs
// Insert Link
var lblLinkBlank		= "new window (_blank)";
var lblLinkSelf			= "same frame (_self)";
var lblLinkParent		= "parent frame (_parent)";
var lblLinkTop			= "first frame (_top)";
var lblLinkType			= "typ odkazu";
var lblLinkOldA			= "existujúca kotva";
var lblLinkNewA			= "nová kotva";
var lblLinkAnchors		= "Kotvy";
var lblLinkAddress		= "Adresa";
var lblLinkText			= "textový odkAz";
var lblLinkOpenIn		= "otvorit odkaz v";
var lblLinkVal0			= "Vložte url adresu.";
var lblLinkSubmit		= "OK";
var lblLinkCancel		= "Zrušit";
var lblLinkRelative		= "relative";
var lblLinkEmail		= "email";
var lblLinkDefault		= "Default";
// Insert Image
var lblImageURL			= "URL obrázok";
var lblImageAltText		= "alternatívny text";
var lblImageVal0		= "oznacit URL obrázok.";
var lblImageSubmit		= "OK";
var lblImageCancel		= "Zrušit";
// Insert Table
var lblTableRows		= "Riadky";
var lblTableColumns		= "Stlpce";
var lblTableWidth		= "Šírka tabulky";
var lblTablePx			= "pixely";
var lblTablePercent		= "percento";
var lblTableBorder		= "Hrúbka okraja";
var lblTablePadding		= "Obsah bunky";
var lblTableSpacing		= "Odstup buniek";
var lblTableSubmit		= "OK";
var lblTableCancel		= "Zrušit";
// Search and Replace
var lblSearchFind		= "Hladat";
var lblSearchReplace	= "Zamenit za";
var lblSearchMatch		= "Porovnat";
var lblSearchWholeWord	= "Hladat iba celé slová";
var lblSearchVal0		= "Vložit nieco do \"Hladat:\".";
var lblSearchSubmit		= "OK";
var lblSearchCancel		= "Zrušit";
// Paste As Plain Text
var lblPasteTextHint	= "Hint: To paste you can either right-click and choose \"Paste\" or use the key combination of Ctrl-V.";
var lblPasteTextVal0	= "Please enter text."
var lblPasteTextSubmit	= "OK";
var lblPasteTextCancel	= "Zrušit";
// Paste from Word
var lblPasteWordHint	= "Hint: To paste you can either right-click and choose \"Paste\" or use the key combination of Ctrl-V.";
var lblPasteWordVal0	= "Please enter text."
var lblPasteWordSubmit	= "OK";
var lblPasteWordCancel	= "Zrušit";
// non-designMode
var lblAutoBR			= "použit automatický prerušovac ciar";
var lblRawHTML			= "Použit iba cistý HTML";
var lblnon_designMode	= 'Použit Rich Text Editor, vyžaduje sa <a href="http://www.mozilla.org/" target="_blank">Mozilla 1.3+</a> browser (eg, <a href="http://www.getfirefox.com/" target="_blank">Firefox</a>), <a href="http://www.apple.com/safari/download/" target="_blank">Safari 1.3+</a>, <a href="http://www.opera.com/" target="_blank">Opera 9+</a> alebo <a href="http://www.microsoft.com/windows/products/winfamily/ie/default.mspx" target="_blank">MS IE5.5+</a>.';
