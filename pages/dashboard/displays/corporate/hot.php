<?php

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time()),
	'hot' => array()
);

$grab_data = $db->query("
	SELECT
		somast.sono AS sales_order_number,
		somast.custno AS customer_code,
		somast.sostat AS sales_order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.hot = 1
		AND
		somast.sostat NOT IN ('V', 'C')
		AND
		somast.sotype NOT IN ('B', 'R')
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		somast.ordate = '" . date('Y-m-d', time()) . "'
");
$hot = [];
foreach($grab_data as $data) {
	$hot[] = [
		'sales_order_number' => trim($data['sales_order_number']),
		'customer_code' => trim($data['customer_code']),
		'sales_order_status' => trim($data['sales_order_status'])
	];
}

print json_encode([
	'success' => True,
	'hot' => $hot
]);
