<?php
# vicidial_modern.php
#
# Capa de estilos "modern skin" para la interfaz de administracion de VICIDIAL.
# Se incluye al final de vicidial_stylesheet.php (que ya cargan todas las
# pantallas de admin y reportes), por lo que sus reglas ganan a las anteriores
# en caso de empate de especificidad.
#
# Es puramente visual: no cambia markup ni logica. Para revertir, basta con
# comentar la linea "include" al final de vicidial_stylesheet.php
#
# 260731 - First build
#

# Colores del tema, tomados de screen_colors.php (ya incluido por el stylesheet)
$MODmenu   = (isset($SSmenu_background))   ? $SSmenu_background   : '015B91';
$MODframe  = (isset($SSframe_background))  ? $SSframe_background  : 'D9E6FE';
$MODbutton = (isset($SSbutton_color))      ? $SSbutton_color      : 'EFEFEF';
?>

/* ============================================================
   VICIDIAL MODERN SKIN
   ============================================================ */

:root {
	--vici-menu:    #<?php echo $MODmenu; ?>;
	--vici-frame:   #<?php echo $MODframe; ?>;
	--vici-accent:  #<?php echo $MODmenu; ?>;
	--vici-font:    system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", Arial, sans-serif;
	--vici-mono:    ui-monospace, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
	--vici-radius:  8px;
	--vici-radius-sm: 6px;
	--vici-border:  #d3dbe6;
	--vici-shadow:  0 1px 2px rgba(15,23,42,.06), 0 4px 14px rgba(15,23,42,.07);
	--vici-shadow-sm: 0 1px 2px rgba(15,23,42,.10);
}

/* ---------- Tipografia base ---------- */
body, td, th, div, span, p, li, dd, dt, label, font, a,
input, select, textarea, button, legend, caption {
	font-family: var(--vici-font) !important;
}
body {
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	background-color: #eef2f7;
	color: #1f2937;
}
/* Se respeta el monoespaciado donde es intencional (alineacion de columnas) */
pre, code, tt, kbd, samp,
.diff td,
[class*="shadowbox"],
.android_campaign_header,
div.android_switchbutton, div.android_switchbutton_blue,
div.android_offbutton, div.android_onbutton,
div.android_offbutton_noshadow, div.android_onbutton_noshadow,
div.android_offbutton_large, div.android_onbutton_large,
div.dropdown_android_button,
span.android_offbutton, span.android_onbutton {
	font-family: var(--vici-mono) !important;
}

/* Los <FONT SIZE=1> de 10px son ilegibles en pantallas actuales */
font[size="1"] { font-size: 11px; }
font[size="2"] { font-size: 13px; }

/* ---------- Contenedor principal ---------- */
body > center > table {
	box-shadow: var(--vici-shadow);
	border-radius: var(--vici-radius);
	overflow: hidden;
	margin-top: 10px;
	margin-bottom: 18px;
}

/* ---------- Barra lateral de navegacion ---------- */
td[width="170"][bgcolor],
table[width="160"] {
	background-color: var(--vici-menu);
	background-image: linear-gradient(180deg, rgba(255,255,255,.10), rgba(0,0,0,.14));
}
td[width="170"][bgcolor] img[alt="System logo"] {
	border-radius: var(--vici-radius-sm);
	margin: 6px 0 2px 0;
}
table[width="160"] td {
	padding: 1px 6px;
}
table[width="160"] a {
	display: block;
	border-radius: var(--vici-radius-sm);
	transition: color .15s ease;
}
tr.head_style,
tr.subhead_style {
	background-color: transparent;
	transition: background-color .15s ease;
}
tr.head_style:hover     { background-color: rgba(255,255,255,.14); }
tr.subhead_style        { background-color: rgba(255,255,255,.86); }
tr.subhead_style:hover  { background-color: #ffffff; }
tr.head_style_selected,
tr.head_style_selected:hover,
tr.subhead_style_selected,
tr.subhead_style_selected:hover {
	background-color: #ffffff;
	box-shadow: inset 3px 0 0 rgba(0,0,0,.30);
}
.horiz_line {
	border-bottom: 1px solid rgba(255,255,255,.22);
}
.horiz_line_grey {
	border-bottom: 1px solid rgba(148,163,184,.55);
}

/* ---------- Filas de tablas / listados ---------- */
tr[bgcolor] > td,
tr[class*="records_list"] > td {
	padding: 3px 6px;
}
.records_list_x, .records_list_y {
	transition: background-color .12s ease;
}
.records_list_x:hover, .records_list_y:hover {
	background-color: #e8eefb;
}
table.question_td, TABLE.question_td,
table.help_td, TABLE.help_td {
	border-radius: var(--vici-radius) !important;
	box-shadow: var(--vici-shadow) !important;
	border-width: 1px !important;
	border-color: var(--vici-border) !important;
}
td.search_td, TD.search_td {
	border-radius: var(--vici-radius) !important;
	border-width: 1px !important;
}
div.scrolling {
	border: 1px solid var(--vici-border);
	border-radius: var(--vici-radius-sm);
	padding: 6px;
}

/* ---------- Campos de formulario ---------- */
input[type="text"], input[type="password"], input[type="number"],
input[type="search"], input[type="email"], input[type="tel"],
input[type="date"], input[type="time"], input[type="url"],
select, textarea {
	font-size: 12px;
	color: #1f2937;
	background-color: #ffffff;
	border: 1px solid var(--vici-border);
	border-radius: var(--vici-radius-sm);
	padding: 3px 6px;
	outline: none;
	transition: border-color .15s ease, box-shadow .15s ease;
}
input[type="text"]:hover, input[type="password"]:hover,
select:hover, textarea:hover {
	border-color: #a9b8cc;
}
input[type="text"]:focus, input[type="password"]:focus,
input[type="number"]:focus, input[type="search"]:focus,
input[type="email"]:focus, input[type="tel"]:focus,
input[type="date"]:focus, input[type="time"]:focus,
select:focus, textarea:focus {
	border-color: var(--vici-accent);
	box-shadow: 0 0 0 3px rgba(1,91,145,.16);
}
input[type="checkbox"], input[type="radio"] {
	accent-color: var(--vici-accent);
	width: 14px;
	height: 14px;
	vertical-align: middle;
}
.form_field, .form_field_whiteboard_android {
	border-radius: var(--vici-radius-sm);
	border-color: var(--vici-border) !important;
	font-size: 12px;
}
.required_field {
	border: 1px solid #e28a8a !important;
	border-radius: var(--vici-radius-sm);
	background-color: #fdecec;
	font-size: 12px;
}
textarea.chat_box, textarea.chat_box_ended {
	border: 1px solid var(--vici-border);
	border-radius: var(--vici-radius-sm);
	font-size: 12px;
	padding: 6px;
}

/* ---------- Botones ---------- */
/* Solo se reestilizan los botones sin color propio y los que usan el color de
   boton del tema; los que llevan un color inline distinto (indicadores de
   estado en reportes en tiempo real) se dejan intactos. */
input[type="submit"]:not([class]):not([style*="background"]),
input[type="button"]:not([class]):not([style*="background"]),
input[type="reset"]:not([class]):not([style*="background"]),
button:not([class]):not([style*="background"]),
input[type="submit"][style*="#<?php echo $MODbutton; ?>"],
input[type="button"][style*="#<?php echo $MODbutton; ?>"] {
	background: var(--vici-accent) !important;
	background-image: linear-gradient(180deg, rgba(255,255,255,.14), rgba(0,0,0,.06)) !important;
	color: #ffffff !important;
	font-size: 12px;
	font-weight: 600;
	border: 1px solid rgba(0,0,0,.12) !important;
	border-radius: var(--vici-radius-sm) !important;
	padding: 4px 14px;
	cursor: pointer;
	box-shadow: var(--vici-shadow-sm);
	transition: filter .15s ease, box-shadow .15s ease, transform .06s ease;
}
input[type="submit"]:hover, input[type="button"]:hover,
input[type="reset"]:hover, button:hover {
	filter: brightness(1.10);
}
input[type="submit"]:active, input[type="button"]:active,
input[type="reset"]:active, button:active {
	transform: translateY(1px);
	box-shadow: none;
}
input[type="submit"]:focus-visible, input[type="button"]:focus-visible,
button:focus-visible, a:focus-visible {
	outline: 2px solid var(--vici-accent);
	outline-offset: 2px;
}

/* Botones de color: se quita el biselado 3D y se aplanan */
input.red_btn, input.red_btn_mobile, input.red_btn_mobile_lg,
input.red_btn_mobile_sm, input.red_btn_anywidth {
	background-color: #c62828;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: var(--vici-radius-sm);
	padding: 4px 12px;
	cursor: pointer;
	box-shadow: var(--vici-shadow-sm);
	transition: filter .15s ease, transform .06s ease;
}
input.green_btn, input.green_btn_mobile, input.green_btn_mobile_lg,
input.green_btn_anywidth, input.green_btn_anywidth_lg {
	background-color: #1e8e3e;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: var(--vici-radius-sm);
	padding: 4px 12px;
	cursor: pointer;
	box-shadow: var(--vici-shadow-sm);
	transition: filter .15s ease, transform .06s ease;
}
input.blue_btn, input.blue_btn_mobile {
	background-color: #1a56b8;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: var(--vici-radius-sm);
	padding: 4px 12px;
	cursor: pointer;
	box-shadow: var(--vici-shadow-sm);
	transition: filter .15s ease, transform .06s ease;
}
input.tiny_red_btn, input.tiny_blue_btn,
input.tiny_yellow_btn, input.tiny_green_btn {
	font-size: 10px;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: 5px;
	padding: 1px 7px;
	cursor: pointer;
	filter: none;
	transition: filter .15s ease;
}
input.tiny_red_btn    { background-color: #c62828; }
input.tiny_blue_btn   { background-color: #1a56b8; }
input.tiny_yellow_btn { background-color: #f2c200; }
input.tiny_green_btn  { background-color: #1e8e3e; }

.button_active, .button_inactive,
.button_hci_ready, .button_hci_active,
.button_hci_verify_confirm, .button_hci_verify_cancel {
	border-radius: var(--vici-radius-sm);
	padding: 8px 18px;
	font-weight: 600;
	transition: filter .15s ease;
}
.button_active, .button_hci_ready, .button_hci_active,
.button_hci_verify_confirm, .button_hci_verify_cancel {
	cursor: pointer;
}
.button_active:hover, .button_hci_ready:hover, .button_hci_active:hover,
.button_hci_verify_confirm:hover, .button_hci_verify_cancel:hover {
	filter: brightness(1.06);
}

/* ---------- Enlaces ---------- */
a { transition: color .15s ease, opacity .15s ease; }
a:hover { opacity: .82; }

/* ---------- Detalles varios ---------- */
.round_corners { border-radius: var(--vici-radius) !important; }
.sm_shadow     { box-shadow: var(--vici-shadow-sm) !important; }
div.shadowbox, div.shadowbox_1st, div.shadowbox_2nd,
div.shadowbox_3rd, div.shadowbox_4th {
	border: 1px solid rgba(0,0,0,.12);
	border-radius: var(--vici-radius);
	box-shadow: var(--vici-shadow);
	padding: 6px 12px;
}
img[src*="icon_"] { border-radius: 4px; }
hr {
	border: 0;
	border-top: 1px solid var(--vici-border);
}

/* Barras de scroll */
* {
	scrollbar-width: thin;
	scrollbar-color: #b7c2d0 transparent;
}
::-webkit-scrollbar { width: 10px; height: 10px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb {
	background-color: #b7c2d0;
	border-radius: 8px;
	border: 2px solid transparent;
	background-clip: content-box;
}
::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

/* No aplicar el fondo/sombra global al imprimir */
@media print {
	body { background: #ffffff; }
	body > center > table { box-shadow: none; }
}
