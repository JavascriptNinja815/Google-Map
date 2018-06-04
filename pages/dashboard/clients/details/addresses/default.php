<?php

// Set all other addresses for this customer to "not default".
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".arcadr
	SET
		defaship = 'N'
	WHERE
		RTRIM(LTRIM(arcadr.custno)) = " . $db->quote(trim($_POST['custno'])) . " -- Under this customer.
		AND
		RTRIM(LTRIM(arcadr.defaship)) = 'Y' -- And is set as default
		AND
		RTRIM(LTRIM(arcadr.cshipno)) != " . $db->quote(trim($_POST['cshipno'])) . " -- And not the address were going to set as default.
");

// Set the specified address to default.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".arcadr
	SET
		defaship = 'Y'
	WHERE
		RTRIM(LTRIM(arcadr.custno)) = " . $db->quote(trim($_POST['custno'])) . " -- Under this customer.
		AND
		RTRIM(LTRIM(arcadr.cshipno)) = " . $db->quote(trim($_POST['cshipno'])) . " -- The address to update.
");

print json_encode([
	'success' => True
]);
