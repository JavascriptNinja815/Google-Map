<?php

$session->ensureLogin();
$session->ensureRole('Administrator');

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".icitem
	SET
		itmdes2 = " . $db->quote($_POST['description']) . "
	WHERE
		icitem.item = " . $db->quote($_POST['item']) . "
");

print json_encode([
	'success' => True
]);
