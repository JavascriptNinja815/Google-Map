<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2018, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$grab_existing = $db->query("
	SELECT
		vendor_mappings.vendor_mapping_id
	FROM
		" . DB_SCHEMA_ERP . ".vendor_mappings
	WHERE
		vendor_brand = " . $db->quote(trim($_POST['vendor_brand'])) . "
		AND
		vendor_mpn = " . $db->quote(trim($_POST['vendor_partnumber'])) . "
		AND
		vpartno = " . $db->quote(trim($_POST['vpartno'])) . "
");
$existing = $grab_existing->fetch();
print_r($existing);
if(!empty($existing)) {
	print json_encode([
		'sucess' => False,
		'message' => 'Mapping already exists'
	]);
	exit;
}

$grab_new = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".vendor_mappings
	(
		vendor_brand,
		vendor_mpn,
		vpartno
	)
	OUTPUT
		Inserted.vendor_mapping_id
	VALUES (
		" . $db->quote(trim($_POST['vendor_brand'])) . ",
		" . $db->quote(trim($_POST['vendor_partnumber'])) . ",
		" . $db->quote(trim($_POST['vpartno'])) . "
	)
");
$new = $grab_new->fetch();

print json_encode([
	'success' => True,
	'vendor_mapping_id' => $new['vendor_mapping_id']
]);
