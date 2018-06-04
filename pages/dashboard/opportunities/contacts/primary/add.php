<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".opportunity_contacts_primary
	(
		contact_id,
		opportunity_id
	) VALUES (
		" . $db->quote($_POST['contact_id']) . ",
		" . $db->quote($_POST['opportunity_id']) . "
	)
");

print json_encode([
	'success' => True
]);
