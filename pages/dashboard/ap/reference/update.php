<?php

$session->ensureLogin();
$session->ensureRole('Accounting');

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".apmast
	SET
		udref = " . $db->quote($_POST['udref']) . "
	WHERE
		apmast.invno = " . $db->quote($_POST['invno']) . "
");

print json_encode([
	'success' => True
]);
