<?php

ini_set('max_execution_time', 300);

$db->query("TRUNCATE TABLE " . DB_SCHEMA_ERP . ".price_items");
$db->query("TRUNCATE TABLE " . DB_SCHEMA_ERP . ".price_quantities");

$fp = fopen('pages/tests/colson-import/ColsonPrices.csv', 'r');
$row = -1;
while(($data = fgetcsv($fp, 10000, ',')) !== False) {
	$row++;
	if($row === 0) {
		continue; // Skip heading row
	}
	$product = [
		'part_number' => trim($data[0]),
		'description' => trim($data[1]),
		'brand' => trim($data[2]),
		'price_1_qty' => trim($data[3]),
		'price_1' => trim(str_replace('$', '', $data[4])),
		'price_2_qty' => trim($data[5]),
		'price_2' => trim(str_replace('$', '', $data[6])),
		'price_3_qty' => trim($data[7]),
		'price_3' => trim(str_replace('$', '', $data[8]))
	];
	
	$item = Null;
	$grab_item = $db->query("
		SELECT
			LTRIM(RTRIM(icitem.item)) AS item
		FROM
			" . DB_SCHEMA_ERP . ".icitem
		WHERE
			LTRIM(RTRIM(itmdesc)) = " . $db->quote($product['part_number']) . "
			OR
			LTRIM(RTRIM(itmdesc)) = " . $db->quote($product['description']) . "
	");
	$item_found = $grab_item->fetch();
	if($item_found) {
		$item = $item_found['item'];
	}

	$grab_item_entry = $db->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".price_items
		(
			supplier_code,
			part_number,
			description,
			brand,
			item
		)
		OUTPUT INSERTED.price_item_id
		VALUES (
			" . $db->quote('CGU01') . ",
			" . $db->quote($product['part_number']) . ",
			" . $db->quote($product['description']) . ",
			" . $db->quote($product['brand']) . ",
			" . ($item ? $db->quote($item) : 'NULL') . "
		)
	");
	$item_entry = $grab_item_entry->fetch();
	if($product['price_1_qty'] && $product['price_1']) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".price_quantities
			(
				price_item_id,
				quantity,
				price
			) VALUES (
				" . $db->quote($item_entry['price_item_id']) . ",
				" . $db->quote($product['price_1_qty']) . ",
				" . $db->quote($product['price_1']) . "
			)
		");
	}
	if($product['price_2_qty'] && $product['price_2']) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".price_quantities
			(
				price_item_id,
				quantity,
				price
			) VALUES (
				" . $db->quote($item_entry['price_item_id']) . ",
				" . $db->quote($product['price_2_qty']) . ",
				" . $db->quote($product['price_2']) . "
			)
		");
	}
	if($product['price_3_qty'] && $product['price_3']) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".price_quantities
			(
				price_item_id,
				quantity,
				price
			) VALUES (
				" . $db->quote($item_entry['price_item_id']) . ",
				" . $db->quote($product['price_3_qty']) . ",
				" . $db->quote($product['price_3']) . "
			)
		");
	}
}
fclose($fp);

print 'Done!';
