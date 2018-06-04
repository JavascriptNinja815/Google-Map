<?php

ini_set('max_execution_time', 300);

$supplier_code = 'CGU01';

print '<pre>';

$fp = fopen('pages/tests/colson-import/import-pricing-ammendments.tsv', 'r');
$row = -1;
while(($data = fgetcsv($fp, 100000, "\t")) !== False) {
	$row++;
	if($row === 0) {
		continue; // Skip heading row
	}

	$product = [
		'customer_key' => trim($data[0]),
		'customer_number' => trim($data[1]),
		'customer_parent' => trim($data[2]),
		'part_number' => trim($data[3]),
		'description' => trim($data[4]),
		'brand' => trim($data[5]),
		'12month_sales' => trim($data[6]),
		'price_1_qty' => $data[7],
		'price_1' => $data[8],
		'price_2_qty' => $data[9],
		'price_2' => $data[10],
		'price_3_qty' => $data[11],
		'price_3' => $data[12],
		'price_4_qty' => $data[13],
		'price_4' => $data[14],
		'price_5_qty' => $data[15],
		'price_5' => $data[16],
		'price_special_qty' => $data[17],
		'price_special' => $data[18]
	];

	print $product['brand'] . ' -- ' . $product['part_number'] . "\r\n";

	$grab_price_item = $db->query("
		SELECT
			price_items.price_item_id,
			price_items.part_number,
			price_items.description,
			price_items.brand,
			price_items.item
		FROM
			" . DB_SCHEMA_ERP . ".price_items
		WHERE
			price_items.supplier_code = " . $db->quote($supplier_code) . "
			AND
			price_items.brand = " . $db->quote($product['brand']) . "
			AND
			price_items.part_number = " . $db->quote($product['part_number']) . "
	");
	$price_item = $grab_price_item->fetch();
	if(!$price_item) {
		print "\tPrice Item doesn't exist, creating it\r\n";

		// Resolve Pro MPN if available.
		$grab_pro_mpn = $db->query("
			SELECT
				LTRIM(RTRIM(icitem.item)) AS item
			FROM
				" . DB_SCHEMA_ERP . ".icitem
			WHERE
				LTRIM(RTRIM(itmdesc)) = " . $db->quote($product['part_number']) . "
				OR
				LTRIM(RTRIM(itmdesc)) = " . $db->quote($product['description']) . "
		");
		$pro_mpn = $grab_pro_mpn->fetch();
		if($pro_mpn) {
			$item = $pro_mpn['item'];
		} else {
			$item = Null;
		}

		$grab_price_item = $db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".price_items
			(
				supplier_code,
				part_number,
				description,
				brand
				" . ($item ? ', item' : Null) . "
			)
			OUTPUT
				INSERTED.price_item_id,
				INSERTED.part_number,
				INSERTED.description,
				INSERTED.brand,
				INSERTED.item
			VALUES (
				" . $db->quote($supplier_code) . ",
				" . $db->quote($product['part_number']) . ",
				" . $db->quote($product['description']) . ",
				" . $db->quote($product['brand']) . "
				" . ($item ? ', ' . $db->quote($item['item']) : Null) . "
			)
		");
		$price_item = $grab_price_item->fetch();
	}
	print "\tPrice Item ID: " . $price_item['price_item_id'] . "\r\n";
	print "\tPart Number: " . $price_item['part_number'] . "\r\n";
	print "\tDescription: " . $price_item['description'] . "\r\n";
	print "\tBrand: " . $price_item['brand'] . "\r\n";
	print "\tItem: " . $price_item['item'] . "\r\n";

	print "\tPrices:\r\n";

	foreach(['price_1', 'price_2', 'price_3', 'price_4', 'price_5', 'price_special'] as $field) {
		$price = trim(str_replace('$', '', str_replace(',', '', $product[$field])));
		$quantity = trim(str_replace(',', '', $product[$field . '_qty']));
		if($price && $quantity) { // Check if fields contain data.
			print "\t\t" . $field . "\r\n";

			print "\t\t\tQuantity: " . $quantity . "\r\n";
			print "\t\t\tPrice: " . $price . "\r\n";

			$grab_current_price = $db->query("
				SELECT
					price_quantities.price_quantity_id,
					price_quantities.price,
					price_quantities.quantity
				FROM
					" . DB_SCHEMA_ERP . ".price_quantities
				INNER JOIN
					" . DB_SCHEMA_ERP . ".price_items
					ON
					price_items.price_item_id = price_quantities.price_item_id
				WHERE
					price_items.supplier_code = " . $db->quote($supplier_code) . "
					AND
					price_items.part_number = " . $db->quote($price_item['part_number']) . "
					AND
					price_items.brand = " . $db->quote($price_item['brand']) . "
					AND
					price_quantities.quantity = " . $db->quote($quantity) . "
					AND
					price_quantities.expires_date IS NULL -- Ensure the quantity price has not expired.
					AND
					special_pricing = " . ($field == 'price_special' ? '1' : '0') . "
			");
			$current_price = $grab_current_price->fetch();
			$insert = False;
			if(!$current_price) {
				$insert = True;
				print("\t\t\tNo Price Entry Found, Inserting\r\n");
			} else {
				print "\t\t\tPrice Entry found:\r\n";
				print "\t\t\t\tPrice Quantity ID: " . $current_price['price_quantity_id'] . "\r\n";
				print "\t\t\t\tQuantity: " . $current_price['quantity'] . "\r\n";
				print "\t\t\t\tPrice: " . $current_price['price'] . "\r\n";

				$current_price_formatted = number_format($current_price['price'], 2, '.', '');
				$new_price_formatted = number_format($price, 2, '.', '');
				print("\t\t\t\tPrices Formatted: " . $current_price_formatted . " vs " . $new_price_formatted . "\r\n");

				if($current_price_formatted != $new_price_formatted) {
					$insert = True;
					print "\t\t\tPrice Change Detected, Expiring and Inserting new price\r\n";
					$db->query("
						UPDATE
							" . DB_SCHEMA_ERP . ".price_quantities
						SET
							expires_date = GETDATE()
						WHERE
							price_quantities.price_quantity_id = " . $db->quote($current_price['price_quantity_id']) . "
					");
				} else {
					print "\t\t\tPrices are the same, nothing to do\r\n";
				}
			}
			if($insert) {
				$grab_new_price = $db->query("
					INSERT INTO
						" . DB_SCHEMA_ERP . ".price_quantities
					(
						price_item_id,
						quantity,
						price,
						special_pricing,
						effective_date
					)
					OUTPUT
						INSERTED.price_quantity_id
					VALUES (
						" . $db->quote($price_item['price_item_id']) . ",
						" . $db->quote($quantity) . ",
						" . $db->quote($price) . ",
						" . ($field == 'price_special' ? '1' : '0') . ",
						GETDATE()
					)
				");
				$new_price = $grab_new_price->fetch();
				print "\t\t\t\tNew Price Quantity ID: " . $new_price['price_quantity_id'] . "\r\n";
			}
		}
	}
}

print '</pre>';
