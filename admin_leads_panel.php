<?php
# admin_leads_panel.php
#
# Panel de Leads: clasifica los leads en cinco tipos de contacto y permite
# trabajarlos desde una sola pantalla.
#
#   - Contactado          se hablo con la persona
#   - No contactado       todavia no se logro hablar con nadie
#   - Agendado a futuro   hay un recontacto programado (vicidial_callbacks)
#   - Contacto erroneo    numero equivocado / desconectado / datos malos
#   - Preventa            lead ya calificado, listo para venderle
#
# El tipo NO es una columna nueva: se deduce del estado (disposition) del lead,
# de modo que el panel funciona con los datos que ya existen. La clasificacion
# usa una tabla de equivalencias fija mas las banderas de vicidial_statuses /
# vicidial_campaign_statuses, asi que las dispositions propias del cliente
# tambien caen en el grupo correcto.
#
# Los agendamientos se guardan como callbacks estandar de VICIDIAL, por lo que
# siguen apareciendo en la pantalla del agente. Ademas, mientras el panel esta
# abierto, avisa (aviso flotante + notificacion del navegador + sonido) cuando
# llega la hora de un recontacto.
#
# 260731 - First build
#

$admin_version = '2.14-1';
$build = '260731-1200';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

# Lee un parametro de POST o GET (POST tiene prioridad)
function lp_req($name, $default='')
	{
	if (isset($_POST[$name]))	{return $_POST[$name];}
	if (isset($_GET[$name]))	{return $_GET[$name];}
	return $default;
	}

# Escapa para SQL
function lp_esc($str)
	{
	global $link;
	return mysqli_real_escape_string($link, (string)$str);
	}

# Escapa para HTML
function lp_h($str)
	{
	return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
	}

# Verdadero si el valor traido de la base tiene contenido. Se usa en lugar de
# strlen() porque las columnas que admiten NULL disparan avisos en PHP 8.
function lp_hay($valor)
	{
	return (strlen((string)$valor) > 0);
	}

$DB =			lp_req('DB',0);
$action =		lp_req('action','');
$row_action =	lp_req('row_action','');
$bulk_tipo =	lp_req('bulk_tipo','');
$tipo =			lp_req('tipo','TODOS');
$campaign_id =	lp_req('campaign_id','');
$list_id =		lp_req('list_id','');
$estado =		lp_req('estado','');
$agente =		lp_req('agente','');
$buscar =		lp_req('buscar','');
$campo_fecha =	lp_req('campo_fecha','modify_date');
$desde =		lp_req('desde','');
$hasta =		lp_req('hasta','');
$orden =		lp_req('orden','');
$por_pagina =	lp_req('por_pagina','50');
$pagina =		lp_req('pagina','1');
$solo_mios =	lp_req('solo_mios','0');
$msg =			lp_req('msg','');
$msgtype =		lp_req('msgtype','ok');
$lead_ids =		(isset($_POST['lead_ids']) && is_array($_POST['lead_ids'])) ? $_POST['lead_ids'] : array();
$cb_lead_id =	lp_req('cb_lead_id','');
$cb_fecha =		lp_req('cb_fecha','');
$cb_hora =		lp_req('cb_hora','');
$cb_recipient =	lp_req('cb_recipient','ANYONE');
$cb_agente =	lp_req('cb_agente','');
$cb_comentarios = lp_req('cb_comentarios','');
$cancel_cb_id =	lp_req('cancel_cb_id','');

$DB = preg_replace("/[^0-9a-zA-Z]/","",$DB);
if (strlen($DB) < 1) {$DB=0;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,qc_features_active,user_territories_active,allow_web_debug,custom_fields_enabled FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$ss_conf_ct = mysqli_num_rows($rslt);
$non_latin=0;
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSenable_languages =			$row[2];
	$SSlanguage_method =			$row[3];
	$SSqc_features_active =			$row[4];
	$SSuser_territories_active =	$row[5];
	$SSallow_web_debug =			$row[6];
	$SScustom_fields_enabled =		$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

##### Limpieza de parametros #####
$tipo =			preg_replace('/[^_A-Z]/','',strtoupper($tipo));
$campaign_id =	preg_replace('/[^-_0-9a-zA-Z]/','',$campaign_id);
$list_id =		preg_replace('/[^0-9]/','',$list_id);
$estado =		preg_replace('/[^-_0-9a-zA-Z]/','',$estado);
$agente =		preg_replace('/[^-_0-9a-zA-Z]/','',$agente);
$campo_fecha =	preg_replace('/[^_a-z]/','',$campo_fecha);
$desde =		preg_replace('/[^-0-9]/','',$desde);
$hasta =		preg_replace('/[^-0-9]/','',$hasta);
$orden =		preg_replace('/[^_a-z]/','',$orden);
$por_pagina =	preg_replace('/[^0-9]/','',$por_pagina);
$pagina =		preg_replace('/[^0-9]/','',$pagina);
$solo_mios =	preg_replace('/[^0-9]/','',$solo_mios);
$action =		preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$row_action =	preg_replace('/[^-_:0-9a-zA-Z]/','',$row_action);
$bulk_tipo =	preg_replace('/[^_A-Z]/','',strtoupper($bulk_tipo));
$msgtype =		preg_replace('/[^a-z]/','',$msgtype);
$cb_lead_id =	preg_replace('/[^0-9]/','',$cb_lead_id);
$cb_fecha =		preg_replace('/[^-0-9]/','',$cb_fecha);
$cb_hora =		preg_replace('/[^:0-9]/','',$cb_hora);
$cb_recipient =	preg_replace('/[^A-Z]/','',strtoupper($cb_recipient));
$cb_agente =	preg_replace('/[^-_0-9a-zA-Z]/','',$cb_agente);
$cancel_cb_id =	preg_replace('/[^0-9]/','',$cancel_cb_id);
$buscar =			preg_replace("/\<|\>|\'|\"|\\\\|;/","",$buscar);
$cb_comentarios =	preg_replace("/\<|\>|\'|\"|\\\\|;/","",$cb_comentarios);
$msg =				preg_replace("/\<|\>|\'|\"|\\\\|;/","",$msg);

if (!in_array($campo_fecha, array('modify_date','entry_date','last_local_call_time'))) {$campo_fecha='modify_date';}
# Primera carga sin ningun parametro: se limita a los ultimos 30 dias para no
# barrer una vicidial_list entera. Basta con vaciar el campo Desde para ver todo.
if ( (count($_GET) < 1) and (count($_POST) < 1) )
	{$desde = date("Y-m-d", strtotime("-30 day"));}
if (!in_array($por_pagina, array('25','50','100','200'))) {$por_pagina='50';}
if ($pagina < 1) {$pagina=1;}
if (strlen($tipo) < 2) {$tipo='TODOS';}

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u','',$PHP_AUTH_PW);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$ip = getenv("REMOTE_ADDR");

$stmt="SELECT selected_language from vicidial_users where user='" . lp_esc($PHP_AUTH_USER) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if (mysqli_num_rows($rslt) > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =	$row[0];
	}

##### Autenticacion estandar de VICIDIAL #####
$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo "Su sesion expiro. <a href=\"admin.php\">Haga clic aqui para ingresar</a>.\n";
		exit;
		}
	}

if ($auth < 1)
	{
	$VDdisplayMESSAGE = "Ingreso incorrecto, intente nuevamente";
	if ($auth_message == 'LOCK')
		{
		Header ("Content-type: text/html; charset=utf-8");
		echo "Demasiados intentos de ingreso, pruebe de nuevo en 15 minutos: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ($auth_message == 'IPBLOCK')
		{
		Header ("Content-type: text/html; charset=utf-8");
		echo "Su direccion IP no esta permitida: $ip |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
	exit;
	}

$stmt="SELECT full_name,user_level,modify_leads,admin_hide_lead_data,admin_hide_phone_data,user_group,ignore_group_on_search from vicidial_users where user='" . lp_esc($PHP_AUTH_USER) . "';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =					$row[0];
$LOGuser_level =				$row[1];
$LOGmodify_leads =				$row[2];
$LOGadmin_hide_lead_data =		$row[3];
$LOGadmin_hide_phone_data =		$row[4];
$LOGuser_group =				$row[5];
$LOGignore_group_on_search =	$row[6];

# Ver el panel exige el mismo permiso que buscar leads
if ($LOGmodify_leads < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo "No tiene permisos para trabajar con leads\n";
	exit;
	}

$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='" . lp_esc($LOGuser_group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$LOGallowed_campaigns = '';
if (mysqli_num_rows($rslt) > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$LOGallowed_campaigns =	$row[0];
	}

##### Restriccion por grupo de usuario: listas y campanias visibles #####
$camp_lists='';
$LOGallowed_campaignsSQL='';
$restrict_lists=0;
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) and ($LOGignore_group_on_search != '1') )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('" . lp_esc($rawLOGallowed_campaignsSQL) . "')";
	$restrict_lists=1;

	$stmt="SELECT list_id from vicidial_lists where campaign_id IN('" . lp_esc($rawLOGallowed_campaignsSQL) . "');";
	$rslt=mysql_to_mysqli($stmt, $link);
	$lists_ct = mysqli_num_rows($rslt);
	$o=0;
	while ($lists_ct > $o)
		{
		$rowx=mysqli_fetch_row($rslt);
		$camp_lists .= "'$rowx[0]',";
		$o++;
		}
	$camp_lists = preg_replace('/.$/i','',$camp_lists);
	if (strlen($camp_lists)<2) {$camp_lists="''";}
	}


#############################################################################
##### CONFIGURACION DE LOS TIPOS DE CONTACTO
#############################################################################
#
# 'estado' es la disposition que se graba en vicidial_list cuando se marca un
# lead con ese tipo. Los estados propios del panel (CONTAC, NOCONT, ERRCON,
# PREVTA) no existen en una instalacion nueva: el aviso superior de la pantalla
# ofrece crearlos con un clic. CALLBK es un estado estandar de VICIDIAL.
#
# Si prefiere reutilizar dispositions que ya usa el call center, cambie aqui el
# valor de 'estado' y agregue los codigos que correspondan en $LP_ESTADOS_FIJOS.
#
$LP_TIPOS = array(
	'CONTACTADO' => array(
		'nombre' => 'Contactado',
		'ayuda'  => 'Se logro hablar con la persona',
		'color'  => '#2e6b4f',
		'estado' => 'CONTAC',
		'estado_nombre' => 'Contactado',
		'flags'  => array('human_answered'=>'Y','customer_contact'=>'Y'),
		),
	'NO_CONTACTADO' => array(
		'nombre' => 'No contactado',
		'ayuda'  => 'Todavia no se hablo con nadie',
		'color'  => '#6b7280',
		'estado' => 'NOCONT',
		'estado_nombre' => 'No Contactado',
		'flags'  => array(),
		),
	'AGENDADO' => array(
		'nombre' => 'Agendado a futuro',
		'ayuda'  => 'Con recontacto programado, avisa al llegar la hora',
		'color'  => '#8a6234',
		'estado' => 'CALLBK',
		'estado_nombre' => 'Call Back',
		'flags'  => array('human_answered'=>'Y','customer_contact'=>'Y','scheduled_callback'=>'Y'),
		),
	'ERRONEO' => array(
		'nombre' => 'Contacto erroneo',
		'ayuda'  => 'Numero equivocado, desconectado o datos malos',
		'color'  => '#9c3a3a',
		'estado' => 'ERRCON',
		'estado_nombre' => 'Contacto Erroneo',
		'flags'  => array('unworkable'=>'Y'),
		),
	'PREVENTA' => array(
		'nombre' => 'Preventa',
		'ayuda'  => 'Lead calificado, listo para venderle',
		'color'  => '#015b91',
		'estado' => 'PREVTA',
		'estado_nombre' => 'Preventa',
		'flags'  => array('human_answered'=>'Y','customer_contact'=>'Y'),
		),
	);

# Equivalencias fijas estado -> tipo. Mandan sobre las reglas automaticas.
$LP_ESTADOS_FIJOS = array(
	'PREVTA' => 'PREVENTA',
	'CONTAC' => 'CONTACTADO',
	'NOCONT' => 'NO_CONTACTADO',
	'ERRCON' => 'ERRONEO',
	'CALLBK' => 'AGENDADO',
	'CBHOLD' => 'AGENDADO',
	'WN'     => 'ERRONEO',
	'DC'     => 'ERRONEO',
	'ADC'    => 'ERRONEO',
	'NEW'    => 'NO_CONTACTADO',
	);

# Minutos de anticipacion con los que se avisa un agendamiento
$LP_AVISO_MINUTOS = 60;

##### Mapa estado -> tipo, armado con vicidial_statuses + los estados de campania
$LP_ESTADO_TIPO = array();		# status => TIPO
$LP_ESTADO_NOMBRE = array();	# status => nombre legible
$LP_ESTADOS_EXISTENTES = array();

$status_queries = array(
	"SELECT status,status_name,human_answered,customer_contact,sale,scheduled_callback,unworkable from vicidial_statuses;",
	"SELECT status,status_name,human_answered,customer_contact,sale,scheduled_callback,unworkable from vicidial_campaign_statuses group by status;"
	);
$sq=0;
while ($sq < count($status_queries))
	{
	$rslt=mysql_to_mysqli($status_queries[$sq], $link);
	$st_ct = mysqli_num_rows($rslt);
	$o=0;
	while ($st_ct > $o)
		{
		$rowS=mysqli_fetch_row($rslt);
		$st = $rowS[0];
		if ($sq == 0) {$LP_ESTADOS_EXISTENTES[$st] = 1;}
		if (isset($LP_ESTADO_TIPO[$st])) {$o++; continue;}		# vicidial_statuses manda

		$LP_ESTADO_NOMBRE[$st] = (lp_hay($rowS[1])) ? $rowS[1] : $st;

		if (isset($LP_ESTADOS_FIJOS[$st]))
			{$LP_ESTADO_TIPO[$st] = $LP_ESTADOS_FIJOS[$st];}
		elseif ($rowS[5] == 'Y')					# scheduled_callback
			{$LP_ESTADO_TIPO[$st] = 'AGENDADO';}
		elseif ( ($rowS[6] == 'Y') and ($rowS[2] != 'Y') )	# unworkable y no atendido por humano
			{$LP_ESTADO_TIPO[$st] = 'ERRONEO';}
		elseif ( ($rowS[2] == 'Y') or ($rowS[3] == 'Y') or ($rowS[4] == 'Y') )
			{$LP_ESTADO_TIPO[$st] = 'CONTACTADO';}
		else
			{$LP_ESTADO_TIPO[$st] = 'NO_CONTACTADO';}
		$o++;
		}
	$sq++;
	}

# Los estados fijos que todavia no existen en la base igual se clasifican
foreach ($LP_ESTADOS_FIJOS as $st => $tp)
	{
	if (!isset($LP_ESTADO_TIPO[$st])) {$LP_ESTADO_TIPO[$st] = $tp;}
	if (!isset($LP_ESTADO_NOMBRE[$st])) {$LP_ESTADO_NOMBRE[$st] = $st;}
	}

# Estados agrupados por tipo, para armar el SQL
$LP_ESTADOS_POR_TIPO = array();
foreach ($LP_TIPOS as $tp => $cfg) {$LP_ESTADOS_POR_TIPO[$tp] = array();}
foreach ($LP_ESTADO_TIPO as $st => $tp)
	{
	if (isset($LP_ESTADOS_POR_TIPO[$tp])) {$LP_ESTADOS_POR_TIPO[$tp][] = $st;}
	}

# Estados del panel que faltan crear en vicidial_statuses
$LP_ESTADOS_FALTANTES = array();
foreach ($LP_TIPOS as $tp => $cfg)
	{
	if (!isset($LP_ESTADOS_EXISTENTES[$cfg['estado']])) {$LP_ESTADOS_FALTANTES[$tp] = $cfg['estado'];}
	}

# Devuelve la condicion SQL que aisla un tipo. "No contactado" se define por
# descarte, asi cualquier disposition desconocida cae ahi y ningun lead se pierde.
function lp_sql_tipo($tp)
	{
	global $LP_ESTADOS_POR_TIPO;
	if ($tp == 'NO_CONTACTADO')
		{
		$otros = array();
		foreach ($LP_ESTADOS_POR_TIPO as $t => $sts)
			{
			if ($t == 'NO_CONTACTADO') {continue;}
			foreach ($sts as $s) {$otros[] = $s;}
			}
		if (count($otros) < 1) {return "1=1";}
		return "vl.status NOT IN('" . implode("','", array_map('lp_esc', $otros)) . "')";
		}
	if ( (!isset($LP_ESTADOS_POR_TIPO[$tp])) or (count($LP_ESTADOS_POR_TIPO[$tp]) < 1) ) {return "1=0";}
	return "vl.status IN('" . implode("','", array_map('lp_esc', $LP_ESTADOS_POR_TIPO[$tp])) . "')";
	}

# Tipo al que pertenece un estado
function lp_tipo_de($st)
	{
	global $LP_ESTADO_TIPO;
	if (isset($LP_ESTADO_TIPO[$st])) {return $LP_ESTADO_TIPO[$st];}
	return 'NO_CONTACTADO';
	}

# Registro en el log de administracion
function lp_log($event_type, $record_id, $event_code, $sql, $notes)
	{
	global $link, $NOW_TIME, $PHP_AUTH_USER, $ip;
	$stmt="INSERT INTO vicidial_admin_log SET event_date='" . lp_esc($NOW_TIME) . "', user='" . lp_esc($PHP_AUTH_USER) . "', ip_address='" . lp_esc($ip) . "', event_section='LEADS', event_type='" . lp_esc($event_type) . "', record_id='" . lp_esc($record_id) . "', event_code='" . lp_esc($event_code) . "', event_sql=\"" . lp_esc($sql) . "\", event_notes='" . lp_esc($notes) . "';";
	mysql_to_mysqli($stmt, $link);
	}

# Campania y lista de un lead, respetando las listas permitidas al usuario
function lp_datos_lead($lead_id)
	{
	global $link, $restrict_lists, $camp_lists;
	$restrictSQL = ($restrict_lists > 0) ? " and vl.list_id IN($camp_lists)" : "";
	$stmt="SELECT vl.lead_id,vl.list_id,vl.status,vl.first_name,vl.last_name,vl.phone_number,vll.campaign_id from vicidial_list vl LEFT JOIN vicidial_lists vll ON vll.list_id=vl.list_id where vl.lead_id='" . lp_esc($lead_id) . "'$restrictSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if (mysqli_num_rows($rslt) < 1) {return false;}
	return mysqli_fetch_assoc($rslt);
	}

# Vuelve a la misma vista conservando filtros (patron POST-Redirect-GET)
function lp_redirect($mensaje, $tipo_mensaje='ok')
	{
	global $PHP_SELF, $tipo, $campaign_id, $list_id, $estado, $agente, $buscar;
	global $campo_fecha, $desde, $hasta, $orden, $por_pagina, $pagina, $solo_mios;
	$qs = array(
		'tipo'=>$tipo, 'campaign_id'=>$campaign_id, 'list_id'=>$list_id,
		'estado'=>$estado, 'agente'=>$agente, 'buscar'=>$buscar,
		'campo_fecha'=>$campo_fecha, 'desde'=>$desde, 'hasta'=>$hasta,
		'orden'=>$orden, 'por_pagina'=>$por_pagina, 'pagina'=>$pagina,
		'solo_mios'=>$solo_mios, 'msg'=>$mensaje, 'msgtype'=>$tipo_mensaje
		);
	header("Location: $PHP_SELF?" . http_build_query($qs));
	exit;
	}


#############################################################################
##### ACCIONES
#############################################################################

##### Crear los estados propios del panel #####
if ($row_action == 'CREAR_ESTADOS')
	{
	$creados = 0;
	foreach ($LP_ESTADOS_FALTANTES as $tp => $st)
		{
		$cfg = $LP_TIPOS[$tp];
		$f = $cfg['flags'];
		$human =	(isset($f['human_answered'])) ? 'Y' : 'N';
		$contact =	(isset($f['customer_contact'])) ? 'Y' : 'N';
		$callback =	(isset($f['scheduled_callback'])) ? 'Y' : 'N';
		$unwork =	(isset($f['unworkable'])) ? 'Y' : 'N';
		$stmt="INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,min_sec,max_sec,answering_machine) values('" . lp_esc($st) . "','" . lp_esc($cfg['estado_nombre']) . "','Y','$human','UNDEFINED','N','N','$contact','N','$unwork','$callback','N','0','30000','N');";
		if ($DB) {echo "|$stmt|\n";}
		mysql_to_mysqli($stmt, $link);
		lp_log('ADD', $st, 'PANEL LEADS ADD STATUS', $stmt, "tipo: $tp");
		$creados++;
		}
	if ($creados > 0) {lp_redirect("Se crearon $creados estados para el panel");}
	lp_redirect("No habia estados para crear", 'aviso');
	}

##### Cancelar un agendamiento #####
if ( ($row_action == 'CANCELAR_CB') and (strlen($cancel_cb_id) > 0) )
	{
	$stmt="SELECT lead_id from vicidial_callbacks where callback_id='" . lp_esc($cancel_cb_id) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if (mysqli_num_rows($rslt) < 1) {lp_redirect("El agendamiento ya no existe", 'error');}
	$rowC=mysqli_fetch_row($rslt);
	if (lp_datos_lead($rowC[0]) === false) {lp_redirect("No tiene acceso a ese lead", 'error');}

	$stmt="UPDATE vicidial_callbacks SET status='INACTIVE' where callback_id='" . lp_esc($cancel_cb_id) . "';";
	if ($DB) {echo "|$stmt|\n";}
	mysql_to_mysqli($stmt, $link);
	lp_log('MODIFY', $rowC[0], 'PANEL LEADS CANCEL CALLBACK', $stmt, "callback_id: $cancel_cb_id");
	lp_redirect("Agendamiento cancelado");
	}

##### Agendar (o reagendar) un recontacto #####
if ($row_action == 'AGENDAR')
	{
	if (strlen($cb_lead_id) < 1) {lp_redirect("Falta el lead a agendar", 'error');}
	$lead = lp_datos_lead($cb_lead_id);
	if ($lead === false) {lp_redirect("No tiene acceso a ese lead", 'error');}

	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $cb_fecha)) {lp_redirect("Fecha de agendamiento invalida", 'error');}
	if (!preg_match('/^\d{1,2}:\d{2}$/', $cb_hora)) {$cb_hora = '10:00';}
	$partes = explode(':', $cb_hora);
	$cb_datetime = sprintf("%s %02d:%02d:00", $cb_fecha, (int)$partes[0], (int)$partes[1]);
	if (strtotime($cb_datetime) === false) {lp_redirect("Fecha y hora invalidas", 'error');}

	$cb_estado = $LP_TIPOS['AGENDADO']['estado'];
	$destinatario = ($cb_recipient == 'USERONLY') ? 'USERONLY' : 'ANYONE';
	$usuario_cb = ($destinatario == 'USERONLY' && strlen($cb_agente) > 0) ? $cb_agente : $PHP_AUTH_USER;

	# Un solo agendamiento activo por lead: los anteriores quedan inactivos
	$stmt="UPDATE vicidial_callbacks SET status='INACTIVE' where lead_id='" . lp_esc($cb_lead_id) . "' and status IN('ACTIVE','LIVE');";
	if ($DB) {echo "|$stmt|\n";}
	mysql_to_mysqli($stmt, $link);

	$stmt="INSERT INTO vicidial_callbacks SET lead_id='" . lp_esc($cb_lead_id) . "', list_id='" . lp_esc($lead['list_id']) . "', campaign_id='" . lp_esc($lead['campaign_id']) . "', status='ACTIVE', entry_time='" . lp_esc($NOW_TIME) . "', callback_time='" . lp_esc($cb_datetime) . "', user='" . lp_esc($usuario_cb) . "', recipient='$destinatario', comments='" . lp_esc($cb_comentarios) . "', user_group='" . lp_esc($LOGuser_group) . "', lead_status='" . lp_esc($cb_estado) . "';";
	if ($DB) {echo "|$stmt|\n";}
	mysql_to_mysqli($stmt, $link);

	$stmtU="UPDATE vicidial_list SET status='" . lp_esc($cb_estado) . "' where lead_id='" . lp_esc($cb_lead_id) . "';";
	if ($DB) {echo "|$stmtU|\n";}
	mysql_to_mysqli($stmtU, $link);

	lp_log('ADD', $cb_lead_id, 'PANEL LEADS SCHEDULE CALLBACK', $stmt, "para: $cb_datetime ($destinatario $usuario_cb)");
	lp_redirect("Lead $cb_lead_id agendado para " . date("d/m/Y H:i", strtotime($cb_datetime)));
	}

##### Cambiar el tipo de uno o varios leads #####
$aplicar_tipo = '';
$aplicar_leads = array();
if (preg_match('/^[A-Z_]+:[0-9]+$/', $row_action))
	{
	$partes = explode(':', $row_action);
	$aplicar_tipo = $partes[0];
	$aplicar_leads = array($partes[1]);
	}
elseif ( (strlen($bulk_tipo) > 1) and (count($lead_ids) > 0) )
	{
	$aplicar_tipo = $bulk_tipo;
	foreach ($lead_ids as $lid)
		{
		$lid = preg_replace('/[^0-9]/','',$lid);
		if (strlen($lid) > 0) {$aplicar_leads[] = $lid;}
		}
	}

if ( (strlen($aplicar_tipo) > 1) and (count($aplicar_leads) > 0) )
	{
	if (!isset($LP_TIPOS[$aplicar_tipo])) {lp_redirect("Tipo de contacto desconocido", 'error');}
	if ($aplicar_tipo == 'AGENDADO') {lp_redirect("Para agendar use el boton Agendar, que pide fecha y hora", 'aviso');}

	$nuevo_estado = $LP_TIPOS[$aplicar_tipo]['estado'];
	if (!isset($LP_ESTADOS_EXISTENTES[$nuevo_estado]))
		{lp_redirect("Falta crear el estado $nuevo_estado. Use el boton \"Crear estados del panel\"", 'error');}

	$ok = 0;
	$sin_acceso = 0;
	foreach ($aplicar_leads as $lid)
		{
		$lead = lp_datos_lead($lid);
		if ($lead === false) {$sin_acceso++; continue;}

		$stmt="UPDATE vicidial_list SET status='" . lp_esc($nuevo_estado) . "' where lead_id='" . lp_esc($lid) . "';";
		if ($DB) {echo "|$stmt|\n";}
		mysql_to_mysqli($stmt, $link);

		# Al salir de "agendado" no tiene sentido dejar el recontacto activo
		if (lp_tipo_de($lead['status']) == 'AGENDADO')
			{
			$stmtC="UPDATE vicidial_callbacks SET status='INACTIVE' where lead_id='" . lp_esc($lid) . "' and status IN('ACTIVE','LIVE');";
			mysql_to_mysqli($stmtC, $link);
			}

		lp_log('MODIFY', $lid, 'PANEL LEADS SET TYPE', $stmt, "de $lead[status] a $nuevo_estado ($aplicar_tipo)");
		$ok++;
		}

	$texto = "$ok lead(s) marcados como " . $LP_TIPOS[$aplicar_tipo]['nombre'];
	if ($sin_acceso > 0) {$texto .= " ($sin_acceso sin acceso)";}
	lp_redirect($texto, ($ok > 0) ? 'ok' : 'error');
	}


#############################################################################
##### AVISOS DE AGENDAMIENTOS (usado por la pantalla y por el sondeo AJAX)
#############################################################################

function lp_avisos($limite=50)
	{
	global $link, $restrict_lists, $camp_lists, $solo_mios, $PHP_AUTH_USER, $LP_AVISO_MINUTOS;
	global $LOGadmin_hide_lead_data, $LOGadmin_hide_phone_data;

	$restrictSQL = ($restrict_lists > 0) ? " and vc.list_id IN($camp_lists)" : "";
	$miosSQL = ($solo_mios > 0) ? " and vc.user='" . lp_esc($PHP_AUTH_USER) . "'" : "";
	$limite = (int)$limite;

	# Los minutos que faltan se calculan en la base y no en PHP: asi el aviso no
	# depende de que el reloj de MySQL y el de PHP tengan la misma zona horaria.
	$stmt="SELECT vc.callback_id,vc.lead_id,vc.callback_time,vc.comments,vc.user,vc.recipient,vc.campaign_id,vc.list_id,vl.first_name,vl.last_name,vl.phone_number,vl.status,TIMESTAMPDIFF(MINUTE,NOW(),vc.callback_time) as minutos from vicidial_callbacks vc LEFT JOIN vicidial_list vl ON vl.lead_id=vc.lead_id where vc.status IN('ACTIVE','LIVE') and vc.callback_time <= DATE_ADD(NOW(), INTERVAL $LP_AVISO_MINUTOS MINUTE)$restrictSQL$miosSQL order by vc.callback_time asc limit $limite;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ct = mysqli_num_rows($rslt);
	$items = array();
	$vencidos = 0;
	$o=0;
	while ($ct > $o)
		{
		$r = mysqli_fetch_assoc($rslt);
		$falta = (int)$r['minutos'];
		if ($falta <= 0) {$vencidos++;}
		$nombre = trim($r['first_name'] . ' ' . $r['last_name']);
		if (strlen($nombre) < 1) {$nombre = 'Lead ' . $r['lead_id'];}
		if ($LOGadmin_hide_lead_data > 0) {$nombre = 'Lead ' . $r['lead_id'];}
		$telefono = ($LOGadmin_hide_phone_data > 0) ? 'XXXXXXXXXX' : $r['phone_number'];
		$items[] = array(
			'callback_id' => $r['callback_id'],
			'lead_id'     => $r['lead_id'],
			'nombre'      => $nombre,
			'telefono'    => $telefono,
			'campania'    => $r['campaign_id'],
			'hora'        => $r['callback_time'],
			'minutos'     => $falta,
			'vencido'     => ($falta <= 0) ? 1 : 0,
			'usuario'     => $r['user'],
			'destinatario'=> $r['recipient'],
			'comentarios' => $r['comments']
			);
		$o++;
		}
	# La hora que se muestra tambien sale de la base, por el mismo motivo
	$rsltN = mysql_to_mysqli("SELECT NOW();", $link);
	$rowN = mysqli_fetch_row($rsltN);

	return array('vencidos'=>$vencidos, 'items'=>$items, 'ahora'=>$rowN[0]);
	}

##### Sondeo AJAX: devuelve los avisos en JSON #####
if ($action == 'NOTIF_JSON')
	{
	$avisos = lp_avisos(50);
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode(array(
		'ahora'    => $NOW_TIME,
		'ventana'  => $LP_AVISO_MINUTOS,
		'vencidos' => $avisos['vencidos'],
		'total'    => count($avisos['items']),
		'items'    => $avisos['items']
		));
	exit;
	}


#############################################################################
##### CONSULTAS DE LA PANTALLA
#############################################################################

##### Filtros comunes (sin el tipo, para poder contar todos los tipos) #####
$wc = array();
if ($restrict_lists > 0) {$wc[] = "vl.list_id IN($camp_lists)";}
if (strlen($campaign_id) > 0) {$wc[] = "vll.campaign_id='" . lp_esc($campaign_id) . "'";}
if (strlen($list_id) > 0) {$wc[] = "vl.list_id='" . lp_esc($list_id) . "'";}
if (strlen($estado) > 0) {$wc[] = "vl.status='" . lp_esc($estado) . "'";}
if (strlen($agente) > 0) {$wc[] = "vl.user='" . lp_esc($agente) . "'";}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {$wc[] = "vl.$campo_fecha >= '" . lp_esc($desde) . " 00:00:00'";}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {$wc[] = "vl.$campo_fecha <= '" . lp_esc($hasta) . " 23:59:59'";}
if (strlen($buscar) > 0)
	{
	$b = lp_esc($buscar);
	$partes_busq = array(
		"vl.phone_number LIKE '%$b%'",
		"vl.alt_phone LIKE '%$b%'",
		"vl.first_name LIKE '%$b%'",
		"vl.last_name LIKE '%$b%'",
		"vl.email LIKE '%$b%'",
		"vl.vendor_lead_code LIKE '%$b%'"
		);
	if (preg_match('/^\d+$/', $buscar)) {$partes_busq[] = "vl.lead_id='$b'";}
	$wc[] = "(" . implode(' or ', $partes_busq) . ")";
	}
$whereSQL = (count($wc) > 0) ? "where " . implode(' and ', $wc) : "";

##### Contadores por tipo #####
$LP_CONTEO = array('TODOS'=>0);
foreach ($LP_TIPOS as $tp => $cfg) {$LP_CONTEO[$tp] = 0;}

$stmt="SELECT vl.status, count(*) from vicidial_list vl LEFT JOIN vicidial_lists vll ON vll.list_id=vl.list_id $whereSQL group by vl.status;";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$ct = mysqli_num_rows($rslt);
$o=0;
while ($ct > $o)
	{
	$rowT=mysqli_fetch_row($rslt);
	$tp = lp_tipo_de($rowT[0]);
	$LP_CONTEO[$tp] += $rowT[1];
	$LP_CONTEO['TODOS'] += $rowT[1];
	if (!isset($LP_ESTADO_NOMBRE[$rowT[0]])) {$LP_ESTADO_NOMBRE[$rowT[0]] = $rowT[0];}
	$o++;
	}

##### Listado #####
$whereTipoSQL = $whereSQL;
if ( ($tipo != 'TODOS') and (isset($LP_TIPOS[$tipo])) )
	{
	$cond = lp_sql_tipo($tipo);
	$whereTipoSQL = (strlen($whereSQL) > 0) ? "$whereSQL and $cond" : "where $cond";
	}

# Orden por defecto: en la pestania de agendados, el recontacto mas proximo
if (strlen($orden) < 2) {$orden = ($tipo == 'AGENDADO') ? 'agenda' : 'modificado';}
$ordenSQL = "vl.modify_date desc";
if ($orden == 'modificado_asc')	{$ordenSQL = "vl.modify_date asc";}
if ($orden == 'ingreso')		{$ordenSQL = "vl.entry_date desc";}
if ($orden == 'llamada')		{$ordenSQL = "vl.last_local_call_time desc";}
if ($orden == 'intentos')		{$ordenSQL = "vl.called_count desc";}
if ($orden == 'nombre')			{$ordenSQL = "vl.last_name asc, vl.first_name asc";}
if ($orden == 'agenda')			{$ordenSQL = "(SELECT MIN(c.callback_time) from vicidial_callbacks c where c.lead_id=vl.lead_id and c.status IN('ACTIVE','LIVE')) asc, vl.modify_date desc";}

$total_filas = (isset($LP_TIPOS[$tipo])) ? $LP_CONTEO[$tipo] : $LP_CONTEO['TODOS'];
$total_paginas = ($total_filas > 0) ? ceil($total_filas / $por_pagina) : 1;
if ($pagina > $total_paginas) {$pagina = $total_paginas;}
$offset = ($pagina - 1) * $por_pagina;

$stmt="SELECT vl.lead_id,vl.status,vl.first_name,vl.last_name,vl.phone_number,vl.alt_phone,vl.email,vl.city,vl.state,vl.comments,vl.called_count,vl.last_local_call_time,vl.entry_date,vl.modify_date,vl.list_id,vl.user,vl.vendor_lead_code,vll.list_name,vll.campaign_id from vicidial_list vl LEFT JOIN vicidial_lists vll ON vll.list_id=vl.list_id $whereTipoSQL order by $ordenSQL limit $offset,$por_pagina;";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$leads_ct = mysqli_num_rows($rslt);
$LEADS = array();
$ids_pagina = array();
$o=0;
while ($leads_ct > $o)
	{
	$r = mysqli_fetch_assoc($rslt);
	$LEADS[] = $r;
	$ids_pagina[] = "'" . lp_esc($r['lead_id']) . "'";
	$o++;
	}

##### Agendamientos activos de los leads que se muestran #####
$CB_POR_LEAD = array();
if (count($ids_pagina) > 0)
	{
	$stmt="SELECT callback_id,lead_id,callback_time,comments,user,recipient,TIMESTAMPDIFF(MINUTE,NOW(),callback_time) as minutos from vicidial_callbacks where lead_id IN(" . implode(',', $ids_pagina) . ") and status IN('ACTIVE','LIVE') order by callback_time asc;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$ct = mysqli_num_rows($rslt);
	$o=0;
	while ($ct > $o)
		{
		$r = mysqli_fetch_assoc($rslt);
		if (!isset($CB_POR_LEAD[$r['lead_id']])) {$CB_POR_LEAD[$r['lead_id']] = $r;}
		$o++;
		}
	}

##### Avisos para la cabecera #####
$AVISOS = lp_avisos(20);

##### Campanias y listas para los filtros #####
$CAMPANIAS = array();
$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns where campaign_id IS NOT NULL $LOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
$ct = mysqli_num_rows($rslt);
$o=0;
while ($ct > $o) {$CAMPANIAS[] = mysqli_fetch_row($rslt); $o++;}

$LISTAS = array();
$listasWHERE = ($restrict_lists > 0) ? "where list_id IN($camp_lists)" : "";
if (strlen($campaign_id) > 0)
	{
	$listasWHERE = (strlen($listasWHERE) > 0) ? "$listasWHERE and campaign_id='" . lp_esc($campaign_id) . "'" : "where campaign_id='" . lp_esc($campaign_id) . "'";
	}
$stmt="SELECT list_id,list_name,campaign_id from vicidial_lists $listasWHERE order by list_id limit 500;";
$rslt=mysql_to_mysqli($stmt, $link);
$ct = mysqli_num_rows($rslt);
$o=0;
while ($ct > $o) {$LISTAS[] = mysqli_fetch_row($rslt); $o++;}

##### Enlace que conserva los filtros y cambia lo que se le pase #####
function lp_url($cambios=array())
	{
	global $PHP_SELF, $tipo, $campaign_id, $list_id, $estado, $agente, $buscar;
	global $campo_fecha, $desde, $hasta, $orden, $por_pagina, $pagina, $solo_mios;
	$qs = array(
		'tipo'=>$tipo, 'campaign_id'=>$campaign_id, 'list_id'=>$list_id,
		'estado'=>$estado, 'agente'=>$agente, 'buscar'=>$buscar,
		'campo_fecha'=>$campo_fecha, 'desde'=>$desde, 'hasta'=>$hasta,
		'orden'=>$orden, 'por_pagina'=>$por_pagina, 'pagina'=>$pagina,
		'solo_mios'=>$solo_mios
		);
	foreach ($cambios as $k => $v) {$qs[$k] = $v;}
	return $PHP_SELF . '?' . http_build_query($qs);
	}

?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title>
ADMINISTRACION: Panel de Leads
<?php
##### BEGIN Set variables to make header show properly #####
$ADD =					'100';
$hh =					'lists';
$sh =					'panel';
$LOGast_admin_access =	'1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width =			'100%';
$section_width =		'750';
$header_font_size =		'3';
$subheader_font_size =	'2';
$subcamp_font_size =	'2';
$header_selected_bold =	'<b>';
$header_nonselected_bold = '';
$lists_color =		'#E6E6E6';
$lists_font =		'BLACK';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");
?>

<style type="text/css">
/* Panel de Leads.
   Todo lleva prefijo lp- para no pisar estilos del admin. Los colores, radios y
   sombras salen de las variables del skin (vicidial_modern.php) y caen en el
   valor de reserva si el skin no esta activo, para que el panel se vea igual
   que el resto de la administracion. */
.lp-wrap {
	padding: 14px 16px 34px 16px;
	font-family: inherit;
	font-size: 13px;
	color: #1f2937;
	}
.lp-wrap * { box-sizing: border-box; }
.lp-wrap input, .lp-wrap select, .lp-wrap textarea, .lp-wrap button {
	font-family: inherit;
	font-size: 12.5px;
	}

/* Encabezado */
.lp-top { display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;
	padding-bottom: 10px; margin-bottom: 14px;
	border-bottom: 1px solid var(--vici-border, #d3dbe6); }
.lp-title { font-size: 17px; font-weight: 700; margin: 0; }
.lp-sub { font-size: 12px; color: #64748b; margin: 3px 0 0 0; }
.lp-top-right { margin-left: auto; display: flex; gap: 8px; align-items: center; }

/* Bloques */
.lp-card { background: #fff;
	border: 1px solid var(--vici-border, #d3dbe6);
	border-radius: var(--vici-radius-sm, 6px);
	padding: 12px 14px; margin-bottom: 14px; }
.lp-card h2 { font-size: 11px; font-weight: 700; margin: 0 0 10px 0; color: #64748b;
	text-transform: uppercase; letter-spacing: .06em; }

/* Mensajes */
.lp-msg { padding: 9px 12px; border-radius: var(--vici-radius-sm, 6px);
	margin-bottom: 14px; border: 1px solid var(--vici-border, #d3dbe6); background: #fff; }
.lp-msg-ok    { border-left: 3px solid #2e6b4f; }
.lp-msg-error { border-left: 3px solid #9c3a3a; }
.lp-msg-aviso { border-left: 3px solid #8a6234; }

/* Recordatorios */
.lp-alertas { border-left: 3px solid var(--vici-menu, #015B91); }
.lp-alertas.lp-vencidos { border-left-color: #9c3a3a; }
.lp-alerta-row { display: flex; align-items: baseline; gap: 10px; padding: 6px 0;
	border-top: 1px solid rgba(15,23,42,.06); flex-wrap: wrap; }
.lp-alerta-row:first-child { border-top: 0; }
.lp-alerta-hora { font-weight: 600; min-width: 92px; font-variant-numeric: tabular-nums; }
.lp-alerta-nom { font-weight: 600; }
.lp-alerta-tel { font-variant-numeric: tabular-nums; }
.lp-alerta-acc { margin-left: auto; display: flex; gap: 6px; }
.lp-alerta-com { color: #64748b; }
.lp-cuenta { font-size: 11px; font-weight: 400; color: #64748b;
	text-transform: none; letter-spacing: 0; margin-left: 8px; }

/* Pestanias de tipo */
.lp-tabs { display: flex; flex-wrap: wrap; margin-bottom: 14px;
	border-bottom: 1px solid var(--vici-border, #d3dbe6); }
.lp-tab { text-decoration: none; padding: 7px 14px; color: #475569;
	font-size: 12.5px; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.lp-tab:hover { color: #1f2937; opacity: 1; }
.lp-tab .lp-n { color: #94a3b8; margin-left: 5px; font-variant-numeric: tabular-nums; }
.lp-tab-on { color: #1f2937; font-weight: 600; border-bottom-color: var(--vici-menu, #015B91); }
.lp-tab-on .lp-n { color: #64748b; }

/* Filtros */
.lp-filtros { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.lp-campo { display: flex; flex-direction: column; gap: 3px; }
.lp-campo label { font-size: 11px; color: #64748b; }
.lp-campo input, .lp-campo select { padding: 4px 7px;
	border: 1px solid var(--vici-border, #d3dbe6);
	border-radius: var(--vici-radius-sm, 6px); min-width: 128px; background: #fff; }
.lp-campo-ancho input { min-width: 220px; }

/* Botones */
.lp-btn { display: inline-block; cursor: pointer; font-weight: 600;
	padding: 5px 11px; border-radius: var(--vici-radius-sm, 6px);
	border: 1px solid var(--vici-border, #d3dbe6); background: #fff; color: #334155;
	text-decoration: none; }
.lp-btn:hover { background: #f1f5f9; opacity: 1; }
.lp-btn-primario { background: var(--vici-menu, #015B91); border-color: rgba(0,0,0,.14); color: #fff; }
.lp-btn-primario:hover { background: var(--vici-menu, #015B91); filter: brightness(1.1); }
.lp-btn-mini { padding: 2px 8px; font-size: 11px; font-weight: 400; }

/* Barra de acciones */
.lp-bulk { display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
	padding: 8px 0; }
.lp-bulk-info { color: #64748b; }

/* Tabla */
.lp-tabla-cont { overflow-x: auto; border: 1px solid var(--vici-border, #d3dbe6);
	border-radius: var(--vici-radius-sm, 6px); background: #fff; }
table.lp-tabla { width: 100%; border-collapse: collapse; font-size: 12.5px; }
table.lp-tabla th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b;
	background: #f6f8fb; padding: 8px 10px; white-space: nowrap;
	border-bottom: 1px solid var(--vici-border, #d3dbe6); }
table.lp-tabla td { padding: 7px 10px; vertical-align: top;
	border-bottom: 1px solid rgba(15,23,42,.06); }
table.lp-tabla tr:last-child td { border-bottom: 0; }
table.lp-tabla tbody tr:hover td { background: #f8fafc; }
.lp-nom { font-weight: 600; }
.lp-tel { font-variant-numeric: tabular-nums; white-space: nowrap; }
.lp-muted { color: #64748b; font-size: 11.5px; }
.lp-nowrap { white-space: nowrap; }

/* Etiqueta de tipo: chip plano con borde, sin relleno saturado */
.lp-badge { display: inline-block; padding: 1px 7px; border-radius: 3px;
	font-size: 11px; font-weight: 600; white-space: nowrap;
	border: 1px solid currentColor; background: #fff; }
.lp-badge-suave { color: #475569; border-color: var(--vici-border, #d3dbe6);
	background: #f6f8fb; font-weight: 400; font-variant-numeric: tabular-nums; }

/* Acciones de fila */
.lp-acc { display: flex; gap: 6px; align-items: center; white-space: nowrap; }
.lp-acc select { padding: 2px 5px; font-size: 11.5px;
	border: 1px solid var(--vici-border, #d3dbe6); border-radius: var(--vici-radius-sm, 6px); }

/* Paginacion */
.lp-pag { display: flex; align-items: center; gap: 6px; margin-top: 12px; flex-wrap: wrap; }
.lp-pag a, .lp-pag span.lp-pag-on { padding: 4px 9px; border-radius: var(--vici-radius-sm, 6px);
	border: 1px solid var(--vici-border, #d3dbe6); background: #fff;
	text-decoration: none; color: #334155; }
.lp-pag a:hover { background: #f1f5f9; opacity: 1; }
.lp-pag .lp-pag-on { background: #f1f5f9; font-weight: 600; }

/* Ventana de agendamiento */
.lp-modal-fondo { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.35);
	z-index: 900; align-items: center; justify-content: center; padding: 20px; }
.lp-modal-fondo.lp-abierto { display: flex; }
.lp-modal { background: #fff; border: 1px solid var(--vici-border, #d3dbe6);
	border-radius: var(--vici-radius, 8px); width: 430px; max-width: 100%;
	box-shadow: var(--vici-shadow, 0 4px 14px rgba(15,23,42,.18)); padding: 16px; }
.lp-modal h3 { margin: 0 0 3px 0; font-size: 14px; }
.lp-modal-lead { color: #64748b; font-size: 12px; margin-bottom: 12px; }
.lp-modal-fila { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 9px; }
.lp-modal label { display: block; font-size: 11px; color: #64748b; margin-bottom: 2px; }
.lp-modal input[type=text], .lp-modal input[type=date], .lp-modal input[type=time],
.lp-modal select, .lp-modal textarea { width: 100%; padding: 4px 7px;
	border: 1px solid var(--vici-border, #d3dbe6); border-radius: var(--vici-radius-sm, 6px); }
.lp-modal-acc { display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px; }
.lp-rapidos { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }

/* Avisos flotantes */
.lp-toasts { position: fixed; right: 16px; bottom: 16px; z-index: 950;
	display: flex; flex-direction: column; gap: 8px; max-width: 320px; }
.lp-toast { background: #fff; border: 1px solid var(--vici-border, #d3dbe6);
	border-left: 3px solid var(--vici-menu, #015B91);
	border-radius: var(--vici-radius-sm, 6px);
	box-shadow: var(--vici-shadow, 0 4px 14px rgba(15,23,42,.18)); padding: 10px 12px; }
.lp-toast-venc { border-left-color: #9c3a3a; }
.lp-toast-tit { font-weight: 600; margin-bottom: 2px; }
.lp-toast-cerrar { float: right; cursor: pointer; color: #94a3b8; }

@media (max-width: 900px) {
	.lp-campo-ancho input { min-width: 160px; }
	.lp-top-right { margin-left: 0; }
}
</style>

<div class="lp-wrap">

<div class="lp-top">
	<div>
		<h1 class="lp-title">Panel de Leads</h1>
		<p class="lp-sub">Contactado &middot; No contactado &middot; Agendado a futuro &middot; Contacto erroneo &middot; Preventa</p>
	</div>
	<div class="lp-top-right">
		<span id="lp-estado-avisos" class="lp-muted">Avisos cada 60 s</span>
		<button type="button" class="lp-btn" id="lp-btn-permiso" onclick="lpPedirPermiso();">Activar avisos del navegador</button>
		<a class="lp-btn" href="<?php echo lp_h(lp_url(array('pagina'=>1))); ?>">Refrescar</a>
	</div>
</div>

<?php
if (strlen($msg) > 0)
	{
	$clase = 'lp-msg-ok';
	if ($msgtype == 'error') {$clase = 'lp-msg-error';}
	if ($msgtype == 'aviso') {$clase = 'lp-msg-aviso';}
	echo "<div class=\"lp-msg $clase\">" . lp_h($msg) . "</div>\n";
	}

##### Aviso de configuracion: faltan los estados propios del panel #####
if (count($LP_ESTADOS_FALTANTES) > 0)
	{
	$faltan = array();
	foreach ($LP_ESTADOS_FALTANTES as $tp => $st) {$faltan[] = $st . ' (' . $LP_TIPOS[$tp]['nombre'] . ')';}
	?>
	<div class="lp-msg lp-msg-aviso">
		Para poder marcar tipos hacen falta estos estados en VICIDIAL:
		<?php echo lp_h(implode(', ', $faltan)); ?>.
		<form method="POST" action="<?php echo lp_h($PHP_SELF); ?>" style="display:inline; margin-left:8px;">
			<input type="hidden" name="tipo" value="<?php echo lp_h($tipo); ?>">
			<button type="submit" name="row_action" value="CREAR_ESTADOS" class="lp-btn lp-btn-primario">Crear estados del panel</button>
		</form>
	</div>
	<?php
	}

##### Recordatorios de agendamientos #####
$clase_alertas = ($AVISOS['vencidos'] > 0) ? 'lp-card lp-alertas lp-vencidos' : 'lp-card lp-alertas';
?>
<div class="<?php echo $clase_alertas; ?>" id="lp-panel-avisos">
	<h2>
		Recontactos de ahora
		<span class="lp-cuenta lp-cuenta-venc" id="lp-c-venc"><?php echo (int)$AVISOS['vencidos']; ?> vencidos</span>
		<span class="lp-cuenta lp-cuenta-prox" id="lp-c-prox"><?php echo count($AVISOS['items']) - (int)$AVISOS['vencidos']; ?> en la proxima hora</span>
	</h2>
	<div id="lp-lista-avisos">
	<?php
	if (count($AVISOS['items']) < 1)
		{
		echo "<div class=\"lp-muted\">No hay recontactos pendientes en los proximos $LP_AVISO_MINUTOS minutos.</div>\n";
		}
	else
		{
		foreach ($AVISOS['items'] as $av)
			{
			$cuando = ($av['vencido']) ? 'vencido hace ' . abs($av['minutos']) . ' min' : 'en ' . $av['minutos'] . ' min';
			?>
			<div class="lp-alerta-row">
				<span class="lp-alerta-hora"><?php echo lp_h(date("d/m H:i", strtotime($av['hora']))); ?></span>
				<span class="lp-badge" style="color:<?php echo ($av['vencido'] ? '#9c3a3a' : '#8a6234'); ?>"><?php echo lp_h($cuando); ?></span>
				<span class="lp-alerta-nom"><?php echo lp_h($av['nombre']); ?></span>
				<span class="lp-alerta-tel"><?php echo lp_h($av['telefono']); ?></span>
				<?php if (lp_hay($av["comentarios"])) { ?>
					<span class="lp-alerta-com"><?php echo lp_h($av['comentarios']); ?></span>
				<?php } ?>
				<span class="lp-muted"><?php echo lp_h($av['campania']); ?> &middot; <?php echo lp_h($av['usuario']); ?></span>
				<span class="lp-alerta-acc">
					<a class="lp-btn lp-btn-mini" target="_blank" href="admin_modify_lead.php?lead_id=<?php echo lp_h($av['lead_id']); ?>">Ver lead</a>
					<a class="lp-btn lp-btn-mini" href="<?php echo lp_h(lp_url(array('buscar'=>$av['lead_id'], 'tipo'=>'TODOS', 'pagina'=>1))); ?>">Buscar</a>
				</span>
			</div>
			<?php
			}
		}
	?>
	</div>
</div>

<?php ##### Pestanias por tipo ##### ?>
<div class="lp-tabs">
	<a class="lp-tab<?php if ($tipo=='TODOS') {echo ' lp-tab-on';} ?>"
	   href="<?php echo lp_h(lp_url(array('tipo'=>'TODOS','pagina'=>1,'orden'=>''))); ?>">Todos
		<span class="lp-n"><?php echo (int)$LP_CONTEO['TODOS']; ?></span>
	</a>
	<?php
	foreach ($LP_TIPOS as $tp => $cfg)
		{
		$on = ($tipo == $tp);
		?>
		<a class="lp-tab<?php if ($on) {echo ' lp-tab-on';} ?>"
		   title="<?php echo lp_h($cfg['ayuda']); ?>"
		   href="<?php echo lp_h(lp_url(array('tipo'=>$tp,'pagina'=>1,'orden'=>''))); ?>"><?php echo lp_h($cfg['nombre']); ?>
			<span class="lp-n"><?php echo (int)$LP_CONTEO[$tp]; ?></span>
		</a>
		<?php
		}
	?>
</div>

<?php ##### Filtros ##### ?>
<div class="lp-card">
	<h2>Filtros</h2>
	<form method="GET" action="<?php echo lp_h($PHP_SELF); ?>">
	<input type="hidden" name="tipo" value="<?php echo lp_h($tipo); ?>">
	<input type="hidden" name="pagina" value="1">
	<div class="lp-filtros">
		<div class="lp-campo lp-campo-ancho">
			<label>Buscar</label>
			<input type="text" name="buscar" value="<?php echo lp_h($buscar); ?>" placeholder="telefono, nombre, email, ID de lead...">
		</div>
		<div class="lp-campo">
			<label>Campania</label>
			<select name="campaign_id">
				<option value="">-- todas --</option>
				<?php
				foreach ($CAMPANIAS as $c)
					{
					$sel = ($campaign_id == $c[0]) ? ' selected' : '';
					echo "<option value=\"" . lp_h($c[0]) . "\"$sel>" . lp_h($c[0]) . " - " . lp_h($c[1]) . "</option>\n";
					}
				?>
			</select>
		</div>
		<div class="lp-campo">
			<label>Lista</label>
			<select name="list_id">
				<option value="">-- todas --</option>
				<?php
				foreach ($LISTAS as $l)
					{
					$sel = ($list_id == $l[0]) ? ' selected' : '';
					echo "<option value=\"" . lp_h($l[0]) . "\"$sel>" . lp_h($l[0]) . " - " . lp_h($l[1]) . "</option>\n";
					}
				?>
			</select>
		</div>
		<div class="lp-campo">
			<label>Estado exacto</label>
			<select name="estado">
				<option value="">-- todos --</option>
				<?php
				$estados_orden = $LP_ESTADO_NOMBRE;
				ksort($estados_orden);
				foreach ($estados_orden as $st => $nom)
					{
					$sel = ($estado == $st) ? ' selected' : '';
					echo "<option value=\"" . lp_h($st) . "\"$sel>" . lp_h($st) . " - " . lp_h($nom) . "</option>\n";
					}
				?>
			</select>
		</div>
		<div class="lp-campo">
			<label>Agente</label>
			<input type="text" name="agente" value="<?php echo lp_h($agente); ?>" style="min-width:110px" placeholder="usuario">
		</div>
		<div class="lp-campo">
			<label>Fecha por</label>
			<select name="campo_fecha">
				<option value="modify_date"<?php if ($campo_fecha=='modify_date') {echo ' selected';} ?>>Modificacion</option>
				<option value="entry_date"<?php if ($campo_fecha=='entry_date') {echo ' selected';} ?>>Ingreso</option>
				<option value="last_local_call_time"<?php if ($campo_fecha=='last_local_call_time') {echo ' selected';} ?>>Ultima llamada</option>
			</select>
		</div>
		<div class="lp-campo">
			<label>Desde</label>
			<input type="date" name="desde" value="<?php echo lp_h($desde); ?>" style="min-width:120px">
		</div>
		<div class="lp-campo">
			<label>Hasta</label>
			<input type="date" name="hasta" value="<?php echo lp_h($hasta); ?>" style="min-width:120px">
		</div>
		<div class="lp-campo">
			<label>Orden</label>
			<select name="orden">
				<option value="modificado"<?php if ($orden=='modificado') {echo ' selected';} ?>>Modificado (nuevo primero)</option>
				<option value="modificado_asc"<?php if ($orden=='modificado_asc') {echo ' selected';} ?>>Modificado (viejo primero)</option>
				<option value="agenda"<?php if ($orden=='agenda') {echo ' selected';} ?>>Agendamiento mas proximo</option>
				<option value="ingreso"<?php if ($orden=='ingreso') {echo ' selected';} ?>>Fecha de ingreso</option>
				<option value="llamada"<?php if ($orden=='llamada') {echo ' selected';} ?>>Ultima llamada</option>
				<option value="intentos"<?php if ($orden=='intentos') {echo ' selected';} ?>>Cantidad de intentos</option>
				<option value="nombre"<?php if ($orden=='nombre') {echo ' selected';} ?>>Apellido</option>
			</select>
		</div>
		<div class="lp-campo">
			<label>Por pagina</label>
			<select name="por_pagina">
				<?php
				foreach (array('25','50','100','200') as $pp)
					{
					$sel = ($por_pagina == $pp) ? ' selected' : '';
					echo "<option value=\"$pp\"$sel>$pp</option>\n";
					}
				?>
			</select>
		</div>
		<div class="lp-campo">
			<label>Avisos</label>
			<select name="solo_mios">
				<option value="0"<?php if ($solo_mios < 1) {echo ' selected';} ?>>De todos</option>
				<option value="1"<?php if ($solo_mios > 0) {echo ' selected';} ?>>Solo mios</option>
			</select>
		</div>
		<div class="lp-campo">
			<label>&nbsp;</label>
			<div style="display:flex; gap:6px;">
				<button type="submit" class="lp-btn lp-btn-primario">Filtrar</button>
				<a class="lp-btn" href="<?php echo lp_h($PHP_SELF); ?>">Limpiar</a>
			</div>
		</div>
	</div>
	</form>
</div>

<?php ##### Listado + acciones (todo dentro de un solo formulario) ##### ?>
<form method="POST" action="<?php echo lp_h($PHP_SELF); ?>" id="lp-form">
<?php
# Los filtros viajan con el POST para volver a la misma vista
$ocultos = array('tipo'=>$tipo,'campaign_id'=>$campaign_id,'list_id'=>$list_id,'estado'=>$estado,
	'agente'=>$agente,'buscar'=>$buscar,'campo_fecha'=>$campo_fecha,'desde'=>$desde,'hasta'=>$hasta,
	'orden'=>$orden,'por_pagina'=>$por_pagina,'pagina'=>$pagina,'solo_mios'=>$solo_mios);
foreach ($ocultos as $k => $v)
	{echo "<input type=\"hidden\" name=\"" . lp_h($k) . "\" value=\"" . lp_h($v) . "\">\n";}
?>
<?php # Lo llena el desplegable de cada fila. Va antes que los botones que tambien
      # se llaman row_action, para que el valor del boton pulsado sea el ultimo. ?>
<input type="hidden" name="row_action" id="lp-row-action" value="">

<div class="lp-bulk">
	<span class="lp-bulk-info"><span id="lp-sel-count">0</span> seleccionados</span>
	<span class="lp-muted">Marcar como:</span>
	<?php
	foreach ($LP_TIPOS as $tp => $cfg)
		{
		if ($tp == 'AGENDADO') {continue;}		# agendar necesita fecha y hora
		?>
		<button type="submit" name="bulk_tipo" value="<?php echo lp_h($tp); ?>" class="lp-btn"
			onclick="return lpConfirmarBulk('<?php echo lp_h($cfg['nombre']); ?>');"><?php echo lp_h($cfg['nombre']); ?></button>
		<?php
		}
	?>
	<span class="lp-muted" style="margin-left:auto">
		<?php echo (int)$total_filas; ?> lead(s) &middot; pagina <?php echo (int)$pagina; ?> de <?php echo (int)$total_paginas; ?>
	</span>
</div>

<div class="lp-tabla-cont">
<table class="lp-tabla">
<tr>
	<th style="width:28px"><input type="checkbox" id="lp-todos" onclick="lpMarcarTodos(this);"></th>
	<th>Lead</th>
	<th>Nombre</th>
	<th>Telefono</th>
	<th>Lista / Campania</th>
	<th>Estado</th>
	<th>Tipo</th>
	<th>Agendado</th>
	<th class="lp-nowrap">Int.</th>
	<th>Ultima llamada</th>
	<th>Acciones</th>
</tr>
<?php
if (count($LEADS) < 1)
	{
	echo "<tr><td colspan=11 style=\"padding:26px; text-align:center; color:#64748b\">No hay leads con estos filtros.</td></tr>\n";
	}
foreach ($LEADS as $L)
	{
	$tp = lp_tipo_de($L['status']);
	$cfg = $LP_TIPOS[$tp];
	$nombre = trim($L['first_name'] . ' ' . $L['last_name']);
	if (strlen($nombre) < 1) {$nombre = '(sin nombre)';}
	if ($LOGadmin_hide_lead_data > 0) {$nombre = '(oculto)';}
	$telefono = ($LOGadmin_hide_phone_data > 0) ? 'XXXXXXXXXX' : $L['phone_number'];
	$estado_nom = (isset($LP_ESTADO_NOMBRE[$L['status']])) ? $LP_ESTADO_NOMBRE[$L['status']] : $L['status'];
	$cb = (isset($CB_POR_LEAD[$L['lead_id']])) ? $CB_POR_LEAD[$L['lead_id']] : false;
	?>
	<tr>
		<td><input type="checkbox" class="lp-chk" name="lead_ids[]" value="<?php echo lp_h($L['lead_id']); ?>" onclick="lpContar();"></td>
		<td class="lp-nowrap">
			<a href="admin_modify_lead.php?lead_id=<?php echo lp_h($L['lead_id']); ?>" target="_blank"><?php echo lp_h($L['lead_id']); ?></a>
		</td>
		<td>
			<div class="lp-nom"><?php echo lp_h($nombre); ?></div>
			<?php if ( lp_hay($L["city"]) or lp_hay($L["state"]) ) { ?>
				<div class="lp-muted"><?php echo lp_h(trim($L['city'] . ' ' . $L['state'])); ?></div>
			<?php } ?>
		</td>
		<td class="lp-tel">
			<?php echo lp_h($telefono); ?>
			<?php if ( (lp_hay($L["alt_phone"])) and ($LOGadmin_hide_phone_data < 1) ) { ?>
				<div class="lp-muted"><?php echo lp_h($L['alt_phone']); ?></div>
			<?php } ?>
		</td>
		<td>
			<div><?php echo lp_h($L['list_id']); ?> - <?php echo lp_h($L['list_name']); ?></div>
			<div class="lp-muted"><?php echo lp_h($L['campaign_id']); ?></div>
		</td>
		<td class="lp-nowrap">
			<span class="lp-badge lp-badge-suave"><?php echo lp_h($L['status']); ?></span>
			<div class="lp-muted"><?php echo lp_h($estado_nom); ?></div>
		</td>
		<td>
			<span class="lp-badge" style="background:<?php echo lp_h($cfg['color']); ?>"><?php echo lp_h($cfg['nombre']); ?></span>
		</td>
		<td class="lp-nowrap">
			<?php
			if ($cb !== false)
				{
				$falta = (int)$cb['minutos'];
				$color_cb = ($falta <= 0) ? '#c62828' : '#b45309';
				echo "<div style=\"font-weight:600; color:$color_cb\">" . lp_h(date("d/m/Y H:i", strtotime($cb['callback_time']))) . "</div>\n";
				echo "<div class=\"lp-muted\">" . lp_h($cb['recipient'] == 'USERONLY' ? 'solo ' . $cb['user'] : 'cualquier agente') . "</div>\n";
				if (lp_hay($cb["comments"]))
					{echo "<div class=\"lp-muted\">" . lp_h($cb['comments']) . "</div>\n";}
				?>
				<button type="submit" name="row_action" value="CANCELAR_CB" class="lp-btn lp-btn-mini"
					onclick="document.getElementById('lp-cancel-id').value='<?php echo lp_h($cb['callback_id']); ?>'; return confirm('Cancelar el agendamiento?');">Cancelar</button>
				<?php
				}
			else
				{
				echo "<span class=\"lp-muted\">-</span>";
				}
			?>
		</td>
		<td><?php echo (int)$L['called_count']; ?></td>
		<td class="lp-nowrap lp-muted">
			<?php
			if ( (lp_hay($L["last_local_call_time"])) and ($L['last_local_call_time'] != '0000-00-00 00:00:00') )
				{echo lp_h(date("d/m/Y H:i", strtotime($L['last_local_call_time'])));}
			else
				{echo '-';}
			?>
			<?php if (lp_hay($L["user"])) { ?><div><?php echo lp_h($L['user']); ?></div><?php } ?>
		</td>
		<td>
			<div class="lp-acc">
				<select onchange="lpMarcarFila(this);" title="Cambiar el tipo de este lead">
					<option value="">Marcar como...</option>
					<?php
					foreach ($LP_TIPOS as $tp2 => $cfg2)
						{
						if ($tp2 == 'AGENDADO') {continue;}		# agendar necesita fecha y hora
						if ($tp2 == $tp) {continue;}			# ya esta en ese tipo
						echo "<option value=\"$tp2:" . lp_h($L['lead_id']) . "\">" . lp_h($cfg2['nombre']) . "</option>\n";
						}
					?>
				</select>
				<button type="button" class="lp-btn lp-btn-mini"
					onclick="lpAbrirAgenda('<?php echo lp_h($L['lead_id']); ?>','<?php echo lp_h(addslashes($nombre)); ?>','<?php echo lp_h($telefono); ?>');">Agendar</button>
			</div>
		</td>
	</tr>
	<?php
	}
?>
</table>
</div>

<?php ##### Paginacion ##### ?>
<div class="lp-pag">
	<?php
	if ($pagina > 1)
		{
		echo "<a href=\"" . lp_h(lp_url(array('pagina'=>1))) . "\">&laquo; Primera</a>\n";
		echo "<a href=\"" . lp_h(lp_url(array('pagina'=>($pagina-1)))) . "\">&lsaquo; Anterior</a>\n";
		}
	$ini = $pagina - 2;   if ($ini < 1) {$ini = 1;}
	$fin = $ini + 4;      if ($fin > $total_paginas) {$fin = $total_paginas;}
	for ($p = $ini; $p <= $fin; $p++)
		{
		if ($p == $pagina) {echo "<span class=\"lp-pag-on\">$p</span>\n";}
		else {echo "<a href=\"" . lp_h(lp_url(array('pagina'=>$p))) . "\">$p</a>\n";}
		}
	if ($pagina < $total_paginas)
		{
		echo "<a href=\"" . lp_h(lp_url(array('pagina'=>($pagina+1)))) . "\">Siguiente &rsaquo;</a>\n";
		echo "<a href=\"" . lp_h(lp_url(array('pagina'=>$total_paginas))) . "\">Ultima &raquo;</a>\n";
		}
	?>
	<span class="lp-muted" style="border:0; background:none">
		<?php echo (int)$total_filas; ?> lead(s) en el filtro actual
	</span>
</div>

<?php ##### Modal de agendamiento (dentro del formulario para que viaje todo junto) ##### ?>
<input type="hidden" name="cb_lead_id" id="lp-cb-lead" value="">
<input type="hidden" name="cancel_cb_id" id="lp-cancel-id" value="">

<div class="lp-modal-fondo" id="lp-modal">
	<div class="lp-modal">
		<h3>Agendar recontacto</h3>
		<div class="lp-modal-lead" id="lp-modal-lead"></div>

		<div class="lp-rapidos">
			<button type="button" class="lp-btn lp-btn-mini" onclick="lpRapido(60);">En 1 hora</button>
			<button type="button" class="lp-btn lp-btn-mini" onclick="lpRapido(180);">En 3 horas</button>
			<button type="button" class="lp-btn lp-btn-mini" onclick="lpManana(10);">Manana 10:00</button>
			<button type="button" class="lp-btn lp-btn-mini" onclick="lpEnDias(2,10);">En 2 dias</button>
			<button type="button" class="lp-btn lp-btn-mini" onclick="lpEnDias(7,10);">En 1 semana</button>
		</div>

		<div class="lp-modal-fila">
			<div style="flex:1">
				<label class="lp-muted">Fecha</label>
				<input type="date" name="cb_fecha" id="lp-cb-fecha" value="<?php echo lp_h(date("Y-m-d", strtotime("+1 day"))); ?>">
			</div>
			<div style="flex:1">
				<label class="lp-muted">Hora</label>
				<input type="time" name="cb_hora" id="lp-cb-hora" value="10:00">
			</div>
		</div>
		<div class="lp-modal-fila">
			<div style="flex:1">
				<label class="lp-muted">Quien lo atiende</label>
				<select name="cb_recipient" id="lp-cb-recipient" onchange="lpRecipient();">
					<option value="ANYONE">Cualquier agente</option>
					<option value="USERONLY">Solo un agente</option>
				</select>
			</div>
			<div style="flex:1; display:none" id="lp-cb-agente-box">
				<label class="lp-muted">Agente</label>
				<input type="text" name="cb_agente" id="lp-cb-agente" value="<?php echo lp_h($PHP_AUTH_USER); ?>">
			</div>
		</div>
		<div>
			<label class="lp-muted">Comentario</label>
			<textarea name="cb_comentarios" rows="2" placeholder="Motivo del recontacto, que se acordo, etc."></textarea>
		</div>

		<div class="lp-modal-acc">
			<button type="button" class="lp-btn" onclick="lpCerrarAgenda();">Cancelar</button>
			<button type="submit" name="row_action" value="AGENDAR" class="lp-btn lp-btn-primario">Agendar y avisar</button>
		</div>
	</div>
</div>

</form>
</div><!-- /lp-wrap -->

<div class="lp-toasts" id="lp-toasts"></div>

<script type="text/javascript">
/* ---------- Seleccion multiple ---------- */
function lpMarcarTodos(chk)
	{
	var cajas = document.querySelectorAll('.lp-chk');
	for (var i=0; i<cajas.length; i++) {cajas[i].checked = chk.checked;}
	lpContar();
	}
function lpContar()
	{
	var n = document.querySelectorAll('.lp-chk:checked').length;
	document.getElementById('lp-sel-count').innerHTML = n;
	}
/* Cambio de tipo desde el desplegable de una fila */
function lpMarcarFila(sel)
	{
	if (!sel.value) {return;}
	var texto = sel.options[sel.selectedIndex].text;
	if (!confirm('Marcar este lead como "' + texto + '"?')) {sel.value = ''; return;}
	document.getElementById('lp-row-action').value = sel.value;
	document.getElementById('lp-form').submit();
	}
function lpConfirmarBulk(nombre)
	{
	var n = document.querySelectorAll('.lp-chk:checked').length;
	if (n < 1) {alert('Seleccione al menos un lead.'); return false;}
	return confirm('Marcar ' + n + ' lead(s) como "' + nombre + '"?');
	}

/* ---------- Modal de agendamiento ---------- */
function lpAbrirAgenda(leadId, nombre, telefono)
	{
	document.getElementById('lp-cb-lead').value = leadId;
	document.getElementById('lp-modal-lead').innerHTML = 'Lead ' + leadId + ' &middot; ' + nombre + ' &middot; ' + telefono;
	document.getElementById('lp-modal').classList.add('lp-abierto');
	}
function lpCerrarAgenda()
	{
	document.getElementById('lp-cb-lead').value = '';
	document.getElementById('lp-modal').classList.remove('lp-abierto');
	}
function lpRecipient()
	{
	var v = document.getElementById('lp-cb-recipient').value;
	document.getElementById('lp-cb-agente-box').style.display = (v == 'USERONLY') ? 'block' : 'none';
	}
function lpPonerFecha(d)
	{
	var mes = ('0' + (d.getMonth()+1)).slice(-2);
	var dia = ('0' + d.getDate()).slice(-2);
	var hh  = ('0' + d.getHours()).slice(-2);
	var mm  = ('0' + d.getMinutes()).slice(-2);
	document.getElementById('lp-cb-fecha').value = d.getFullYear() + '-' + mes + '-' + dia;
	document.getElementById('lp-cb-hora').value = hh + ':' + mm;
	}
function lpRapido(minutos)
	{
	var d = new Date();
	d.setMinutes(d.getMinutes() + minutos);
	lpPonerFecha(d);
	}
function lpManana(hora)
	{
	var d = new Date();
	d.setDate(d.getDate() + 1);
	d.setHours(hora, 0, 0, 0);
	lpPonerFecha(d);
	}
function lpEnDias(dias, hora)
	{
	var d = new Date();
	d.setDate(d.getDate() + dias);
	d.setHours(hora, 0, 0, 0);
	lpPonerFecha(d);
	}

/* ---------- Avisos de recontacto ----------
   Se consulta el mismo archivo con action=NOTIF_JSON. Cada agendamiento avisa
   una sola vez por sesion (se recuerda el callback_id en sessionStorage). */
var lpAvisados = {};
var lpURLavisos = '<?php echo lp_h($PHP_SELF); ?>?action=NOTIF_JSON&solo_mios=<?php echo (int)$solo_mios; ?>';

try {
	var guardado = sessionStorage.getItem('lp_avisados_<?php echo lp_h($PHP_AUTH_USER); ?>');
	if (guardado) {lpAvisados = JSON.parse(guardado);}
} catch(e) {}

function lpGuardarAvisados()
	{
	try {sessionStorage.setItem('lp_avisados_<?php echo lp_h($PHP_AUTH_USER); ?>', JSON.stringify(lpAvisados));} catch(e) {}
	}

function lpPedirPermiso()
	{
	if (!('Notification' in window)) {alert('Este navegador no soporta notificaciones de escritorio.'); return;}
	Notification.requestPermission().then(function(p)
		{
		var b = document.getElementById('lp-btn-permiso');
		if (p == 'granted') {b.innerHTML = 'Avisos del navegador activados'; b.disabled = true;}
		});
	}

function lpBeep()
	{
	try {
		var ctx = new (window.AudioContext || window.webkitAudioContext)();
		var osc = ctx.createOscillator();
		var gan = ctx.createGain();
		osc.connect(gan); gan.connect(ctx.destination);
		osc.type = 'sine'; osc.frequency.value = 880;
		gan.gain.setValueAtTime(0.0001, ctx.currentTime);
		gan.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.02);
		gan.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.5);
		osc.start(); osc.stop(ctx.currentTime + 0.55);
	} catch(e) {}
	}

function lpToast(item)
	{
	var cont = document.getElementById('lp-toasts');
	var div = document.createElement('div');
	div.className = 'lp-toast' + (item.vencido ? ' lp-toast-venc' : '');
	var cuando = item.vencido ? 'Vencido hace ' + Math.abs(item.minutos) + ' min' : 'En ' + item.minutos + ' min';
	div.innerHTML = '<span class="lp-toast-cerrar" onclick="this.parentNode.remove();">&times;</span>' +
		'<div class="lp-toast-tit">Recontacto: ' + lpEscape(item.nombre) + '</div>' +
		'<div class="lp-muted">' + cuando + ' &middot; ' + lpEscape(item.hora) + '</div>' +
		'<div>' + lpEscape(item.telefono) + '</div>' +
		(item.comentarios ? '<div class="lp-muted">' + lpEscape(item.comentarios) + '</div>' : '') +
		'<div style="margin-top:8px"><a class="lp-btn lp-btn-mini" target="_blank" href="admin_modify_lead.php?lead_id=' +
		encodeURIComponent(item.lead_id) + '">Abrir lead</a></div>';
	cont.appendChild(div);
	setTimeout(function() {if (div.parentNode) {div.remove();}}, 60000);
	}

function lpEscape(t)
	{
	var d = document.createElement('div');
	d.appendChild(document.createTextNode(t == null ? '' : t));
	return d.innerHTML;
	}

function lpNotificar(item)
	{
	var cuando = item.vencido ? 'ahora (vencido hace ' + Math.abs(item.minutos) + ' min)' : 'en ' + item.minutos + ' min';
	lpToast(item);
	lpBeep();
	if (('Notification' in window) && Notification.permission == 'granted')
		{
		var n = new Notification('Recontacto ' + cuando, {
			body: item.nombre + ' - ' + item.telefono + (item.comentarios ? '\n' + item.comentarios : ''),
			tag: 'lp-cb-' + item.callback_id
			});
		n.onclick = function() {window.open('admin_modify_lead.php?lead_id=' + encodeURIComponent(item.lead_id), '_blank'); n.close();};
		}
	}

function lpRevisarAvisos()
	{
	fetch(lpURLavisos, {credentials: 'same-origin', cache: 'no-store'})
		.then(function(r) {return r.json();})
		.then(function(d)
			{
			document.getElementById('lp-c-venc').innerHTML = d.vencidos + ' vencidos';
			document.getElementById('lp-c-prox').innerHTML = (d.total - d.vencidos) + ' en la proxima hora';
			document.getElementById('lp-estado-avisos').innerHTML = 'Ultima revision ' + d.ahora.substr(11,5);

			for (var i=0; i<d.items.length; i++)
				{
				var it = d.items[i];
				/* Solo se avisa cuando ya llego la hora, y una sola vez */
				if (it.minutos > 0) {continue;}
				if (lpAvisados[it.callback_id]) {continue;}
				lpAvisados[it.callback_id] = 1;
				lpNotificar(it);
				}
			lpGuardarAvisados();
			})
		.catch(function(e)
			{
			document.getElementById('lp-estado-avisos').innerHTML = 'Sin conexion con el servidor';
			});
	}

document.addEventListener('DOMContentLoaded', function()
	{
	lpContar();
	lpRecipient();
	if (('Notification' in window) && Notification.permission == 'granted')
		{
		var b = document.getElementById('lp-btn-permiso');
		b.innerHTML = 'Avisos del navegador activados';
		b.disabled = true;
		}
	lpRevisarAvisos();
	setInterval(lpRevisarAvisos, 60000);
	});
</script>

</body>
</html>
