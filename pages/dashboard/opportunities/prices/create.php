<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_price = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".opportunity_lineitem_prices
	(
		lineitem_id
	)
	OUTPUT
		INSERTED.price_id
	VALUES (
		" . $db->quote($_POST['opportunity_lineitem_id']) . "
	)
");
$price = $grab_price->fetch();

print json_encode([
	'success' => True,
	'price_id' => $price['price_id']
]);
