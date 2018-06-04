<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Administration');

if($_GET['login_id'] != $session->login['login_id']) {
	// Delete user.
	$db->query("
		DELETE FROM
			" . DB_SCHEMA_INTERNAL . ".logins
		WHERE
			logins.login_id = " . $db->quote($_GET['login_id']) . "
	");
	
	// Delete user's association with companies.
	$db->query("
		DELETE FROM
			" . DB_SCHEMA_INTERNAL . ".login_companies
		WHERE
			login_companies.login_id = " . $db->quote($_GET['login_id']) . "
	");
}

header('Location: /dashboard/administration/logins');
