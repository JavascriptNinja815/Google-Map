<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$db->query("
	DELETE FROM
		" . DB_SCHEMA_ERP . ".opportunity_lineitem_prices
	WHERE
		opportunity_lineitem_prices.price_id = " . $db->quote($_POST['price_id']) . "
");

print json_encode([
	'success' => True
]);
