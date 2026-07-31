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
$MODrow1   = (isset($SSstd_row1_background)) ? $SSstd_row1_background : '9BB9FB';
$MODrow2   = (isset($SSstd_row2_background)) ? $SSstd_row2_background : 'B9CBFD';
$MODrow3   = (isset($SSstd_row3_background)) ? $SSstd_row3_background : '8EBCFD';
$MODrow4   = (isset($SSstd_row4_background)) ? $SSstd_row4_background : 'B6D3FC';

# Ancho de la barra lateral. El markup original la fija en 170px; con 226px
# los nombres largos del menu dejan de partirse en dos lineas.
$MODsidebar_w = 226;

# Mezcla un color del tema con blanco para obtener un tono plano y claro.
# Se emite el resultado calculado y, ademas, la version color-mix() para que el
# navegador use el valor exacto del tema si lo soporta.
function mod_tint($hex, $pct)
	{
	$hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
	if (strlen($hex) != 6) {return '#FFFFFF';}
	$out = '#';
	for ($i = 0; $i < 3; $i++)
		{
		$c = hexdec(substr($hex, $i * 2, 2));
		$out .= str_pad(dechex((int)round($c * $pct + 255 * (1 - $pct))), 2, '0', STR_PAD_LEFT);
		}
	return strtoupper($out);
	}

# Aclara ($f > 1) u oscurece ($f < 1) un color, para degradados y relieves.
function mod_shade($hex, $f)
	{
	$hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
	if (strlen($hex) != 6) {return '#000000';}
	$out = '#';
	for ($i = 0; $i < 3; $i++)
		{
		$c = (int)round(hexdec(substr($hex, $i * 2, 2)) * $f);
		if ($c > 255) {$c = 255;}
		if ($c < 0)   {$c = 0;}
		$out .= str_pad(dechex($c), 2, '0', STR_PAD_LEFT);
		}
	return strtoupper($out);
	}

# Reglas que reemplazan un color de fondo del tema por su version suavizada,
# tanto si viene por atributo BGCOLOR como por clase CSS.
function mod_soften($hex, $pct, $extra = '')
	{
	$hex  = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
	$tint = mod_tint($hex, $pct);
	$sel  = "tr[bgcolor=\"#$hex\" i], td[bgcolor=\"#$hex\" i], th[bgcolor=\"#$hex\" i], table[bgcolor=\"#$hex\" i]";
	$mix  = round($pct * 100);
	echo "$sel {\n";
	echo "\tbackground-color: $tint;\n";
	echo "\tbackground-color: color-mix(in srgb, #$hex {$mix}%, white);\n";
	if ($extra != '') {echo "\t$extra\n";}
	echo "}\n";
	}
?>

/* ============================================================
   VICIDIAL MODERN SKIN
   ============================================================ */

:root {
	--vici-menu:    #<?php echo $MODmenu; ?>;
	--vici-frame:   #<?php echo $MODframe; ?>;
	--vici-accent:  #<?php echo $MODmenu; ?>;
	/* Sin "system-ui": en Linux puede resolver a una fuente monoespaciada
	   segun la configuracion de fontconfig. Se listan familias concretas. */
	--vici-font:    -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans", "Open Sans", "Liberation Sans", "DejaVu Sans", Arial, Helvetica, sans-serif;
	--vici-mono:    "SF Mono", Menlo, Consolas, "Liberation Mono", "DejaVu Sans Mono", "Courier New", monospace;
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

/* ---------- Paleta suavizada ----------
   Los fondos del tema son muy saturados para pantallas grandes. Se sustituyen
   por tintes planos del MISMO color, manteniendo la identidad del esquema
   configurado en Admin -> System Settings pero con mucho menos ruido visual.
   Los colores alt_row (verdes) NO se tocan: indican estado. */
<?php
mod_soften($MODframe, 0.22);                                            /* fondo del area de contenido */
mod_soften($MODrow4,  0.16);                                            /* filas de formulario */
mod_soften($MODrow2,  0.16);                                            /* filas alternas */
mod_soften($MODrow3,  0.20);                                            /* filas destacadas */
mod_soften($MODrow1,  0.34, 'font-weight: 600;');                       /* cabeceras de tabla */
?>
/* Cabeceras de listado: separador inferior en el color del tema */
tr[bgcolor="#<?php echo $MODrow1; ?>" i] > td,
tr[bgcolor="#<?php echo $MODrow1; ?>" i] > th {
	border-bottom: 2px solid rgba(15,23,42,.14);
	padding-top: 5px;
	padding-bottom: 5px;
	color: #1e293b;
}
/* Las mismas equivalencias cuando el color llega por clase */
.std_row1 { background-color: <?php echo mod_tint($MODrow1, 0.34); ?>; }
.std_row2 { background-color: <?php echo mod_tint($MODrow2, 0.16); ?>; }
.std_row3 { background-color: <?php echo mod_tint($MODrow3, 0.20); ?>; }
.std_row4 { background-color: <?php echo mod_tint($MODrow4, 0.16); ?>; }

/* Filas de formulario: realce al pasar el raton */
tr[bgcolor="#<?php echo $MODrow4; ?>" i]:hover,
tr[bgcolor="#<?php echo $MODrow2; ?>" i]:hover {
	background-color: <?php echo mod_tint($MODrow4, 0.30); ?>;
}

/* Listados: fondo claro con separadores en vez de bloques de color */
.records_list_x { background-color: #ffffff; }
.records_list_y { background-color: <?php echo mod_tint($MODrow2, 0.10); ?>; }
.records_list_x > td, .records_list_y > td {
	border-bottom: 1px solid rgba(15,23,42,.07);
}

/* ---------- Barras de sub-navegacion ----------
   admin.php define estos colores fijos para las barras de seccion; los tonos
   puros (magenta, naranja, amarillo) se rebajan a pasteles. */
<?php
$MODsubbars = array('FF9933','FFFF99','FFCC99','FFCCCC','CC99FF','CCFFCC',
                    'CCFFFF','99FFCC','CCCCCC','FF99FF','99FF33','FF33FF');
foreach ($MODsubbars as $MODc) {mod_soften($MODc, 0.28);}
?>
tr[bgcolor="#FF33FF" i] > td, tr[bgcolor="#FF9933" i] > td,
tr[bgcolor="#99FF33" i] > td, tr[bgcolor="#FF99FF" i] > td,
tr[bgcolor="#FFFF99" i] > td, tr[bgcolor="#FFCC99" i] > td,
tr[bgcolor="#FFCCCC" i] > td, tr[bgcolor="#CC99FF" i] > td,
tr[bgcolor="#CCFFFF" i] > td, tr[bgcolor="#99FFCC" i] > td {
	padding: 5px 8px;
	border-bottom: 1px solid rgba(15,23,42,.10);
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

/* ============================================================
   LAYOUT: SHELL + HEADER + SIDEBAR
   ------------------------------------------------------------
   admin_header.php marca la estructura con #vici-shell (tabla
   contenedora), #vici-sidebar, #vici-main, .vici-nav, .vici-topbar y
   .vici-sidefoot. Aqui esa tabla de maquetacion se convierte en un grid
   de dos columnas: barra lateral fija a la izquierda y contenido fluido
   con cabecera pegada arriba.
   ============================================================ */

/* ---------- Shell ---------- */
html, body { height: 100%; }
body > center { display: block; min-height: 100%; }

#vici-shell {
	display: grid;
	grid-template-columns: <?php echo $MODsidebar_w; ?>px minmax(0, 1fr);
	/* La primera fila (barra lateral + contenido) se queda con todo el alto
	   sobrante para que ninguna columna termine a media pantalla; la segunda
	   (pie del sidebar) ocupa solo lo que necesita. */
	grid-template-rows: 1fr auto;
	min-height: 100vh;
	width: 100%;
	margin: 0;
	border-radius: 0;
	box-shadow: none;
	background: transparent;
}
/* Las filas desaparecen como caja para que las celdas sean items del grid:
   fila 1 -> [sidebar | contenido], fila 2 -> [pie del sidebar | vacio] */
#vici-shell > tbody,
#vici-shell > tbody > tr {
	display: contents;
}
#vici-shell > tbody > tr > td {
	display: block;
	width: auto;          /* anula los WIDTH=170 / WIDTH=870 del markup */
	vertical-align: top;
}

/* ---------- Barra lateral ---------- */
#vici-sidebar {
	background-color: var(--vici-menu);
	background-image: linear-gradient(180deg,
		<?php echo mod_shade($MODmenu, 1.22); ?> 0%,
		<?php echo mod_shade($MODmenu, 0.78); ?> 100%);
	padding: 0 0 8px 0;
	text-align: left;
	box-shadow: inset -1px 0 0 rgba(0,0,0,.20);
}
#vici-sidebar .vici-nav-sticky {
	position: sticky;
	top: 0;
	max-height: 100vh;
	overflow-y: auto;
	overscroll-behavior: contain;
	padding-bottom: 8px;
}
#vici-sidebar .vici-nav-sticky::-webkit-scrollbar-thumb {
	background-color: rgba(255,255,255,.28);
}
#vici-sidebar .vici-nav-sticky { scrollbar-color: rgba(255,255,255,.28) transparent; }

/* Marca */
#vici-sidebar .vici-brand {
	display: block;
	padding: 14px 14px 8px 14px;
	line-height: 0;
}
#vici-sidebar .vici-brand:hover { opacity: 1; }
#vici-sidebar .vici-brand img {
	max-width: 100%;
	height: auto;
	border-radius: var(--vici-radius-sm);
}
#vici-sidebar > .vici-nav-sticky > b {
	display: block;
	padding: 0 16px 12px 16px;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: .14em;
	color: rgba(255,255,255,.55);
}
#vici-sidebar > .vici-nav-sticky > b font { color: inherit !important; }
/* El <BR> tras el titulo sobra con el nuevo espaciado */
#vici-sidebar > .vici-nav-sticky > br { display: none; }

/* ---------- Navegacion ---------- */
.vici-nav {
	width: 100%;
	background: transparent;
	border-collapse: collapse;
}
.vici-nav > tbody > tr {
	background-color: transparent;
	transition: background-color .13s ease, box-shadow .13s ease;
}
.vici-nav td {
	padding: 4px 14px;
	border: 0;
}
/* Ojo: los enlaces NO deben ser display:block. El markup original los precede
   de un "&nbsp;" suelto, asi que un bloque los empuja a una segunda linea y
   duplica el alto de cada fila. */
.vici-nav a {
	text-decoration: none;
	transition: color .13s ease;
}
.vici-nav a:hover { opacity: 1; }
.vici-nav img { vertical-align: middle; margin-right: 2px; }

/* El markup trae font-size sin unidad ("font-size:11"), que el navegador
   descarta; se fijan tamanos coherentes. Los colores en linea (color:BLACK)
   solo se pueden reemplazar con !important. */
.vici-nav font {
	font-size: 12.5px;
	color: rgba(255,255,255,.72) !important;
}
.vici-nav tr[bgcolor] font,
.vici-nav tr[class^="head_style"] font {
	font-size: 13px;
	font-weight: 600;
	color: rgba(255,255,255,.95) !important;
}
.vici-nav tr:hover font { color: #ffffff !important; }

/* Estados */
.vici-nav > tbody > tr:hover {
	background-color: rgba(255,255,255,.09);
}
.vici-nav > tbody > tr[class$="_selected"] {
	background-color: rgba(255,255,255,.16);
	box-shadow: inset 3px 0 0 #ffffff;
}
.vici-nav > tbody > tr[class$="_selected"] font {
	color: #ffffff !important;
	font-weight: 600;
}
/* Sub-elementos: sangria y tipografia menor */
.vici-nav tr[class^="subhead"] td {
	padding-left: 22px;
}
.vici-nav tr[class^="subhead"] font { font-size: 12px; }

.horiz_line {
	height: 0;
	margin: 6px 0;
	border-bottom: 1px solid rgba(255,255,255,.11);
	font-size: 1px;
}
.horiz_line_grey {
	border-bottom: 1px solid rgba(148,163,184,.45);
}

/* Pie del sidebar (version / build) */
.vici-sidefoot {
	background-color: var(--vici-menu);
	background-image: linear-gradient(180deg,
		<?php echo mod_shade($MODmenu, 0.78); ?> 0%,
		<?php echo mod_shade($MODmenu, 0.66); ?> 100%);
	padding: 14px 12px 20px 12px;
	text-align: center;
}
.vici-sidefoot font {
	font-size: 10.5px !important;
	line-height: 1.6;
	color: rgba(255,255,255,.55) !important;
}
.vici-sidefoot a font, .vici-sidefoot font a { color: rgba(255,255,255,.7) !important; }
/* Solo los dos <br> de relleno que abren el bloque. Ojo: no vale "br + br",
   porque un <br> oculto sigue contando como hermano y se llevaria por delante
   el salto de linea que separa VERSION de BUILD. */
.vici-sidefoot font > br:first-child,
.vici-sidefoot font > br:first-child + br { display: none; }

/* ---------- Contenido ---------- */
#vici-main {
	background-color: <?php echo mod_tint($MODframe, 0.22); ?>;
	padding: 0 0 28px 0;
	min-width: 0;
}

/* ---------- Cabecera pegada ---------- */
/* La cabecera se fija sobre la TABLA, no sobre el <TR>: un elemento sticky
   solo puede desplazarse dentro de su bloque contenedor, y el de una fila es
   su propia tabla (40px de alto). Sobre la tabla, el bloque contenedor pasa a
   ser #vici-main, asi que acompana a todo el scroll de la pagina. */
#vici-topbar-table {
	position: sticky;
	top: 0;
	z-index: 30;
	width: 100%;
	height: auto;          /* anula el HEIGHT=15 del markup */
	background: transparent;
}
/* admin_header.php emite una fila sin celdas debajo de la cabecera; sin esto
   deja una banda vacia entre el header y el contenido. */
#vici-topbar-table > tbody > tr:not(:has(td)) {
	display: none;
}
tr.vici-topbar > td {
	padding: 9px 16px;
	background-color: var(--vici-menu);
	background-image: linear-gradient(180deg,
		<?php echo mod_shade($MODmenu, 1.14); ?> 0%,
		<?php echo mod_shade($MODmenu, 0.92); ?> 100%);
	box-shadow: 0 1px 0 rgba(0,0,0,.18), 0 2px 10px rgba(15,23,42,.10);
}
tr.vici-topbar font { font-size: 12px; }
tr.vici-topbar a {
	padding: 4px 9px;
	border-radius: 6px;
	text-decoration: none;
	transition: background-color .13s ease;
}
tr.vici-topbar a:hover {
	background-color: rgba(255,255,255,.16);
	opacity: 1;
}
/* Fecha a la derecha: menos peso visual que los enlaces */
tr.vici-topbar > td[align="right" i] font,
tr.vici-topbar > td[align="RIGHT"] font {
	font-size: 11.5px;
	font-weight: 500;
	color: rgba(255,255,255,.72);
}

/* Barra de sub-navegacion justo debajo de la cabecera */
#vici-topbar-table > tbody > tr + tr > td {
	padding: 6px 16px;
}

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
	#vici-shell { grid-template-columns: 1fr; }
	#vici-sidebar .vici-nav-sticky {
		position: static;
		max-height: none;
	}
	#vici-sidebar { box-shadow: none; }
}

/* No aplicar el fondo/sombra global al imprimir */
@media print {
	body { background: #ffffff; }
	body > center > table { box-shadow: none; }
	#vici-shell { grid-template-columns: 1fr; }
	#vici-sidebar, .vici-sidefoot { display: none; }
	tr.vici-topbar, tr.vici-topbar > td { position: static; }
}
