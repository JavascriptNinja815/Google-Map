<?php

$grab_items = $db->query("
	SELECT TOP 1000
		icitem.item AS item,
		icitem.itmdesc AS part_number,
		iciloc.loctid AS location,
		iciqty.qonhand AS local_stock,
		icitem.ionhand AS total_stock,
		icitem.makeitr AS bom,
		icitem.lstcost AS last_cost,
		icitem.itmdes2 AS description,
		CONVERT(varchar(10), icitem.ilrecv, 120) AS last_received,
		CONVERT(varchar(10), icitem.ilsale, 120) AS last_sale,
		iciloc.lsupplr AS lastvendor,
		icitem.plinid AS productline,
		icitem.itmclss AS class
	FROM
		PRO01.dbo.icitem
	INNER JOIN
		PRO01.dbo.iciloc
		ON
		iciloc.item = icitem.item
	LEFT JOIN
		PRO01.dbo.iciqty
		ON
		iciloc.item = iciqty.item
		AND
		iciloc.loctid = iciqty.loctid
	WHERE
		LTRIM(RTRIM(iciloc.loctid)) = 'DC'
	ORDER BY
		icitem.item
");

//$fp_json = fopen(BASE_PATH . '/pages/export/inventory-for-magento/inventory.json', 'w');
$csv_filename = BASE_PATH . '/pages/export/inventory-for-magento/inventory.csv';
$fp_csv = fopen($csv_filename, 'w');
$ct = 0;
//$json_products = [];
foreach($grab_items as $item) {
	$ct++;
	$row = [
		'item' => trim($item['item']),
		'part_number' => trim($item['part_number']),
		'title' => trim($item['description']),
		'local_inventory' => (int)$item['local_stock'],
		'total_inventory' => (int)$item['total_stock'],
		'bom' => trim($item['bom']),
		'cost' => number_format(trim($item['last_cost']), 2, '.', ''),
		'supplier' => trim($item['lastvendor']),
		'product_line' => trim($item['productline']),
		'item_class' => trim($item['class'])
	];
	// Add line to JSON.
	//$json_products[] = $row;
	// Add line to CSV file.
	if($ct === 1) {
		fputcsv($fp_csv, array_keys($row));
	}
	fputcsv($fp_csv, $row);
}
fclose($fp_csv);

print file_get_contents($csv_filename);

// Write products to JSON file.
//fputcsv($fp_json, json_encode($row));
//fclose($fp_json);
