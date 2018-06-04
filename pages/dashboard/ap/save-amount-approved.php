<?php

$session->ensureLogin();
$session->ensureRole('Accounting');

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".apmast
	SET
		aprpay = " . $db->quote($_POST['amount']) . "
	WHERE
		apmast.invno = " . $db->quote($_POST['invno']) . "
");

print json_encode([
	'success' => True
]);
