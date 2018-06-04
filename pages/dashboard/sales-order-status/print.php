<?php

$print_data = array();
foreach($_POST['items'] as $item) {
	$grab_label_data = $db->query("
		SELECT
			sotran.item AS item_number,
			icitem.itmdesc AS item_description,
			sotran.sono AS sales_order_number,
			sotran.descrip AS so_description,
			sotran.cpartno AS client_part_number,
			somast.ponum AS client_purchase_order_number,
			sotran.rqdate AS required_date
		FROM
			" . DB_SCHEMA_ERP . ".sotran
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".icitem
			ON
			sotran.item = icitem.item
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".somast
			ON
			sotran.sono = somast.sono
		WHERE
			RTRIM(LTRIM(sotran.item)) = " . $db->quote($item['item-number']) . "
			AND
			RTRIM(LTRIM(somast.sono)) = " . $db->quote($_POST['sales-order-number']) . "
	");
	$label_data = $grab_label_data->fetch();
	if(!empty($label_data)) {
		$label_ct = 0;
		while(++$label_ct <= $item['number-of-labels']) {
			$print_data[] = array(
				'sotran.item' => $label_data['item_number'],
				'icitem.itmdesc' => $label_data['item_description'],
				'sotran.sono' => $label_data['sales_order_number'],
				'sotran.descrip' => $label_data['so_description'],
				'sotran.cpartno' => $label_data['client_part_number'],
				'somast.ponum' => $label_data['client_purchase_order_number'],
				'sotran.reqdate' => $label_data['required_date'],
				'box-quantity' => ((int)$item['qty-per-box']) > 0 ? 'Quantity: ' . $item['qty-per-box'] : ''
			);
		}
	}
}

$label_printer = new LabelPrinter($_POST['printer']);
$label_printer->printSalesOrders($labels_result);
