<?php

if(isset($_POST['item_code'])) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunity_lineitems
		SET
			item_code = " . $db->quote($_POST['item_code']) . "
		WHERE
			opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($_POST['opportunity_lineitem_id']) . "
	");
} else if(isset($_POST['partnumber'])) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunity_lineitems
		SET
			itmdesc = " . $db->quote($_POST['partnumber']) . "
		WHERE
			opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($_POST['opportunity_lineitem_id']) . "
	");
}

$grab_item = $db->query("
	SELECT
		icitem.id_col,
		icitem.item AS item_code,
		icitem.lstcost AS last_cost,
		(SELECT SUM(iciloc.lonhand) FROM " . DB_SCHEMA_ERP . ".iciloc WHERE " . (
			isset($_POST['item_code']) ?
				"iciloc.item = " . $db->quote(strtoupper(trim($_POST['item_code'])))
			:
				"iciloc.item = " . $db->quote(strtoupper(trim($_POST['partnumber'])))
		) . ") AS available,
		icitem.itmdesc,
		icitem.itmdes2 AS itmdesc2
	FROM
		" . DB_SCHEMA_ERP . ".icitem
	WHERE
		" . (
			isset($_POST['item_code']) ?
				"icitem.item = " . $db->quote(strtoupper(trim($_POST['item_code'])))
			:
				"icitem.itmdesc = " . $db->quote(strtoupper(trim($_POST['partnumber'])))
		) . "
");

if(!$grab_item->rowCount()) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".opportunity_lineitems
		SET
			icitem_id_col = NULL
		WHERE
			opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($_POST['opportunity_lineitem_id']) . "
	");
	print json_encode([
		'success' => False
	]);
	exit;
}

$item = $grab_item->fetch();

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".opportunity_lineitems
	SET
		icitem_id_col = " . $db->quote($item['id_col']) . ",
		" . (
			isset($_POST['item_code']) ?
				"itmdesc = " . $db->quote(strtoupper(trim($item['itmdesc']))) .
				", " .
				"itmdesc2 = " . $db->quote(trim($item['itmdesc2']))
			:
				"item_code = " . $db->quote(strtoupper(trim($item['item_code'])))
		) . "
	WHERE
		opportunity_lineitems.opportunity_lineitem_id = " . $db->quote($_POST['opportunity_lineitem_id']) . "
");

print json_encode([
	'success' => True,
	'item_code' => trim($item['item_code']),
	'last_cost' => number_format($item['last_cost'], 2),
	'available' => number_format($item['available'], 0),
	'itmdesc' => trim($item['itmdesc']),
	'itmdesc2' => trim($item['itmdesc2'])
]);
