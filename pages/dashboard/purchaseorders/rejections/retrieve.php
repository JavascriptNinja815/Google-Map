<?php

// Grab general information about the PO(s) we'll be working with.
$grab_po_info = $db->query("
	SELECT DISTINCT
			potran.purno AS po_number,
			potran.vendno AS vendor,
			potran.purdate AS purchase_date
	FROM
		" . DB_SCHEMA_ERP . ".potran
	WHERE
		RTRIM(LTRIM(potran.purno)) = " . $db->quote($_POST['po-number']) . "
");
$po_info = $grab_po_info->fetch();

// Grab all of the items associated with the PO passed.
$grab_po_items = $db->query("
	SELECT
		potran.vpartno AS vendor_part_number,
		potran.descrip AS item_name,
		potran.quality_r AS return_quantity,
		potran.quality_n AS return_notes
	FROM
		" . DB_SCHEMA_ERP . ".potran
	WHERE
		RTRIM(LTRIM(potran.purno)) = " . $db->quote($_POST['po-number']) . "
		AND
		RTRIM(LTRIM(potran.vpartno)) != ''
");
$po_items = $grab_po_items->fetchAll();

if(empty($po_info)) { // No record returned.
	print json_encode(array(
		'success' => False,
		'message' => 'The PO specified doesn\'t exist'
	));
	exit();
}

// Return the results.
print json_encode(array(
	'success' => True,
	'po' => array(
		'info' => $po_info,
		'items' => $po_items
	)
));
