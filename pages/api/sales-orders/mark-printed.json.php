<?php

/*

This will return an array with a success of True or False.
When returning False, a "message" and "error-code" will also be returned.
"message" is a human-readable error message.
"error-code" defines the type of error encountered.
	100 = Missing required parameter "sales-order-number"
	101 = Sales Order Number specified does not exist
	200 = Record previously marked as shipped, nothing to change.

 */

/**
 * Ensure a Sales Order Number has been passed.
 */
if(empty($_REQUEST['sales-order-number'])) {
	print json_encode(array(
		'success' => False,
		'error-code' => 100,
		'message' => 'Missing required parameter "sales-order-number"'
	));
	exit();
}

/**
 * Ensure the Sales Order number specified exists.
 */
$grab_sales_order = $db->prepare("
	SELECT
		LTRIM(RTRIM(somast.sono)) AS sales_order_number,
		CONVERT(varchar(10), somast.printed, 120) AS printed
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		LTRIM(RTRIM(somast.sono)) = " . $db->quote($_REQUEST['sales-order-number']) . "
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$grab_sales_order->execute();

if(!$grab_sales_order->rowCount()) {
	// Sales Order number specified doesn't exist.
	print json_encode(array(
		'success' => False,
		'error-code' => 101,
		'message' => 'Sales Order Number specified does not exist'
	));
	exit();
}
$sales_order = $grab_sales_order->fetch();

/**
 * Ensure the Sales Order returned hasn't previously been marked as "printed"
 */
//print trim($sales_order['printed']);
if(trim($sales_order['printed']) != '1900-01-01') {
	print json_encode(array(
		'success' => False,
		'error-code' => 200,
		'message' => 'Record previously marked as printed, nothing to change'
	));
	exit();
}

/**
 * Update the Sales Order's "printed" date.
 */
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".somast
	SET
		printed = GETDATE()
	WHERE
		LTRIM(RTRIM(somast.sono)) = " . $db->quote($_REQUEST['sales-order-number']) . "
");

print json_encode(array(
	'success' => True
));
