<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".opportunity_contacts_primary
	WHERE
		opportunity_contacts_primary.contact_id = " . $db->quote($_POST['contact_id']) . "
		AND
		opportunity_contacts_primary.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");

print json_encode([
	'success' => True
]);
