<?php

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".artran
	SET
		paydate = " . $db->quote($_POST['paydate']) . "
	WHERE
		invno = " . $db->quote($_POST['invno']) . "
");

print json_encode(array(
	'success' => True
));
