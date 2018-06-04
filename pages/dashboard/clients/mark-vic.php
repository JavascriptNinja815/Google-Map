<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Sales');

$vic = $_REQUEST['vic'] == 'true' ? 1 : 0;

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".arcust
	SET
		vic = '" . $vic . "'
	WHERE
		custno = " . $db->quote($_REQUEST['custno']) . "
");

print json_encode(array(
	'success' => True
));