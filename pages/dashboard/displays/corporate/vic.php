<?php

$grab_data = $db->query("
	SELECT TOP 10
		somast.sono AS sales_order_number,
		somast.custno AS customer_code,
		somast.orderstat AS sales_order_status
	FROM
		" . DB_SCHEMA_ERP . ".somast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.custno = somast.custno
	WHERE
		somast.ordate = '" . date('Y-m-d', time()) . "'
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) IN ('ISS', 'PICKING', 'PRODUCTION', 'Q''D - CUSTOM', 'QCUSTOM', 'QUEUED')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		arcust.vic = 1
	ORDER BY
		somast.custno
");
$vic = [];
foreach($grab_data as $data) {
	$vic[] = [
		'sales_order_number' => trim($data['sales_order_number']),
		'customer_code' => trim($data['customer_code']),
		'sales_order_status' => trim($data['sales_order_status'])
	];
}

print json_encode([
	'success' => True,
	'vic' => $vic
]);
