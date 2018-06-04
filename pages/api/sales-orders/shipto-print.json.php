<?php

// Ensure a Sales Order Number has been passed.
if(empty($_REQUEST['so'])) {
	print json_encode([
		'success' => False,
		'error-code' => 100,
		'message' => 'Missing required parameter "so"'
	]);
	exit;
}

// Ensure a printer has been specified.
if(empty($_REQUEST['printer_id'])) {
	$printer_id = 102;
} else {
	$printer_id = $_REQUEST['printer_id'];
}

// Ensure quantity is specified.
if(empty($_REQUEST['quantity'])) {
	$quantity = 1;
} else {
	$quantity = $_REQUEST['quantity'];
}

/**
 * Ensure the Sales Order number specified exists.
 */
$grab_shipto_data = $db->prepare("
	SELECT
		LTRIM(RTRIM(soaddr.sono)) AS sono,
		LTRIM(RTRIM(soaddr.company)) AS company,
		LTRIM(RTRIM(soaddr.address1)) AS address1,
		LTRIM(RTRIM(soaddr.address2)) AS address2,
		LTRIM(RTRIM(soaddr.city)) AS city,
		LTRIM(RTRIM(soaddr.addrstate)) AS state,
		LTRIM(RTRIM(soaddr.zip)) AS zip,
		LTRIM(RTRIM(soaddr.country)) AS country,
		LTRIM(RTRIM(somast.ponum)) AS ponum,
		LTRIM(RTRIM(somast.shipvia)) AS shipvia
	FROM
		" . DB_SCHEMA_ERP . ".soaddr
	INNER JOIN
		" . DB_SCHEMA_ERP . ".somast
		ON
		somast.sono = soaddr.sono
	WHERE
		LTRIM(RTRIM(soaddr.sono)) = " . $db->quote($_REQUEST['so']) . "
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$grab_shipto_data->execute();

if(!$grab_shipto_data->rowCount()) {
	// Sales Order number specified doesn't exist.
	print json_encode(array(
		'success' => False,
		'error-code' => 101,
		'message' => 'Sales Order Number specified does not exist'
	));
	exit();
}
$shipto_data = $grab_shipto_data->fetch();

// Combine multiple fields into single string.
$shipto_data['address'] = $shipto_data['company']
						. "\r\n"
						. $shipto_data['address1']
						. "\r\n"
						. (!empty($shipto_data['address2']) ? $shipto_data['address2'] . "\r\n" : "")
						. $shipto_data['city'] . ', ' . $shipto_data['state'] . ' ' . $shipto_data['zip']
						. "\r\n"
						. $shipto_data['country']
;

// Delete the originally separated fields.
unset($shipto_data['company'], $shipto_data['address1'], $shipto_data['address2'], $shipto_data['city'], $shipto_data['state'], $shipto_data['zip'], $shipto_data['country']);

// Send the job off for printing.
$label_printer = new LabelPrinter($printer_id);

$curr = 0;
while($curr < $quantity) {
	$curr++;
	$label_printer->printShipToLabel(
		[ $shipto_data ]
	);
}

print json_encode([
	'success' => True
]);
