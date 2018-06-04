<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Sales');

$result = array(
	'success' => true
);

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".armast
	SET
		followup = " . $db->quote($_POST['followup']) . "
	WHERE
		armast.custno = " . $db->quote($_POST['custno']) . "
");

print json_encode($result);
