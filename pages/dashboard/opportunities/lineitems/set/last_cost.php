<?php

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	SET
		last_cost = " . $db->quote($_POST['value']) . "
	WHERE
		opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($_POST['opportunity_lineitem_id']) . "
");

print json_encode([
	'success' => True
]);
