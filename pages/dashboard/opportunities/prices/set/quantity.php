<?php

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunity_lineitem_prices
	SET
		quantity = " . $db->quote($_POST['value']) . "
	WHERE
		opportunity_lineitem_prices.price_id = " . $db->quote($_POST['price_id']) . "
");

print json_encode([
	'success' => True
]);
