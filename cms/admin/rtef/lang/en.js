// English Language File
// Translation provided by Timothy Bell

// Settings
var lang = "en"; // xhtml language
var lang_direction = "ltr"; // language direction:ltr=left-to-right,rtl=right-to-left

// Buttons
var lblSubmit			= "Submit"; // Button value fullsceen RTE
var lblModeRichText		= "Switch to RichText Mode"; // Label of the Show Design view link
var lblModeHTML			= "Switch to HTML Mode"; // Label of the Show Code view link
var lblSave				= "Save";
var lblPrint			= "Print";
var lblSelectAll		= "Select/Deselect All";
var lblSpellCheck		= "Spell Check";
var lblCut				= "Cut";
var lblCopy				= "Copy";
var lblPaste			= "Paste";
var lblPasteText		= "Paste as Plain Text";
var lblPasteWord		= "Paste From Word";
var lblUndo				= "Undo";
var lblRedo				= "Redo";
var lblHR				= "Horizontal Rule";
var lblInsertChar		= "Insert Special Character";
var lblBold				= "Bold";
var lblItalic			= "Italic";
var lblUnderline		= "Underline";
var lblStrikeThrough	= "Strike Through";
var lblSuperscript		= "Superscript";
var lblSubscript		= "Subscript";
var lblAlgnLeft			= "Align Left";
var lblAlgnCenter		= "Center";
var lblAlgnRight		= "Align Right";
var lblJustifyFull		= "Justify Full";
var lblOL				= "Ordered List";
var lblUL				= "Unordered List";
var lblOutdent			= "Outdent";
var lblIndent			= "Indent";
var lblTextColor		= "Text Color";
var lblBgColor			= "Background Color";
var lblSearch			= "Search And Replace";
var lblInsertLink		= "Insert Link";
var lblUnLink			= "Remove link";
var lblAddImage			= "Add Image";
var lblInsertTable		= "Insert Table";
var lblWordCount		= "Word Count";
var lblUnformat			= "Unformat";
// Dropdowns
// Format Dropdown
var lblFormat			= "<option value=\"\" selected=\"selected\">Format</option>";
lblFormat				+= "<option value=\"&lt;h1&gt;\">Heading 1</option>";
lblFormat				+= "<option value=\"&lt;h2&gt;\">Heading 2</option>";
lblFormat				+= "<option value=\"&lt;h3&gt;\">Heading 3</option>";
lblFormat				+= "<option value=\"&lt;h4&gt;\">Heading 4</option>";
lblFormat				+= "<option value=\"&lt;h5&gt;\">Heading 5</option>";
lblFormat				+= "<option value=\"&lt;h6&gt;\">Heading 6</option>";
lblFormat				+= "<option value=\"&lt;p&gt;\">Paragraph</option>";
lblFormat				+= "<option value=\"&lt;address&gt;\">Address</option>";
lblFormat				+= "<option value=\"&lt;pre&gt;\">Preformatted</option>";
// Font Dropdown
var lblFont				= "<option value=\"\" selected=\"selected\">Font</option>";
lblFont					+= "<option value=\"Arial, Helvetica, sans-serif\">Arial</option>";
lblFont					+= "<option value=\"Courier New, Courier, mono\">Courier New</option>";
lblFont					+= "<option value=\"Palatino Linotype\">Palatino Linotype</option>";
lblFont					+= "<option value=\"Times New Roman, Times, serif\">Times New Roman</option>";
lblFont					+= "<option value=\"Verdana, Arial, Helvetica, sans-serif\">Verdana</option>";
var lblApplyFont		= "Apply selected font";
// Size Dropdown
var lblSize				= "<option value=\"\">Size</option>";
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
var lblSearchConfirm	= "The search expression [SF] was found [RUNCOUNT] time(s).\n\n"; // Leave in [SF], [RUNCOUNT] and [RW]
lblSearchConfirm		+= "Are you sure you want to replace these entries with [RW] ?\n";
var lblSearchAbort		= "Operation Aborted.";
var lblSearchNotFound	= "was not found.";
var lblCountTotal		= "Word Count";
var lblCountChar		= "Available Characters";
var lblCountCharWarn	= "Warning! Your content is too long and may not save correctly.";
// Dialogs
// Insert Link
var lblLinkBlank		= "new window (_blank)";
var lblLinkSelf			= "same frame (_self)";
var lblLinkParent		= "parent frame (_parent)";
var lblLinkTop			= "first frame (_top)";
var lblLinkType			= "Link Type";
var lblLinkOldA			= "existing anchor";
var lblLinkNewA			= "new anchor";
var lblLinkAnchors		= "Anchors";
var lblLinkAddress		= "Address";
var lblLinkText			= "Link Text";
var lblLinkOpenIn		= "Open Link In";
var lblLinkVal0			= "Please enter a url.";
var lblLinkSubmit		= "OK";
var lblLinkCancel		= "Cancel";
var lblLinkRelative		= "relative";
var lblLinkEmail		= "email";
var lblLinkDefault		= "Default";
// Insert Image
var lblImageURL			= "Image URL";
var lblImageAltText		= "Alternative Text";
var lblImageVal0		= "Please indicate the \"Image URL\".";
var lblImageSubmit		= "OK";
var lblImageCancel		= "Cancel";
// Insert Table
var lblTableRows		= "Rows";
var lblTableColumns		= "Columns";
var lblTableWidth		= "Table width";
var lblTablePx			= "pixels";
var lblTablePercent		= "percent";
var lblTableBorder		= "Border thickness";
var lblTablePadding		= "Cell padding";
var lblTableSpacing		= "Cell spacing";
var lblTableSubmit		= "OK";
var lblTableCancel		= "Cancel";
// Search and Replace
var lblSearchFind		= "Find what";
var lblSearchReplace	= "Replace with";
var lblSearchMatch		= "Match case";
var lblSearchWholeWord	= "Find whole words only";
var lblSearchVal0		= "You must enter something into \"Find what:\".";
var lblSearchSubmit		= "OK";
var lblSearchCancel		= "Cancel";
// Paste As Plain Text
var lblPasteTextHint	= "Hint: To paste you can either right-click and choose \"Paste\" or use the key combination of Ctrl-V.";
var lblPasteTextVal0	= "Please enter text.";
var lblPasteTextSubmit	= "OK";
var lblPasteTextCancel	= "Cancel";
// Paste from Word
var lblPasteWordHint	= "Hint: To paste you can either right-click and choose \"Paste\" or use the key combination of Ctrl-V.";
var lblPasteWordVal0	= "Please enter text.";
var lblPasteWordSubmit	= "OK";
var lblPasteWordCancel	= "Cancel";
// non-designMode
var lblAutoBR			= "Use Auto Line Breaks";
var lblRawHTML			= "Use Only Raw HTML";
var lblnon_designMode	= 'To use the Rich Text Editor you must use a browser based on <a href="http://www.mozilla.org/" target="_blank">Mozilla 1.3+</a> (eg, <a href="http://www.getfirefox.com/" target="_blank">Firefox</a>), <a href="http://www.apple.com/safari/download/" target="_blank">Safari 1.3+</a>, <a href="http://www.opera.com/" target="_blank">Opera 9+</a> or <a href="http://www.microsoft.com/windows/products/winfamily/ie/default.mspx" target="_blank">MS IE5.5+</a>.';
