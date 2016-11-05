// Italian Language File
// Translation provided by Giulio Paci and LucaS(nickname)

// Settings
var lang = "it"; // xhtml language
var lang_direction = "ltr"; // language direction:ltr=left-to-right,rtl=right-to-left

// Buttons
var lblSubmit			= "Invia"; // Button value for non-designMode() & non fullsceen RTE
var lblModeRichText		= "Visualizza testo formattato"; // Label of the Show Design view link
var lblModeHTML			= "Visualizza sorgente HTML"; // Label of the Show Code view link
var lblSave				= "Salva";
var lblPrint			= "Stampa";
var lblSelectAll		= "Seleziona/Deseleziona Tutto";
var lblSpellCheck		= "Controllo ortografico";
var lblCut				= "Taglia";
var lblCopy				= "Copia";
var lblPaste			= "Incolla";
var lblPasteText		= "Incolla come Testo Semplice";
var lblPasteWord		= "Incolla da Word";
var lblUndo				= "Annulla";
var lblRedo				= "Ripristina";
var lblHR				= "Riga orizzontale";
var lblInsertChar		= "Inserisci Carattere Speciale";
var lblBold				= "Grassetto";
var lblItalic			= "Corsivo";
var lblUnderline		= "Sottolineato";
var lblStrikeThrough	= "Barrato";
var lblSuperscript		= "Apice";
var lblSubscript		= "Pedice";
var lblAlgnLeft			= "Allinea a sinistra";
var lblAlgnCenter		= "Centrato";
var lblAlgnRight		= "Allinea a destra";
var lblJustifyFull		= "Giustificato";
var lblOL				= "Elenco numerato";
var lblUL				= "Elenco non numerato";
var lblOutdent			= "Togli indentazione";
var lblIndent			= "Indenta";
var lblTextColor		= "Colore del testo";
var lblBgColor			= "Colore di sfondo";
var lblSearch			= "Cerca e Sostituisci";
var lblInsertLink		= "Inserisci collegamento";
var lblUnLink			= "Remove link";
var lblAddImage			= "Aggiungi immagine";
var lblInsertTable		= "Inserisci tabella";
var lblWordCount		= "Conteggio parole";
var lblUnformat			= "Togli formattazione";
// Dropdowns
// Format Dropdown
var lblFormat			= "<option value=\"\" selected=\"selected\">Formato</option>";
lblFormat				+= "<option value=\"&lt;h1&gt;\">Intestazione 1</option>";
lblFormat				+= "<option value=\"&lt;h2&gt;\">Intestazione 2</option>";
lblFormat				+= "<option value=\"&lt;h3&gt;\">Intestazione 3</option>";
lblFormat				+= "<option value=\"&lt;h4&gt;\">Intestazione 4</option>";
lblFormat				+= "<option value=\"&lt;h5&gt;\">Intestazione 5</option>";
lblFormat				+= "<option value=\"&lt;h6&gt;\">Intestazione 6</option>";
lblFormat				+= "<option value=\"&lt;p&gt;\">Paragrafo</option>";
lblFormat				+= "<option value=\"&lt;address&gt;\">Indirizzo</option>";
lblFormat				+= "<option value=\"&lt;pre&gt;\">Preformattato</option>";
// Font Dropdown
var lblFont				= "<option value=\"\" selected=\"selected\">Carattere</option>";
lblFont					+= "<option value=\"Arial, Helvetica, sans-serif\">Arial</option>";
lblFont					+= "<option value=\"Courier New, Courier, mono\">Courier New</option>";
lblFont					+= "<option value=\"Palatino Linotype\">Palatino Linotype</option>";
lblFont					+= "<option value=\"Times New Roman, Times, serif\">Times New Roman</option>";
lblFont					+= "<option value=\"Verdana, Arial, Helvetica, sans-serif\">Verdana</option>";
var lblApplyFont		= "Apply selected font";
// Size Dropdown
var lblSize				= "<option value=\"\">Dimensione</option>";
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
var lblSearchConfirm	= "La stringa cercata [SF] è stata trovata [RUNCOUNT] volte.\n\n"; // Leave in [SF], [RUNCOUNT] and [RW]
lblSearchConfirm		+= "Sei sicuro di voler sostituire la stringa con [RW] ?\n";
var lblSearchAbort		= "Operazione annullata.";
var lblSearchNotFound	= "non è stata trovata.";
var lblCountTotal		= "Parole totali:";
var lblCountChar		= "Caratteri Disponibili";
var lblCountCharWarn	= "Attenzione! Il contenuto è troppo lungo e potrebbe non essere salvato correttamente.";
// Dialogs
// Insert Link
var lblLinkBlank		= "new window (_blank)";
var lblLinkSelf			= "same frame (_self)";
var lblLinkParent		= "parent frame (_parent)";
var lblLinkTop			= "first frame (_top)";
var lblLinkType			= "Tipo del collegamento";
var lblLinkOldA			= "Ancoraggio esistente";
var lblLinkNewA			= "Nuovo ancoraggio";
var lblLinkAnchors		= "Ancoraggio";
var lblLinkAddress		= "Indirizzo";
var lblLinkText			= "Testo del collegamento";
var lblLinkOpenIn		= "Apri il collegamento in";
var lblLinkVal0			= "Inserire un URL.";
var lblLinkSubmit		= "OK";
var lblLinkCancel		= "Annulla";
var lblLinkRelative		= "relative";
var lblLinkEmail		= "email";
var lblLinkDefault		= "Default";
// Insert Image
var lblImageURL			= "URL dell\'immagine";
var lblImageAltText		= "Testo alternativo";
var lblImageVal0		= "Inserire l\'\"" + lblImageURL + "\".";
var lblImageSubmit		= "OK";
var lblImageCancel		= "Annulla";
// Insert Table
var lblTableRows		= "Righe";
var lblTableColumns		= "Colonne";
var lblTableWidth		= "Larghezza";
var lblTablePx			= "pixels";
var lblTablePercent		= "percentuale";
var lblTableBorder		= "Bordo";
var lblTablePadding		= "Margine della cella";
var lblTableSpacing		= "Margine fra celle";
var lblTableSubmit		= "OK";
var lblTableCancel		= "Annulla";
// Search and Replace
var lblSearchFind		= "Cerca";
var lblSearchReplace	= "Sostituisci con";
var lblSearchMatch		= "Distingui fra maiuscole e minuscole";
var lblSearchWholeWord	= "Trova solo parole intere";
var lblSearchVal0		= "Devi inserire qualcosa in \"" + lblSearchFind + "\".";
var lblSearchSubmit		= "OK";
var lblSearchCancel		= "Annulla";
// Paste As Plain Text
var lblPasteTextHint	= "Suggerimento: per incollare è possibile cliccare col tasto destro del mouse e scegliere \"Incolla\" o usare la combinazione di tasti Ctrl+V.";
var lblPasteTextVal0	= "Inserire il testo."
var lblPasteTextSubmit	= "OK";
var lblPasteTextCancel	= "Annulla";
// Paste from Word
var lblPasteWordHint	= "Suggerimento: per incollare è possibile cliccare col tasto destro del mouse e scegliere \"Incolla\" o usare la combinazione di tasti Ctrl+V.";
var lblPasteWordVal0	= "Inserire il testo." 
var lblPasteWordSubmit	= "OK";
var lblPasteWordCancel	= "Annulla";
// non-designMode
var lblAutoBR			= "Utilizza \"a capo\" automatici";
var lblRawHTML			= "Utilizza solo HTML grezzo";
var lblnon_designMode	= 'Per usare il Rich Text Editor, è necessario un browser <a href="http://www.mozilla.org/" target="_blank">Mozilla 1.3+</a> (es, <a href="http://www.getfirefox.com/" target="_blank">Firefox</a>), <a href="http://www.apple.com/safari/download/" target="_blank">Safari 1.3+</a>, <a href="http://www.opera.com/" target="_blank">Opera 9+</a>  o <a href="http://www.microsoft.com/windows/products/winfamily/ie/default.mspx" target="_blank">MS IE5+</a> (Windows). I browser IE5(Mac) non sono al momento supportati e tutto il testo deve essere inserito in HTML.';
