// Spanish Language File (UTF-8)
// Translation provided by Mariano Luna

// Settings
var lang = "es"; // xhtml language
var lang_direction = "ltr"; // language direction:ltr=left-to-right,rtl=right-to-left

// Buttons
var lblSubmit			= "Enviar"; // Button value for non-designMode() & non fullsceen RTE
var lblModeRichText		= "Cambiar al modo Texto Enriquecido"; // Label of the Show Design view link
var lblModeHTML			= "Cambiar al modo HTML"; // Label of the Show Code view link
var lblSave				= "Guardar";
var lblPrint			= "Imprimir";
var lblSelectAll		= "Seleccionar/deseleccionar Todo";
var lblSpellCheck		= "Ortografía";
var lblCut				= "Cortar";
var lblCopy				= "Copiar";
var lblPaste			= "Pegar";
var lblPasteText		= "Pegar sin formato";
var lblPasteWord		= "Pegar con formato";
var lblUndo				= "Deshacer";
var lblRedo				= "Rehacer";
var lblHR				= "Línea horizontal";
var lblInsertChar		= "Insertar símbolo";
var lblBold				= "Negrita";
var lblItalic			= "Cursiva";
var lblUnderline		= "Subrayado";
var lblStrikeThrough	= "Tachado";
var lblSuperscript		= "Superíndice";
var lblSubscript		= "Subíndice";
var lblAlgnLeft			= "Alinear a la izquierda";
var lblAlgnCenter		= "Centrar";
var lblAlgnRight		= "Alinear a la derecha";
var lblJustifyFull		= "Justificar";
var lblOL				= "Numeración";
var lblUL				= "Viñetas";
var lblOutdent			= "Reducir sangría";
var lblIndent			= "Aumentar sangría";
var lblTextColor		= "Color de fuente";
var lblBgColor			= "Color de fondo";
var lblSearch			= "Buscar y reemplazar";
var lblInsertLink		= "Insertar hipervínculo";
var lblUnLink			= "Remove link";
var lblAddImage			= "Agregar imagen";
var lblInsertTable		= "Insertar tabla";
var lblWordCount		= "Contar palabras";
var lblUnformat			= "Sin formato";

// Dropdowns
// Format Dropdown
var lblFormat			= "<option value=\"\" selected=\"selected\">Formato</option>";
lblFormat				+= "<option value=\"&lt;h1&gt;\">Encabezado 1</option>";
lblFormat				+= "<option value=\"&lt;h2&gt;\">Encabezado 2</option>";
lblFormat				+= "<option value=\"&lt;h3&gt;\">Encabezado 3</option>";
lblFormat				+= "<option value=\"&lt;h4&gt;\">Encabezado 4</option>";
lblFormat				+= "<option value=\"&lt;h5&gt;\">Encabezado 5</option>";
lblFormat				+= "<option value=\"&lt;h6&gt;\">Encabezado 6</option>";
lblFormat				+= "<option value=\"&lt;p&gt;\">Párrafo</option>";
lblFormat				+= "<option value=\"&lt;address&gt;\">Dirección</option>";
lblFormat				+= "<option value=\"&lt;pre&gt;\">Preformateado</option>";
// Font Dropdown
var lblFont				= "<option value=\"\" selected=\"selected\">Fuente</option>";
lblFont					+= "<option value=\"Arial, Helvetica, sans-serif\">Arial</option>";
lblFont					+= "<option value=\"Courier New, Courier, mono\">Courier New</option>";
lblFont					+= "<option value=\"Palatino Linotype\">Palatino Linotype</option>";
lblFont					+= "<option value=\"Times New Roman, Times, serif\">Times New Roman</option>";
lblFont					+= "<option value=\"Verdana, Arial, Helvetica, sans-serif\">Verdana</option>";
var lblApplyFont		= "Apply selected font";
// Size Dropdown
var lblSize				= "<option value=\"\">Tamaño</option>";
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
var lblSearchConfirm	= "La expresión de busqueda [SF] fue encontrada [RUNCOUNT] veces.\n\n"; // Leave in [SF], [RUNCOUNT] and [RW]
lblSearchConfirm		+= "¿Está seguro que desea reemplazar estas entradas con [RW]?\n";
var lblSearchAbort		= "Operacion Abortada.";
var lblSearchNotFound	= "no fué encontrada.";
var lblCountTotal		= "Número de palabras";
var lblCountChar		= "Número de caracteres disponibles";
var lblCountCharWarn	= "¡Aviso! El texto es demasiado largo y no puede ser guardado correctamente.";
// Dialogs
// Insert Link
var lblLinkBlank		= "Ventana nueva";
var lblLinkSelf			= "Ventana o marco actual";
var lblLinkParent		= "Marco padre";
var lblLinkTop			= "Ventana completa del navegador";
var lblLinkType			= "Tipo de Enlace";
var lblLinkOldA			= "Ancha existente";
var lblLinkNewA			= "Ancla nueva";
var lblLinkAnchors		= "Anclas";
var lblLinkAddress		= "Dirección";
var lblLinkText			= "Texto del enlace";
var lblLinkOpenIn		= "Abrir enlace en";
var lblLinkVal0			= "Por favor, ingrese la dirección.";
var lblLinkSubmit		= "Insertar";
var lblLinkCancel		= "Cancelar";
var lblLinkRelative		= "Relativo";
var lblLinkEmail		= "Correo electrónico";
var lblLinkDefault		= "Default";
// Insert Image
var lblImageURL			= "URL de la Imagen";
var lblImageAltText		= "Texto alternativo";
var lblImageVal0		= "Por favor indique el \"URL de la Imagen\".";
var lblImageSubmit		= "OK";
var lblImageCancel		= "Cancelar";
// Insert Table
var lblTableRows		= "Filas";
var lblTableColumns		= "Columnas";
var lblTableWidth		= "Ancho de la tabla";
var lblTablePx			= "píxeles";
var lblTablePercent		= "porcentaje";
var lblTableBorder		= "Ancho del borde";
var lblTablePadding		= "Margen de celdas";
var lblTableSpacing		= "Espaciado entre celdas";
var lblTableSubmit		= "Insertar";
var lblTableCancel		= "Cancelar";
// Search and Replace
var lblSearchFind		= "Buscar";
var lblSearchReplace	= "Reemplazar con";
var lblSearchMatch		= "Distinguir mayúsculas y minúsculas";
var lblSearchWholeWord	= "Solo palabras completas";
var lblSearchVal0		= "Por favor, ingrese el texto a buscar.";
var lblSearchSubmit		= "Buscar";
var lblSearchCancel		= "Cancelar";
// Paste As Plain Text
var lblPasteTextHint	= "Sugerencia: Para pegar puede hacer clic en el botón derecho del ratón y elegir \"Pegar\" o usar la conbinación de teclas Ctrl-V.";
var lblPasteTextVal0	= "Por favor, ingrese el texto.";
var lblPasteTextSubmit	= "Pegar";
var lblPasteTextCancel	= "Cancelar";
// Paste from Word
var lblPasteWordHint	= "Sugerencia: Para pegar puede hacer clic en el botón derecho del ratón y elegir \"Pegar\" o usar la conbinación de teclas Ctrl-V.";
var lblPasteWordVal0	= "Por favor, ingrese el texto.";
var lblPasteWordSubmit	= "Pegar";
var lblPasteWordCancel	= "Cancelar";

// non-designMode
var lblAutoBR			= "Usar corte de línea automático";
var lblRawHTML			= "Usar solo HTML puro";
var lblnon_designMode	= 'Para usar el Editor de Texto Enriquecido, se requiere un navegador tipo <a href="http://www.mozilla.org/" target="_blank">Mozilla 1.3+</a> (ej, <a href="http://www.getfirefox.com/" target="_blank">Firefox</a>), <a href="http://www.apple.com/safari/download/" target="_blank">Safari 1.3+</a>, <a href="http://www.opera.com/" target="_blank">Opera 9+</a> o <a href="http://www.microsoft.com/windows/products/winfamily/ie/default.mspx" target="_blank">MS IE5.5+</a>.';
