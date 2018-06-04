<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2018, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".vendor_mappings
	WHERE
		vendor_mappings.vendor_mapping_id = " . $db->quote(trim($_POST['vendor_mapping_id'])) . "
");

print json_encode([
	'success' => True
]);
