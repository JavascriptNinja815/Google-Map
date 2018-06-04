<?php

ini_set('max_execution_time', 300);

print '<pre>';

$fp = fopen('pages/tests/colson-import/import-pro-partnumbers.tsv', 'r');
$row = -1;
while(($data = fgetcsv($fp, 10000, "\t")) !== False) {
	$row++;
	if($row === 0) {
		continue; // Skip heading row
	}

	$product = [
		'colson_mpn' => trim($data[0]),
		'description' => trim($data[1]),
		'brand' => trim($data[2]),
		'pro_mpn' => trim($data[3])
	];
	print "Colson MPN: " . $product['colson_mpn'] . "\r\n";

	if(empty($product['pro_mpn'])) {
		print "\tEmpty Pro MPN, skipping.\r\n";
		continue;
	}

	$grab_priceitem = $db->query("
		SELECT
			price_items.price_item_id
		FROM
			" . DB_SCHEMA_ERP . ".price_items
		WHERE
			price_items.supplier_code = 'CGU01'
			AND
			price_items.part_number = " . $db->quote($product['colson_mpn']) . "
			AND
			(
				item = ''
				OR
				item IS NULL
			)
	");

	$priceitem = $grab_priceitem->fetch();
	if(!$priceitem) {
		print "\tItem not found, or already contains Pro MPN, skipping.\r\n";
		continue;
	}
	print "\tPrice Item ID: " . $priceitem['price_item_id'] . "\r\n";
	print "\tPro MPN: " . $product['pro_mpn'] . "\r\n";

	$update_item = $db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".price_items
		SET
			item = " . $db->quote($product['pro_mpn']) . "
		WHERE
			price_items.price_item_id = " . $db->quote($priceitem['price_item_id']) . "
			AND
			(
				price_items.item = ''
				OR
				price_items.item IS NULL
			)
	");
	print "\tRow(s) Updated: " . $update_item->rowCount() . "\r\n";
}

print '</pre>';
