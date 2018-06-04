<?php

$session->ensureLogin();

// Ensure custno passed exists in DB.
$grab_client = $db->query("
	SELECT
		LTRIM(RTRIM(arcust.custno)) AS custno
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		UPPER(LTRIM(RTRIM(arcust.custno))) = " . $db->quote(strtoupper($_POST['custno'])) . "
");
$client = $grab_client->fetch();

if(!$client) {
	print json_encode([
		'success' => False,
		'message' => 'Invalid Client Code'
	]);
	exit;
}

print json_encode([
	'success' => True,
	'custno' => $client['custno']
]);
