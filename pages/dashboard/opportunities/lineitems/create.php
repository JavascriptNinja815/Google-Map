<?php

$session->ensureLogin();
$session->ensureRole('Sales');

// Count current lineitems.
$grab_count = $db->query("
	SELECT
		COUNT(*) AS count
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	WHERE
		opportunity_lineitems.opportunity_group_id = " . $db->quote($_POST['opportunity_group_id']) . "
");
$count = $grab_count->fetch();
$position = $count['count'] + 1;

$grab_lineitem = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	(
		opportunity_group_id,
		position
	)
	OUTPUT
		INSERTED.opportunity_lineitem_id
	VALUES (
		" . $db->quote($_POST['opportunity_group_id']) . ",
		" . $db->quote($position) . "
	)
");
$lineitem = $grab_lineitem->fetch();

$grab_lineitem_price = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".opportunity_lineitem_prices
	(
		lineitem_id
	)
	OUTPUT
		INSERTED.price_id
	VALUES (
		" . $db->quote($lineitem['opportunity_lineitem_id']) . "
	)
");
$lineitem_price = $grab_lineitem_price->fetch();

print json_encode([
	'success' => True,
	'opportunity_lineitem_id' => $lineitem['opportunity_lineitem_id'],
	'price_id' => $lineitem_price['price_id'],
	'position' => $position
]);
