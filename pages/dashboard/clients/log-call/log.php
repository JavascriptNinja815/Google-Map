<?php

$grab_client = $db->query("
	SELECT
		arcust.custno,
		arcust.oob
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		arcust.custno = " . $db->quote($_POST['custno']) . "
");
$client = $grab_client->fetch();

if(empty($client)) {
	print json_encode([
		'success' => False,
		'message' => 'Invalid client specified'
	]);
	exit;
}

$db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".cust_contact_log
	(
		custno,
		salesmn,
		added_on,
		memo
	) VALUES (
		" . $db->quote($_POST['custno']) . ",
		" . $db->quote($session->login['initials']) . ",
		GETDATE(),
		" . $db->quote($_POST['memo']) . "
	)
");

if(!empty($_POST['oob-memo'])) {
	$memo = $db->quote($_POST['oob-memo']);
} else {
	$memo = 'NULL';
}

if(!empty($_POST['oob'])) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".arcust
		SET
			oob = 1,
			oob_notes = " . $memo . "
		WHERE
			arcust.custno = " . $db->quote($client['custno']) . "
	");
} else if(empty($_POST['oob']) && !empty($client['oob'])) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".arcust
		SET
			oob = NULL,
			oob_notes = " . $memo . "
		WHERE
			arcust.custno = " . $db->quote($client['custno']) . "
	");
}

print json_encode([
	'success' => True
]);
