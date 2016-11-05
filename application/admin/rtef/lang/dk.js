// Danish Language File (UTF-8)
// Translation provided by Martin Vium, Anders Jenbo, Lars Christian Jensen and M.P. Rommedahl

// Settings
var lang = "da"; // xhtml language
var lang_direction = "ltr"; // language direction:ltr=left-to-right,rtl=right-to-left

// Buttons
var lblModeRichText		= "Skift til grafisk visning"; // Label of the Show Design view link
var lblModeHTML			= "Skift til HTML-visning"; // Label of the Show Code view link
var lblSave				= "Gem";
var lblPrint			= "Udskriv";
var lblSelectAll		= "Markér alt";
var lblSpellCheck		= "Stavekontrol";
var lblCut				= "Klip";
var lblCopy				= "Kopiér";
var lblPaste			= "Indsæt";
var lblPasteText		= "Indsæt fra normal tekst";
var lblPasteWord		= "Indsæt fra Word";
var lblUndo				= "Fortryd";
var lblRedo				= "Gentag";
var lblHR				= "Indsæt vandret streg";
var lblInsertChar		= "Indsæt specialtegn";
var lblBold				= "Fed skrift";
var lblItalic			= "Kursiv";
var lblUnderline		= "Understregning";
var lblStrikeThrough	= "Gennemstregning";
var lblSuperscript		= "Hævet skrift";
var lblSubscript		= "Sænket skrift";
var lblAlgnLeft			= "Justér til venstre";
var lblAlgnCenter		= "Centrér";
var lblAlgnRight		= "Justeret til højre";
var lblJustifyFull		= "Lige margener";
var lblOL				= "Opstilling med tal";
var lblUL				= "Opstilling med punkttegn";
var lblOutdent			= "Tilbagejustering";
var lblIndent			= "Indjustering";
var lblTextColor		= "Tekstfarve";
var lblBgColor			= "Baggrundsfarve";
var lblSearch			= "Søg og erstat";
var lblInsertLink		= "Indsæt henvisning";
var lblUnLink			= "Fjern henvisning";
var lblAddImage			= "Indsæt billede";
var lblInsertTable		= "Indsæt tabel";
var lblWordCount		= "Ordoptælling";
var lblUnformat			= "Fjern formatering";
// Dropdowns
// Format Dropdown
var lblFormat			= "<option value=\"\" selected=\"selected\">Formatering</option>";
lblFormat				+= "<option value=\"&lt;h1&gt;\">Overskrift 1</option>";
lblFormat				+= "<option value=\"&lt;h2&gt;\">Overskrift 2</option>";
lblFormat				+= "<option value=\"&lt;h3&gt;\">Overskrift 3</option>";
lblFormat				+= "<option value=\"&lt;h4&gt;\">Overskrift 4</option>";
lblFormat				+= "<option value=\"&lt;h5&gt;\">Overskrift 5</option>";
lblFormat				+= "<option value=\"&lt;h6&gt;\">Overskrift 6</option>";
lblFormat				+= "<option value=\"&lt;p&gt;\">Paragraf</option>";
lblFormat				+= "<option value=\"&lt;address&gt;\">Adresse</option>";
lblFormat				+= "<option value=\"&lt;pre&gt;\">Maskintekst</option>";
// Font Dropdown
var lblFont				= "<option value=\"\" selected=\"selected\">Skrifttype</option>";
lblFont					+= "<option value=\"Arial, Helvetica, sans-serif\">Arial</option>";
lblFont					+= "<option value=\"Courier New, Courier, mono\">Courier New</option>";
lblFont					+= "<option value=\"Palatino Linotype\">Palatino Linotype</option>";
lblFont					+= "<option value=\"Times New Roman, Times, serif\">Times New Roman</option>";
lblFont					+= "<option value=\"Verdana, Arial, Helvetica, sans-serif\">Verdana</option>";
var lblApplyFont		= "Benyt skrift type";
// Size Dropdown
var lblSize				= "<option value=\"\">Skriftstørrelse</option>";
lblSize					+= "<option value=\"1\">1</option>";
lblSize					+= "<option value=\"2\">2</option>";
lblSize					+= "<option value=\"3\">3</option>";
lblSize					+= "<option value=\"4\">4</option>";
lblSize					+= "<option value=\"5\">5</option>";
lblSize					+= "<option value=\"6\">6</option>";
lblSize					+= "<option value=\"7\">7</option>";
//Size buttons
var lblIncreasefontsize		= "Øg skriftstørrelsen";
var lblDecreasefontsize		= "Nedjustér skriftstørrelsen";

// Alerts
var lblSearchConfirm	= "Søgeordet [SF] blev fundet [RUNCOUNT] gang(e).\n\n"; // Leave in [SF], [RUNCOUNT] and [RW]
lblSearchConfirm		+= "Er du sikker på du vil erstatte disse ord med [RW] ?\n";
var lblSearchAbort		= "Handling annulleret.";
var lblSearchNotFound	= "blev ikke fundet.";
var lblCountTotal		= "Ordtælling";
var lblCountChar		= "Bogstaver tilbage";
var lblCountCharWarn	= "Advarsel! Din tekst er for lang og kan måske ikke gemmes korekt.";

// Dialogs
// Insert Link
var lblLinkBlank		= "Nyt vindue (_blank)";
var lblLinkSelf			= "Samme ramme (_self)";
var lblLinkParent		= "En ramme op (_parent)";
var lblLinkTop			= "Første ramme (_top)";
var lblLinkType			= "Henvisningstype";
var lblLinkOldA			= "eksisterende anker";
var lblLinkNewA			= "nyt anker";
var lblLinkAnchors		= "Anker";
var lblLinkAddress		= "Adresse";
var lblLinkText			= "Henvisningstekst";
var lblLinkOpenIn		= "Åbn henvisning i";
var lblLinkVal0			= "Indsæt venligst en adresse.";
var lblLinkSubmit		= "O.k.";
var lblLinkCancel		= "Annullér";
var lblLinkRelative		= "relativ";
var lblLinkEmail		= "e-mail";
var lblLinkDefault		= "Standard";
// Insert Image
var lblImageURL			= "Billedaddresse";
var lblImageAltText		= "Alternativ tekst";
var lblImageVal0		= "Indtast venligst adressen til billedet.";
var lblImageSubmit		= "O.k.";
var lblImageCancel		= "Annullér";
// Insert Table
var lblTableRows		= "Rækker";
var lblTableColumns		= "Kolonner";
var lblTableWidth		= "Tabelbredde";
var lblTablePx			= "pixels";
var lblTablePercent		= "procent";
var lblTableBorder		= "Kantens tykkelse";
var lblTablePadding		= "Celleindjustering";
var lblTableSpacing		= "Cellemargin";
var lblTableSubmit		= "O.k.";
var lblTableCancel		= "Annullér";
// Search and Replace
var lblSearchFind		= "Søg efter";
var lblSearchReplace	= "Erstat med";
var lblSearchMatch		= "Forskel på store og små bogstaver";
var lblSearchWholeWord	= "Søg kun efter hele ord";
var lblSearchVal0		= "Vær venlig at indtaste et søgeord.";
var lblSearchSubmit		= "O.k.";
var lblSearchCancel		= "Annullér";
// Paste As Plain Text
var lblPasteTextHint	= "Tip: For at indsætte, højreklik og vælg \"Indsæt\" eller brug genvestasterne Ctrl-V.";
var lblPasteTextVal0	= "Indtast tekst."
var lblPasteTextSubmit	= "O.k.";
var lblPasteTextCancel	= "Annullér";
// Paste As Plain Text
var lblPasteWordHint	= "Tip: For at indsætte, højreklik og vælg \"Indsæt\" eller brug genvestasterne Ctrl-V.";
var lblPasteWordVal0	= "Indtast tekst."
var lblPasteWordSubmit	= "O.k.";
var lblPasteWordCancel	= "Annullér";

// non-designMode
var lblAutoBR			= "Benyt automatiske linjeskift";
var lblRawHTML			= "Benyt kun ren HTML";
var lblnon_designMode	= 'For at benytte den grafiske tekstredigering, kræves en browser baseret på <a href="http://www.mozilla.org/" target="_blank">Mozilla 1.3+</a> (fx. <a href="http://www.mozilla-europe.org/da/products/firefox/" target="_blank">Firefox</a>), <a href="http://www.apple.com/safari/download/" target="_blank">Safari 1.3+</a>, <a href="http://www.opera.com/" target="_blank">Opera 9+</a> eller <a href="http://www.microsoft.com/windows/products/winfamily/ie/default.mspx" target="_blank">MS IE5.5+</a>.';
