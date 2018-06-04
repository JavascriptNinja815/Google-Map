<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$custno = explode(' - ', $_POST['custno']);
$custno = $custno[0];

$grab_client = $db->query("
	SELECT
		custno
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		custno = " . $db->quote($custno) . "
");
$client = $grab_client->fetch();

if(!$client) {
	print json_encode([
		'success' => False
	]);
	exit;
}

print json_encode([
	'success' => True
]);
