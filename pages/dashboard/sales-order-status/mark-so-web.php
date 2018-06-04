<?php

$result = array(
	'success' => True
);

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".somast
	SET
		web = " . $db->quote($_POST['web']) . "
	WHERE
		RTRIM(LTRIM(somast.sono)) = " . $db->quote($_POST['sales-order-number']) . "
");

print json_encode($result);
