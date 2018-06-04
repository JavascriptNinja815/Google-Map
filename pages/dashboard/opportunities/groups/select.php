<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_opportunity_group = $db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunity_groups
	SET
		selected = " . $db->quote($_POST['selected']) . "
	WHERE
		opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
");

print json_encode([
	'success' => True
]);
