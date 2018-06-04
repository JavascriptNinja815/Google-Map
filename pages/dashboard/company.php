<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2015, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */ 

$session->ensureLogin();

$db_conn = DB::get();
$db_conn->query("
	UPDATE
		" . DB_SCHEMA_INTERNAL . ".login_sessions
	SET
		company_id = " . $db_conn->quote($_REQUEST['company']) . "
	WHERE
		login_sessions.login_id = '" . $session->login['login_id'] . "'
		AND
		login_sessions.session_id = '" . $session->login['session_id'] . "'
");


// Allow redirecting to other pages.
if(isset($_GET['redirect'])){
	$uri = $_GET['redirect'];
}else{
	$uri = '/dashboard';
}

header('Location: '.$uri);